<?php
// cleanup.php - da lanciare via cron (php /path/cleanup.php)
require_once __DIR__ . '/functions.php';

// In modalità senza database non fa nulla
if (defined('DB_DISABLED') && DB_DISABLED) {
    exit(0);
}

$pdo = getPDO();

// soglia in secondi
$threshold = 30;

$stmt = $pdo->prepare("SELECT uid FROM t_user WHERE online = 1 AND last_heartbeat < (NOW() - INTERVAL :seconds SECOND)");
$stmt->execute([':seconds' => $threshold]);
$rows = $stmt->fetchAll();

foreach ($rows as $r) {
    $uid = (int)$r['uid'];
    insertLog($uid, 0, 1); // logout forzato kiosk
    $u2 = $pdo->prepare("UPDATE t_user SET online = 0, last_heartbeat = NULL WHERE uid = :uid");
    $u2->execute([':uid' => $uid]);
}
