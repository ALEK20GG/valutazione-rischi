<?php
// config.php

declare(strict_types=1);

// Con MySQL nel container Docker
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'mydb');
define('DB_USER', 'root');
define('DB_PASS', 'abcxyz');

// Token segreto per identificare il kiosk tramite cookie
// IMPORTANTE: Cambia questo valore con uno casuale e sicuro!
// Oppure usa una variabile d'ambiente KIOSK_TOKEN
if (!defined('KIOSK_TOKEN')) {
    define('KIOSK_TOKEN', getenv('KIOSK_TOKEN') ?: 'aaaaaa');
}

// Password per la pagina di setup (opzionale ma consigliato)
if (!defined('SETUP_PASSWORD')) {
    define('SETUP_PASSWORD', getenv('SETUP_PASSWORD') ?: 'setup123');
}

// Nome del cookie per identificare il kiosk
if (!defined('KIOSK_COOKIE_NAME')) {
    define('KIOSK_COOKIE_NAME', 'kiosk_token');
}

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}