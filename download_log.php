<?php
// download_log.php - Permette il download del log personale in CSV
require_once __DIR__ . '/functions.php';

// Verifica che l'utente sia loggato
if (empty($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$uid = (int)$_SESSION['uid'];
$username = $_SESSION['username'] ?? 'utente';

// Recupera tutti i log dell'utente
$pdo = getPDO();
$stmt = $pdo->prepare("
    SELECT log_id, op_type, timestamp, is_kiosk 
    FROM t_log 
    WHERE uid = :uid 
    ORDER BY timestamp DESC
");
$stmt->execute([':uid' => $uid]);
$logs = $stmt->fetchAll();

// Imposta headers per il download CSV
$filename = 'log_accessi_' . $username . '_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Crea output stream
$output = fopen('php://output', 'w');

// Aggiungi BOM UTF-8 per compatibilità Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header CSV
fputcsv($output, ['ID', 'Data e Ora', 'Tipo Operazione', 'Postazione'], ';');

// Dati
foreach ($logs as $log) {
    $op_type = $log['op_type'] == 1 ? 'Login' : 'Logout';
    $postazione = $log['is_kiosk'] == 1 ? 'Kiosk' : 'Altra Postazione';
    
    fputcsv($output, [
        $log['log_id'],
        date('d/m/Y H:i:s', strtotime($log['timestamp'])),
        $op_type,
        $postazione
    ], ';');
}

fclose($output);
exit;