<?php
// logout.php
require_once __DIR__ . '/functions.php';

$pdo = getPDO();

// Si permette logout via sessione oppure via sendBeacon (uid inviato)
$kiosk = isKiosk() ? 1 : 0;
$uid = null;

// Priorità: sessione attiva
if (!empty($_SESSION['uid'])) {
    $uid = (int)$_SESSION['uid'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uid'])) {
    $uid = (int)$_POST['uid']; // chiamata da sendBeacon potrebbe includere uid
} else {
    // prova JSON body per sendBeacon
    $body = file_get_contents('php://input');
    if ($body) {
        $data = json_decode($body, true);
        if (isset($data['uid'])) $uid = (int)$data['uid'];
    }
}

if ($uid !== null) {
    // Inserisci log logout
    insertLog($uid, 0, $kiosk);
    // Aggiorna online flag solo se era online
    $stmt = $pdo->prepare("UPDATE t_user SET online = 0, last_heartbeat = NULL WHERE uid = :uid");
    $stmt->execute([':uid' => $uid]);

    // Se logout via sessione, distruggi sessione
    if (!empty($_SESSION['uid']) && (int)$_SESSION['uid'] === $uid) {
        hLogoutCleanup();
    }

    // Risposta semplice
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// Se arrivi qui: errore
http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'uid non trovato']);
