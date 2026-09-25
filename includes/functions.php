<?php
/**
 * Basic reusable helpers for a Core PHP application.
 * Phase 1 only: foundation helpers, no authentication logic.
 */

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function old(string $key, mixed $default = ''): string
{
    if (isset($_POST[$key])) {
        return e($_POST[$key]);
    }

    if (isset($_GET[$key])) {
        return e($_GET[$key]);
    }

    return e($default);
}

function setFlash(string $key, string $message): void
{
    if (!isset($_SESSION)) {
        session_start();
    }

    $_SESSION['flash'][$key] = $message;
}

function getFlash(string $key): ?string
{
    if (!isset($_SESSION)) {
        session_start();
    }

    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }

    return null;
}

function sanitizeString(string $value): string
{
    return trim(strip_tags($value));
}

function sanitizeEmail(string $email): string
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isPositiveNumber(mixed $value): bool
{
    if (!is_numeric($value)) {
        return false;
    }

    return (float) $value > 0;
}

function formatDateTime(?string $value): string
{
    if (empty($value)) {
        return 'N/A';
    }

    $date = new DateTime($value, new DateTimeZone(APP_TIMEZONE ?? 'UTC'));
    return $date->format('d M Y, h:i A');
}
