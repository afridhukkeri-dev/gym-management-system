<?php
/**
 * Application configuration for Gym Management System
 * Phase 1: Foundation setup only.
 */

define('APP_NAME', 'Gym Management System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development');

define('BASE_URL', 'http://localhost/your-project');
define('APP_TIMEZONE', 'Asia/Kolkata');

define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2MB

define('UPLOAD_PROFILE_DIR', __DIR__ . '/../uploads/profiles');
define('UPLOAD_PROFILE_URL', BASE_URL . '/uploads/profiles');

define('DB_NAME', 'gym_management');

define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

define('DB_USERNAME', 'your_database_username');
define('DB_PASSWORD', 'your_database_password');

define('SESSION_NAME', 'gym_management_session');
define('SESSION_LIFETIME', 1800);
define('SESSION_TIMEOUT', 1800);
define('SESSION_REGENERATE_INTERVAL', 300);

define('DEBUG_MODE', true);

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(APP_TIMEZONE);
}
