<?php
// index.php
require_once __DIR__ . '/functions.php';

// SE SESSIONE ATTIVA → vai direttamente alla dashboard
if (!empty($_SESSION['uid'])) {
    header('Location: dashboard.php');
    exit;
}

$isKiosk = isKiosk();
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor di Log - Home</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖥️ Monitor di Log</h1>
            <p>Sistema di gestione accessi e monitoraggio</p>
            <?php if ($isKiosk): ?>
                <span class="badge kiosk">📍 Postazione Kiosk</span>
            <?php else: ?>
                <span class="badge normal">💻 Postazione Standard</span>
            <?php endif; ?>
        </div>

        <p style="text-align: center; color: #6b7280; margin-bottom: 24px;">
            Benvenuto! Effettua il login o registrati per accedere al sistema.
        </p>

        <ul class="menu">
            <li>
                <a href="login.php">
                    🔐 Login
                </a>
            </li>

            <?php if ($isKiosk): ?>
                <li>
                    <a href="register.php">
                        ✏️ Registrati
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <em>📍 La registrazione è disponibile solo dalla postazione kiosk dedicata</em>
                </li>
            <?php endif; ?>
        </ul>

    </div>
</body>
</html>