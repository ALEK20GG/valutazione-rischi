<?php
// history.php
require_once __DIR__ . '/functions.php';

requireAuth();

$evaluations = [];
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        "SELECT * FROM t_niosh_evaluations 
         WHERE uid = :uid 
         ORDER BY created_at DESC"
    );
    $stmt->execute([':uid' => getCurrentUid()]);
    $evaluations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("History query error: " . $e->getMessage());
}

/**
 * Determina il livello di rischio da LI
 */
function getLiRiskLevel($li) {
    if ($li <= 1.0) return 'basso';
    elseif ($li <= 2.0) return 'moderato';
    elseif ($li <= 3.0) return 'elevato';
    else return 'molto_elevato';
}
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storico Valutazioni - NIOSH Calculator</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Storico Valutazioni</h1>
            <p>Tutte le valutazioni NIOSH effettuate</p>
            <div style="text-align: right; margin-top: 12px;">
                <span style="color: #6b7280;">Utente: <strong><?= htmlspecialchars(getCurrentUsername()) ?></strong></span>
                <span style="margin: 0 8px; color: #d1d5db;">•</span>
                <a href="niosh_form.php" style="color: #3b82f6; text-decoration: none;">➕ Nuova Valutazione</a>
                <span style="margin: 0 8px; color: #d1d5db;">•</span>
                <a href="logout.php" style="color: #ef4444; text-decoration: none;">🚪 Logout</a>
            </div>
        </div>

        <?php if (empty($evaluations)): ?>
            <div style="text-align: center; padding: 48px 0; color: #6b7280;">
                <p style="font-size: 18px; margin-bottom: 12px;">Non hai ancora effettuato valutazioni.</p>
                <a href="niosh_form.php" class="btn btn-primary">➕ Crea la Prima Valutazione</a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Nome Valutazione</th>
                            <th>Peso (kg)</th>
                            <th>RWL (kg)</th>
                            <th>LI</th>
                            <th>Rischio</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $eval): ?>
                            <tr class="eval-row-<?= getLiRiskLevel($eval['li']) ?>">
                                <td style="font-weight: 500;"><?= htmlspecialchars($eval['eval_name']) ?></td>
                                <td><?= number_format($eval['weight'], 2, ',', '.') ?></td>
                                <td><?= number_format($eval['rwl'], 2, ',', '.') ?></td>
                                <td>
                                    <strong><?= number_format($eval['li'], 2, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $risk_level = getLiRiskLevel($eval['li']);
                                    $risk_icons = [
                                        'basso' => '✅',
                                        'moderato' => '⚠️',
                                        'elevato' => '🔴',
                                        'molto_elevato' => '🚨'
                                    ];
                                    $risk_labels = [
                                        'basso' => 'Basso',
                                        'moderato' => 'Moderato',
                                        'elevato' => 'Elevato',
                                        'molto_elevato' => 'Molto Elevato'
                                    ];
                                    echo $risk_icons[$risk_level] . ' ' . $risk_labels[$risk_level];
                                    ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($eval['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 24px; padding: 16px; background: #f3f4f6; border-radius: 8px; color: #6b7280;">
                <p><strong>Legenda LI (Lift Index):</strong></p>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    <li>✅ <strong>LI ≤ 1.0</strong>: Carico sicuro per la popolazione</li>
                    <li>⚠️ <strong>1.0 < LI ≤ 2.0</strong>: Alcuni lavoratori potrebbero essere a rischio</li>
                    <li>🔴 <strong>2.0 < LI ≤ 3.0</strong>: Molti lavoratori potrebbero essere a rischio</li>
                    <li>🚨 <strong>LI > 3.0</strong>: Implementare misure di controllo</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
