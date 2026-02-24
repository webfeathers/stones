<?php
/**
 * Public Controller
 *
 * Handles the public-facing gallery, search, and detail pages.
 */

class PublicController
{
    /**
     * Gallery listing — grid of specimens with thumbnails
     */
    public static function gallery(): void
    {
        $config = require __DIR__ . '/../config.php';
        $page = currentPage();
        $perPage = $config['per_page'];

        // Check for active filters
        $filters = [];
        $filterableFields = CustomField::filterable();

        foreach ($filterableFields as $field) {
            $paramKey = 'filter_' . $field['id'];
            if (!empty($_GET[$paramKey])) {
                $val = $_GET[$paramKey];
                // Multi-select comes as comma-separated
                if ($field['field_type'] === 'multi_select') {
                    $val = explode(',', $val);
                }
                $filters[(int)$field['id']] = $val;
            }
        }

        if (!empty($filters)) {
            $result = Specimen::filter($filters, $page, $perPage);
        } else {
            $result = Specimen::paginate($page, $perPage, true);
        }

        view('public/gallery', [
            'specimens'        => $result['items'],
            'total'            => $result['total'],
            'page'             => $page,
            'perPage'          => $perPage,
            'filterableFields' => $filterableFields,
            'activeFilters'    => $filters,
        ], 'public');
    }

    /**
     * Search results
     */
    public static function search(): void
    {
        $config = require __DIR__ . '/../config.php';
        $query = trim($_GET['q'] ?? '');
        $page = currentPage();
        $perPage = $config['per_page'];

        $result = ['items' => [], 'total' => 0];
        if (!empty($query)) {
            $result = Specimen::search($query, $page, $perPage);
        }

        view('public/search', [
            'query'     => $query,
            'specimens' => $result['items'],
            'total'     => $result['total'],
            'page'      => $page,
            'perPage'   => $perPage,
        ], 'public');
    }

    /**
     * Specimen detail page
     */
    public static function detail(string $slug): void
    {
        $specimen = Specimen::findBySlug($slug);

        if (!$specimen) {
            http_response_code(404);
            view('public/404', [], 'public');
            return;
        }

        // Get only publicly visible fields that have values
        $visibleFields = array_filter($specimen['fields'], function ($f) {
            return $f['is_visible_public'] && !empty($f['value']);
        });

        view('public/detail', [
            'specimen'      => $specimen,
            'visibleFields' => $visibleFields,
        ], 'public');
    }
}
