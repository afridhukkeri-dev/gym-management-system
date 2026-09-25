<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Initialize session with secure cookie settings for local HTTP and HTTPS.
 */
function initializeSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Check whether the current request has a valid authenticated user session.
 */
function isLoggedIn(): bool
{
    initializeSession();

    if (empty($_SESSION['user'])) {
        return false;
    }

    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }

    if ((time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        logoutUser();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Redirect users to login if they are not authenticated.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Your session has expired. Please log in again.');
        redirect(BASE_URL . '/login.php');
    }
}

/**
 * Restrict access based on role.
 *
 * Accepts a single role or an array of allowed roles.
 */
function requireRole(string|array $allowedRoles): void
{
    requireLogin();

    $user = currentUser();
    if (!$user) {
        redirect(BASE_URL . '/login.php');
    }

    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    if (!in_array($user['role'], $roles, true)) {
        setFlash('error', 'You do not have permission to access this page.');
        redirect(BASE_URL . '/login.php');
    }
}

/**
 * Return the currently authenticated user from the session.
 */
function currentUser(): ?array
{
    initializeSession();

    if (empty($_SESSION['user'])) {
        return null;
    }

    return $_SESSION['user'];
}

/**
 * Store the necessary user data in the session after successful login.
 */
function loginUser(array $user): void
{
    initializeSession();
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
        'status' => (string) $user['status'],
    ];

    $_SESSION['last_activity'] = time();
}

/**
 * Safely destroy the current session and redirect to login.
 */
function logoutUser(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        initializeSession();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
