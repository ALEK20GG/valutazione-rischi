<?php
// heartbeat.php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo 'Method not allowed'; exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$uid = $input['uid'] ?? $_POST['uid'] ?? null;
if (!$uid) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'uid mancante']);
    exit;
}

$uid = (int)$uid;
$pdo = getPDO();
$stmt = $pdo->prepare("UPDATE t_user SET last_heartbeat = NOW() WHERE uid = :uid");
$stmt->execute([':uid' => $uid]);

echo json_encode(['ok' => true]);
