<?php
// setup_kiosk.php
// IMPORTANTE: Questa pagina va usata UNA SOLA VOLTA sulla postazione kiosk
// Poi va protetta o rimossa per sicurezza
require_once __DIR__ . '/functions.php';

$message = '';
$is_already_kiosk = isKiosk();
$cookie_debug = isset($_COOKIE[KIOSK_COOKIE_NAME]) ? $_COOKIE[KIOSK_COOKIE_NAME] : 'NON PRESENTE';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if ($password !== SETUP_PASSWORD) {
        $message = '<p style="color:red">Password di setup non valida.</p>';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'enable') {
            $result = setKioskCookie();
            if ($result) {
                $message = '<p style="color:green"><strong>✓ Cookie kiosk impostato con successo!</strong><br>Questa postazione è ora riconosciuta come kiosk.<br><em>Aggiorna la pagina per verificare.</em></p>';
                $is_already_kiosk = true;
                $cookie_debug = KIOSK_TOKEN;
            } else {
                $message = '<p style="color:red">Errore nell\'impostazione del cookie. Verifica le impostazioni del browser.</p>';
            }
        } elseif ($action === 'disable') {
            removeKioskCookie();
            $message = '<p style="color:orange">Cookie kiosk rimosso! Questa postazione non è più riconosciuta come kiosk.<br><em>Aggiorna la pagina per verificare.</em></p>';
            $is_already_kiosk = false;
            $cookie_debug = 'RIMOSSO';
        }
    }
}
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Setup Postazione Kiosk</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .status { padding: 15px; border-radius: 5px; margin: 20px 0; }
        .status.active { background: #d4edda; border: 1px solid #c3e6cb; }
        .status.inactive { background: #f8d7da; border: 1px solid #f5c6cb; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; font-size: 14px; }
        .btn-enable { background: #28a745; color: white; border: none; }
        .btn-disable { background: #dc3545; color: white; border: none; }
        .btn-refresh { background: #007bff; color: white; border: none; }
        input[type="password"] { padding: 8px; margin: 10px 0; width: 200px; }
        .warning { background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px; margin: 20px 0; }
        .debug { background: #e9ecef; padding: 10px; border-radius: 5px; margin: 20px 0; font-family: monospace; font-size: 12px; }
        .links { margin: 30px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .links a { display: block; margin: 5px 0; }
    </style>
</head>
<body>
    <h1>🔧 Setup Postazione Kiosk</h1>
    
    <div class="warning">
        <strong>⚠️ ATTENZIONE:</strong> Questa pagina va usata solo per configurare la postazione kiosk dedicata.
        Dopo la configurazione, proteggi o rimuovi questa pagina per sicurezza.
    </div>
    
    <div class="status <?= $is_already_kiosk ? 'active' : 'inactive' ?>">
        <strong>Stato attuale:</strong> 
        <?= $is_already_kiosk ? '✓ Questa postazione è riconosciuta come KIOSK' : '✗ Questa postazione NON è riconosciuta come kiosk' ?>
    </div>
    
    <?= $message ?>
    
    <div class="debug">
        <strong>Debug Info:</strong><br>
        Cookie Name: <?= KIOSK_COOKIE_NAME ?><br>
        Cookie Value: <?= htmlspecialchars($cookie_debug) ?><br>
        Expected Token: <?= substr(KIOSK_TOKEN, 0, 20) ?>...<br>
        isKiosk(): <?= isKiosk() ? 'TRUE' : 'FALSE' ?>
    </div>
    
    <h3>Configurazione</h3>
    <p>Inserisci la password di setup per modificare lo stato del kiosk:</p>
    
    <form method="post" style="margin: 20px 0;">
        <input type="password" name="password" placeholder="Password di setup" required>
        <br>
        <?php if (!$is_already_kiosk): ?>
            <button type="submit" name="action" value="enable" class="btn-enable">
                ✓ Abilita come Kiosk
            </button>
        <?php else: ?>
            <button type="submit" name="action" value="disable" class="btn-disable">
                ✗ Disabilita Kiosk
            </button>
        <?php endif; ?>
    </form>
    
    <button onclick="location.reload()" class="btn-refresh">🔄 Aggiorna Pagina</button>
    
    <div class="links">
        <h4>Link di Test:</h4>
        <a href="index.php">← Torna alla home</a>
        <a href="register.php" target="_blank">🔗 Vai a Register (apre in nuova scheda)</a>
        <a href="login.php" target="_blank">🔗 Vai a Login (apre in nuova scheda)</a>
    </div>
    
    <hr>
    <p><small>
        Password di setup: <code><?= SETUP_PASSWORD ?></code><br>
        (definita nella variabile d'ambiente SETUP_PASSWORD o in config.php)
    </small></p>
</body>
</html>