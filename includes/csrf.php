<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

/**
 * Generate or return a CSRF token from the session.
 */
function generateCsrfToken(): string
{
    initializeSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF field for HTML forms.
 */
function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Validate the CSRF token from POST requests.
 */
function verifyCsrfToken(): bool
{
    initializeSession();

    $tokenFromRequest = $_POST['csrf_token'] ?? '';
    $tokenFromSession = $_SESSION['csrf_token'] ?? '';

    if ($tokenFromSession === '' || $tokenFromRequest === '') {
        return false;
    }

    return hash_equals($tokenFromSession, $tokenFromRequest);
}

/**
 * Stop processing when CSRF fails.
 */
function requireCsrfToken(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken()) {
        setFlash('error', 'Invalid or expired CSRF token. Please try again.');
        redirect(BASE_URL . '/login.php');
    }
}
