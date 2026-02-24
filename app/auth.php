<?php
/**
 * Authentication Helper
 *
 * Session-based auth with bcrypt passwords.
 */

class Auth
{
    /**
     * Start session with secure settings
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/config.php';
            session_name($config['session']['name']);
            session_set_cookie_params([
                'lifetime' => $config['session']['lifetime'],
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly'  => true,
                'samesite'  => 'Lax',
            ]);
            session_start();
        }
    }

    /**
     * Attempt to log in a user
     */
    public static function login(string $username, string $password): bool
    {
        $user = Database::fetch(
            'SELECT id, username, password_hash, role FROM users WHERE username = ?',
            [$username]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            session_regenerate_id(true);
            return true;
        }

        return false;
    }

    /**
     * Log out the current user
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user info
     */
    public static function user(): ?array
    {
        if (!self::check()) return null;
        return [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role'     => $_SESSION['role'],
        ];
    }

    /**
     * Require authentication — redirect to login if not authenticated
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /admin/login');
            exit;
        }
    }

    /**
     * Generate a CSRF token
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Verify CSRF token from form submission
     */
    public static function verifyCsrf(): bool
    {
        $token = $_POST['_csrf_token'] ?? '';
        return hash_equals($_SESSION['_csrf_token'] ?? '', $token);
    }

    /**
     * Output a hidden CSRF input field
     */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(self::csrfToken()) . '">';
    }
}
