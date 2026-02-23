<?php
// config.php
declare(strict_types=1);

// Database configuration (PostgreSQL: Supabase / Render / altri)
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'mydb';
$db_user = getenv('DB_USER') ?: 'postgres';
$db_pass = getenv('DB_PASS') ?: 'postgres';
$db_port = getenv('DB_PORT') ?: 5432;

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
        $db_user = getenv('DB_USER') ?: 'postgres';
        $db_pass = getenv('DB_PASS') ?: 'postgres';
        $db_port = getenv('DB_PORT') ?: 5432;
        
        // DSN per PostgreSQL (Supabase richiede SSL)
        $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}