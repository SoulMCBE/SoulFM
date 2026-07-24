<?php
/**
 * SoulFM - Configuration File
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'soulmc_soulfm');
define('DB_USER', 'soulmc_soulfm');
define('DB_PASS', 'JxB2kGrYtHdbqCRq6S9R');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'SoulFM');
define('APP_VERSION', '1.0.0');

// Determine base URL automatically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = '';

// Find the base path (handle subdirectory installations)
if (strpos($script, '/admin/') !== false) {
    $basePath = substr($script, 0, strpos($script, '/admin/'));
} elseif (strpos($script, '/api/') !== false) {
    $basePath = substr($script, 0, strpos($script, '/api/'));
}

define('BASE_URL', $protocol . '://' . $host . $basePath);
define('ADMIN_URL', BASE_URL . '/admin');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Europe/Amsterdam');

// Rate limiting for requests (minutes)
define('REQUEST_RATE_LIMIT', 15);

// =====================================================
// Encryptie voor bedrijfsmail wachtwoorden
// BELANGRIJK: Verander MAIL_CRYPT_KEY vóór productie!
// Genereer een veilige sleutel: php -r "echo bin2hex(random_bytes(32));"
// =====================================================
if (!defined('MAIL_CRYPT_KEY')) {
    // Probeer sleutel uit een los bestand te laden (buiten webroot aanbevolen)
    $keyFile = __DIR__ . '/../.mail_key';
    if (file_exists($keyFile)) {
        define('MAIL_CRYPT_KEY', trim(file_get_contents($keyFile)));
    } else {
        // Fallback: genereer eenmalig en sla op (setup.php doet dit beter)
        define('MAIL_CRYPT_KEY', 'SoulFM_Change_This_Key_In_Production_32chars!');
    }
}
define('MAIL_CRYPT_CIPHER', 'AES-256-CBC');
