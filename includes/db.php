<?php
/**
 * SoulFM - Database Connection
 */

require_once __DIR__ . '/config.php';

$_pdo_instance = null;

/**
 * Get PDO database instance (singleton)
 */
function getPDO(): PDO {
    global $_pdo_instance;
    
    if ($_pdo_instance === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $_pdo_instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error, show friendly message
            error_log('Database connection failed: ' . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:2rem;background:#fee;border:1px solid #f00;margin:2rem;border-radius:8px;">
                <h2>Database verbinding mislukt</h2>
                <p>Controleer de database instellingen in includes/config.php</p>
                <p><small>' . htmlspecialchars($e->getMessage()) . '</small></p>
            </div>');
        }
    }
    
    return $_pdo_instance;
}
