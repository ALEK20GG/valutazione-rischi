<?php
// dashboard.php
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$uid = (int)$_SESSION['uid'];
$username = $_SESSION['username'] ?? 'utente';
$is_kiosk = isKiosk() ? 1 : 0;

// Recupera statistiche accessi
$pdo = getPDO();

// Conta accessi totali dell'utente
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM t_log WHERE uid = :uid AND op_type = 1");
$stmt->execute([':uid' => $uid]);
$total_accessi = $stmt->fetch()['total'];

// Conta accessi dalla postazione kiosk
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM t_log WHERE uid = :uid AND op_type = 1 AND is_kiosk = 1");
$stmt->execute([':uid' => $uid]);
$accessi_kiosk = $stmt->fetch()['total'];

// Conta accessi da altre postazioni
$accessi_altre = $total_accessi - $accessi_kiosk;

// Recupera tutti gli accessi (ultimi 50)
$stmt = $pdo->prepare("
    SELECT log_id, op_type, timestamp, is_kiosk 
    FROM t_log 
    WHERE uid = :uid 
    ORDER BY timestamp DESC 
    LIMIT 50
");
$stmt->execute([':uid' => $uid]);
$logs = $stmt->fetchAll();
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin: 24px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-card.kiosk {
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        }
        .stat-card.other {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 8px 0;
        }
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .log-table thead {
            background: #f9fafb;
        }
        .log-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }
        .log-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        .log-table tr:hover {
            background: #f9fafb;
        }
        .log-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .log-badge.login {
            background: #dcfce7;
            color: #166534;
        }
        .log-badge.logout {
            background: #fee2e2;
            color: #991b1b;
        }
        .log-badge.kiosk {
            background: #dbeafe;
            color: #1e40af;
        }
        .log-badge.other {
            background: #f3f4f6;
            color: #4b5563;
        }
        .download-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 16px;
            transition: all 0.2s;
        }
        .download-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 24px 0 16px 0;
            color: #1f2937;
        }
        .container-large {
            max-width: 900px;
        }
    </style>
</head>
<body>
    <div class="container container-large">
        <div class="header">
            <h1>👋 Benvenuto, <?= htmlspecialchars($username) ?>!</h1>
            <p>Dashboard personale</p>
            <?php if ($is_kiosk): ?>
                <span class="badge kiosk">📍 Postazione Kiosk</span>
            <?php else: ?>
                <span class="badge normal">💻 Postazione Standard</span>
            <?php endif; ?>
        </div>

        <div class="section-title">📊 Statistiche Accessi</div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Totale Accessi</div>
                <div class="stat-number"><?= $total_accessi ?></div>
            </div>
            <div class="stat-card kiosk">
                <div class="stat-label">Da Kiosk</div>
                <div class="stat-number"><?= $accessi_kiosk ?></div>
            </div>
            <div class="stat-card other">
                <div class="stat-label">Da Altre Postazioni</div>
                <div class="stat-number"><?= $accessi_altre ?></div>
            </div>
        </div>

        <div class="section-title">📋 Storico Accessi (ultimi 50)</div>
        
        <a href="download_log.php" class="download-btn">
            📥 Scarica Log Completo (CSV)
        </a>

        <?php if (empty($logs)): ?>
            <div class="alert alert-warning">
                Nessun accesso registrato ancora.
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Data e Ora</th>
                            <th>Tipo</th>
                            <th>Postazione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i:s', strtotime($log['timestamp'])) ?></td>
                                <td>
                                    <span class="log-badge <?= $log['op_type'] == 1 ? 'login' : 'logout' ?>">
                                        <?= $log['op_type'] == 1 ? '🔓 Login' : '🔒 Logout' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="log-badge <?= $log['is_kiosk'] == 1 ? 'kiosk' : 'other' ?>">
                                        <?= $log['is_kiosk'] == 1 ? '📍 Kiosk' : '💻 Altra' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form method="post" action="logout.php" style="margin-top: 24px;">
            <button type="submit" class="btn btn-danger">
                🚪 Logout
            </button>
        </form>

        <div class="links">
            <a href="index.php">← Torna alla home</a>
        </div>
    </div>

    <?php if ($is_kiosk): ?>
        <script src="assets/kiosk.js"></script>
        <script>
            Kiosk.init({ 
                uid: <?= json_encode($uid) ?>, 
                heartbeatInterval: 15000, 
                logoutUrl: 'index.php', 
                heartbeatUrl: 'heartbeat.php' 
            });
        </script>
    <?php endif; ?>
</body>
</html>