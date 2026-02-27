<?php
/**
 * Admin Controller
 *
 * Handles all admin panel routes: login, specimen CRUD, photo management, field management.
 */

class AdminController
{
    // ============================================
    // Authentication
    // ============================================

    public static function loginForm(): void
    {
        if (Auth::check()) redirect('/admin');
        view('admin/login');
    }

    public static function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::login($username, $password)) {
            flash('success', 'Welcome back!');
            redirect('/admin');
        }

        flash('error', 'Invalid username or password.');
        redirect('/admin/login');
    }

    public static function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been logged out.');
        redirect('/admin/login');
    }

    // ============================================
    // Dashboard
    // ============================================

    public static function dashboard(): void
    {
        Auth::requireLogin();

        $stats = [
            'total_specimens'     => Specimen::count(),
            'published_specimens' => Specimen::count(true),
            'total_fields'        => (int)Database::fetch('SELECT COUNT(*) as c FROM custom_fields WHERE is_active = 1')['c'],
            'total_photos'        => (int)Database::fetch('SELECT COUNT(*) as c FROM photos')['c'],
        ];

        // Recent specimens
        $recent = Database::fetchAll(
            'SELECT s.*, p.filename AS photo_filename
             FROM specimens s
             LEFT JOIN photos p ON p.specimen_id = s.id AND p.is_primary = 1
             ORDER BY s.updated_at DESC LIMIT 10'
        );

        view('admin/dashboard', [
            'stats'  => $stats,
            'recent' => $recent,
        ], 'admin');
    }

    // ============================================
    // Specimens
    // ============================================

    public static function specimenList(): void
    {
        Auth::requireLogin();

        $page = currentPage();
        $config = require __DIR__ . '/../config.php';
        $result = Specimen::paginate($page, $config['per_page'], false);

        view('admin/specimens/list', [
            'specimens' => $result['items'],
            'total'     => $result['total'],
            'page'      => $page,
            'perPage'   => $config['per_page'],
        ], 'admin');
    }

    public static function specimenForm(?int $id = null): void
    {
        Auth::requireLogin();

        $specimen = null;
        if ($id) {
            $specimen = Specimen::find($id);
            if (!$specimen) {
                flash('error', 'Specimen not found.');
                redirect('/admin/specimens');
            }
        }

        $fields = CustomField::all();

        view('admin/specimens/form', [
            'specimen' => $specimen,
            'fields'   => $fields,
        ], 'admin');
    }

    public static function specimenSave(?int $id = null): void
    {
        Auth::requireLogin();

        if (!Auth::verifyCsrf()) {
            flash('error', 'Invalid form submission. Please try again.');
            redirect($id ? "/admin/specimens/{$id}/edit" : '/admin/specimens/create');
        }

        $data = [
            'name'         => trim($_POST['name'] ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'sort_order'   => (int)($_POST['sort_order'] ?? 0),
        ];

        if (empty($data['name'])) {
            flash('error', 'Name is required.');
            redirect($id ? "/admin/specimens/{$id}/edit" : '/admin/specimens/create');
        }

        if ($id) {
            Specimen::update($id, $data);
        } else {
            $id = Specimen::create($data);
        }

        // Save custom field values
        $fieldValues = $_POST['fields'] ?? [];
        $processedValues = [];

        foreach ($fieldValues as $fieldId => $value) {
            // Handle multi_select (comes as array)
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $processedValues[(int)$fieldId] = $value;
        }

        Specimen::saveFieldValues($id, $processedValues);

        // Handle photo uploads (for new specimens)
        $files = $_FILES['photos'] ?? null;
        if ($files && !empty($files['name'][0])) {
            // Normalize single/multiple file uploads
            if (!is_array($files['name'])) {
                $files = [
                    'name'     => [$files['name']],
                    'type'     => [$files['type']],
                    'tmp_name' => [$files['tmp_name']],
                    'error'    => [$files['error']],
                    'size'     => [$files['size']],
                ];
            }

            $uploadCount = 0;
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];

                $result = processUpload($file, $id);
                if ($result) {
                    Photo::create($id, $result);
                    $uploadCount++;
                }
            }

            if ($uploadCount > 0) {
                flash('success', "Specimen saved with {$uploadCount} photo(s).");
            } else {
                flash('success', 'Specimen saved successfully.');
            }
        } else {
            flash('success', 'Specimen saved successfully.');
        }

        // "Save & Add Another" redirects to a fresh create form
        if (isset($_POST['add_another'])) {
            redirect('/admin/specimens/create');
        } else {
            redirect("/admin/specimens/{$id}/edit");
        }
    }

    public static function specimenDelete(int $id): void
    {
        Auth::requireLogin();

        if (!Auth::verifyCsrf()) {
            flash('error', 'Invalid request.');
            redirect('/admin/specimens');
        }

        Specimen::delete($id);
        flash('success', 'Specimen deleted.');
        redirect('/admin/specimens');
    }

    // ============================================
    // Photos
    // ============================================

    public static function photoManager(int $specimenId): void
    {
        Auth::requireLogin();

        $specimen = Specimen::find($specimenId);
        if (!$specimen) {
            flash('error', 'Specimen not found.');
            redirect('/admin/specimens');
        }

        view('admin/specimens/photos', [
            'specimen' => $specimen,
        ], 'admin');
    }

    public static function photoUpload(int $specimenId): void
    {
        Auth::requireLogin();

        $specimen = Database::fetch('SELECT id FROM specimens WHERE id = ?', [$specimenId]);
        if (!$specimen) {
            http_response_code(404);
            echo json_encode(['error' => 'Specimen not found']);
            return;
        }

        $uploaded = [];
        $files = $_FILES['photos'] ?? null;

        if (!$files) {
            flash('error', 'No files selected.');
            redirect("/admin/specimens/{$specimenId}/photos");
        }

        // Normalize single/multiple file uploads
        if (!is_array($files['name'])) {
            $files = [
                'name'     => [$files['name']],
                'type'     => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error'    => [$files['error']],
                'size'     => [$files['size']],
            ];
        }

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            $result = processUpload($file, $specimenId);
            if ($result) {
                Photo::create($specimenId, $result);
                $uploaded[] = $result['filename'];
            }
        }

        $uploadCount = count($uploaded);
        if ($uploadCount > 0) {
            flash('success', "{$uploadCount} photo(s) uploaded successfully.");
        } else {
            flash('error', 'No photos were uploaded. Check file types and sizes.');
        }

        redirect("/admin/specimens/{$specimenId}/photos");
    }

    public static function photoSetPrimary(int $photoId): void
    {
        Auth::requireLogin();

        $photo = Photo::find($photoId);
        if ($photo) {
            Photo::setPrimary($photoId, $photo['specimen_id']);
            flash('success', 'Primary photo updated.');
            redirect("/admin/specimens/{$photo['specimen_id']}/photos");
        }

        redirect('/admin/specimens');
    }

    public static function photoUpdateCaption(int $photoId): void
    {
        Auth::requireLogin();

        $photo = Photo::find($photoId);
        if ($photo) {
            Photo::updateCaption($photoId, trim($_POST['caption'] ?? ''));
            flash('success', 'Caption updated.');
            redirect("/admin/specimens/{$photo['specimen_id']}/photos");
        }

        redirect('/admin/specimens');
    }

    public static function photoDelete(int $photoId): void
    {
        Auth::requireLogin();

        $photo = Photo::find($photoId);
        if ($photo) {
            $specimenId = $photo['specimen_id'];
            Photo::delete($photoId);
            flash('success', 'Photo deleted.');
            redirect("/admin/specimens/{$specimenId}/photos");
        }

        redirect('/admin/specimens');
    }

    public static function photoReorder(): void
    {
        Auth::requireLogin();

        $order = $_POST['order'] ?? [];
        if (is_array($order)) {
            Photo::updateSortOrder($order);
        }

        // AJAX response
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // ============================================
    // Custom Fields
    // ============================================

    public static function fieldList(): void
    {
        Auth::requireLogin();

        $fields = CustomField::all(false); // Include inactive

        view('admin/fields/list', [
            'fields' => $fields,
        ], 'admin');
    }

    public static function fieldForm(?int $id = null): void
    {
        Auth::requireLogin();

        $field = null;
        if ($id) {
            $field = CustomField::find($id);
            if (!$field) {
                flash('error', 'Field not found.');
                redirect('/admin/fields');
            }
        }

        view('admin/fields/form', [
            'field' => $field,
        ], 'admin');
    }

    public static function fieldSave(?int $id = null): void
    {
        Auth::requireLogin();

        if (!Auth::verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect($id ? "/admin/fields/{$id}/edit" : '/admin/fields/create');
        }

        $data = [
            'label'             => trim($_POST['label'] ?? ''),
            'field_type'        => $_POST['field_type'] ?? 'text',
            'is_required'       => isset($_POST['is_required']) ? 1 : 0,
            'is_filterable'     => isset($_POST['is_filterable']) ? 1 : 0,
            'is_visible_public' => isset($_POST['is_visible_public']) ? 1 : 0,
            'sort_order'        => (int)($_POST['sort_order'] ?? 0),
        ];

        // Handle options for select/multi_select
        $optionsRaw = trim($_POST['options'] ?? '');
        if (!empty($optionsRaw) && in_array($data['field_type'], ['select', 'multi_select'])) {
            $data['options'] = array_filter(array_map('trim', explode("\n", $optionsRaw)));
        }

        if (empty($data['label'])) {
            flash('error', 'Label is required.');
            redirect($id ? "/admin/fields/{$id}/edit" : '/admin/fields/create');
        }

        if ($id) {
            CustomField::update($id, $data);
        } else {
            $id = CustomField::create($data);
        }

        flash('success', 'Field saved successfully.');
        redirect('/admin/fields');
    }

    public static function fieldToggle(int $id): void
    {
        Auth::requireLogin();

        $field = CustomField::find($id);
        if ($field) {
            if ($field['is_active']) {
                CustomField::deactivate($id);
                flash('success', "Field \"{$field['label']}\" deactivated. Data is preserved.");
            } else {
                CustomField::activate($id);
                flash('success', "Field \"{$field['label']}\" reactivated.");
            }
        }

        redirect('/admin/fields');
    }

    public static function fieldReorder(): void
    {
        Auth::requireLogin();

        $order = $_POST['order'] ?? [];
        if (is_array($order)) {
            CustomField::updateSortOrder($order);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // ============================================
    // Initial Setup (one-time admin account creation)
    // ============================================

    public static function setup(): void
    {
        // Only allow if no users exist
        $userCount = Database::fetch('SELECT COUNT(*) as c FROM users')['c'];
        if ($userCount > 0) {
            redirect('/admin/login');
        }

        view('admin/setup');
    }

    public static function setupSave(): void
    {
        $userCount = Database::fetch('SELECT COUNT(*) as c FROM users')['c'];
        if ($userCount > 0) {
            redirect('/admin/login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (empty($username) || empty($password)) {
            flash('error', 'Username and password are required.');
            redirect('/admin/setup');
        }

        if ($password !== $confirm) {
            flash('error', 'Passwords do not match.');
            redirect('/admin/setup');
        }

        if (strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            redirect('/admin/setup');
        }

        Database::query(
            'INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)',
            [$username, password_hash($password, PASSWORD_BCRYPT), 'admin']
        );

        flash('success', 'Admin account created! Please log in.');
        redirect('/admin/login');
    }
}
