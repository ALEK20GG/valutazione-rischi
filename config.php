<?php
// config.php
declare(strict_types=1);

// Database configuration per Render
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'mydb';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'abcxyz';
$db_port = getenv('DB_PORT') ?: 3306;

// Token segreto per identificare il kiosk tramite cookie
if (!defined('KIOSK_TOKEN')) {
    define('KIOSK_TOKEN', getenv('KIOSK_TOKEN') ?: 'default-kiosk-token');
}

// Nome del cookie per identificare il kiosk
if (!defined('KIOSK_COOKIE_NAME')) {
    define('KIOSK_COOKIE_NAME', 'kiosk_token');
}

// Password di setup per la pagina setup_kikos.php
// Puoi sovrascriverla impostando la variabile d'ambiente SETUP_PASSWORD su Render
if (!defined('SETUP_PASSWORD')) {
    define('SETUP_PASSWORD', getenv('SETUP_PASSWORD') ?: 'changeme-setup');
}

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $db_host = getenv('DB_HOST') ?: '127.0.0.1';
        $db_name = getenv('DB_NAME') ?: 'mydb';
        $db_user = getenv('DB_USER') ?: 'root';
        $db_pass = getenv('DB_PASS') ?: 'abcxyz';
        $db_port = getenv('DB_PORT') ?: 3306;
        
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}