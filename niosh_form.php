<?php
// niosh_form.php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/niosh_calculator.php';

requireAuth();

$result = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione input
    $weight = (float)($_POST['weight'] ?? 0);
    $horizontal_distance = (float)($_POST['horizontal_distance'] ?? 25);
    $vertical_height = (float)($_POST['vertical_height'] ?? 75);
    $distance_moved = (float)($_POST['distance_moved'] ?? 25);
    $asymmetric_angle = (float)($_POST['asymmetric_angle'] ?? 0);
    $frequency = (float)($_POST['frequency'] ?? 1);
    $duration = $_POST['duration'] ?? 'moderate';
    $grip_quality = $_POST['grip_quality'] ?? 'fair';
    $eval_name = trim($_POST['eval_name'] ?? 'Valutazione NIOSH');

    // Validazione
    if ($weight <= 0 || $weight > 100) {
        $errors[] = "Il peso deve essere tra 0.1 e 100 kg.";
    }
    if ($horizontal_distance < 0 || $horizontal_distance > 80) {
        $errors[] = "La distanza orizzontale deve essere tra 0 e 80 cm.";
    }
    if ($vertical_height < 0 || $vertical_height > 175) {
        $errors[] = "L'altezza verticale deve essere tra 0 e 175 cm.";
    }
    if ($distance_moved <= 0 || $distance_moved > 300) {
        $errors[] = "La distanza di movimento deve essere tra 0.1 e 300 cm.";
    }
    if ($asymmetric_angle < 0 || $asymmetric_angle > 135) {
        $errors[] = "L'angolo asimmetrico deve essere tra 0 e 135 gradi.";
    }
    if ($frequency <= 0 || $frequency > 15) {
        $errors[] = "La frequenza deve essere tra 0.1 e 15 sollevamenti/minuto.";
    }

    if (empty($errors)) {
        // Calcola il risultato
        $calculator = new NIRoshCalculator(
            $weight,
            $horizontal_distance,
            $vertical_height,
            $distance_moved,
            $asymmetric_angle,
            $frequency,
            $duration,
            $grip_quality
        );
        $calculator->calculate();
        $result = $calculator->getResult();
        // Aggiunge il nome valutazione anche all'array risultato (per la vista)
        $result['eval_name'] = $eval_name;

        // Salva nel database
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare(
                "INSERT INTO t_niosh_evaluations 
                 (uid, eval_name, weight, horizontal_distance, vertical_distance, 
                  vertical_height, distance_moved, asymmetric_angle, frequency, 
                  duration, grip_quality, rwl, li)
                 VALUES (:uid, :eval_name, :weight, :horizontal_distance, :vertical_distance,
                         :vertical_height, :distance_moved, :asymmetric_angle, :frequency,
                         :duration, :grip_quality, :rwl, :li)"
            );
            $stmt->execute([
                ':uid' => getCurrentUid(),
                ':eval_name' => $eval_name,
                ':weight' => $weight,
                ':horizontal_distance' => $horizontal_distance,
                ':vertical_distance' => 0,
                ':vertical_height' => $vertical_height,
                ':distance_moved' => $distance_moved,
                ':asymmetric_angle' => $asymmetric_angle,
                ':frequency' => $frequency,
                ':duration' => $duration,
                ':grip_quality' => $grip_quality,
                ':rwl' => $result['rwl'],
                ':li' => $result['li']
            ]);
        } catch (Exception $e) {
            error_log("Database save error: " . $e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIOSH Calculator - Valutazione</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Calcolatore NIOSH</h1>
            <p>Valutazione dei rischi di sollevamento manuale</p>
            <div style="text-align: right; margin-top: 12px;">
                <span style="color: #6b7280;">Utente: <strong><?= htmlspecialchars(getCurrentUsername()) ?></strong></span>
                <span style="margin: 0 8px; color: #d1d5db;">•</span>
                <a href="history.php" style="color: #3b82f6; text-decoration: none;">📊 Storico</a>
                <span style="margin: 0 8px; color: #d1d5db;">•</span>
                <a href="logout.php" style="color: #ef4444; text-decoration: none;">🚪 Logout</a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <div>❌ <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($result === null): ?>
            <!-- FORM DI INPUT -->
            <form method="post" action="niosh_form.php" class="niosh-form">
                <fieldset>
                    <legend>📋 Dati di Sollevamento</legend>

                    <div class="form-group">
                        <label for="eval_name">Nome Valutazione</label>
                        <input type="text" id="eval_name" name="eval_name" 
                               value="<?= htmlspecialchars($_POST['eval_name'] ?? 'Valutazione NIOSH') ?>"
                               placeholder="Es: Movimentazione scatole magazzino">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight">Peso da sollevare (kg) *</label>
                            <input type="number" id="weight" name="weight" step="0.1" min="0.1" max="100" required
                                   value="<?= htmlspecialchars($_POST['weight'] ?? '10') ?>"
                                   placeholder="Es: 10">
                            <small>Intervallo: 0.1 - 100 kg</small>
                        </div>

                        <div class="form-group">
                            <label for="horizontal_distance">Distanza orizzontale (cm) *</label>
                            <input type="number" id="horizontal_distance" name="horizontal_distance" 
                                   step="1" min="0" max="80" required
                                   value="<?= htmlspecialchars($_POST['horizontal_distance'] ?? '25') ?>"
                                   placeholder="Es: 25">
                            <small>Intervallo: 0 - 80 cm (Ideale: 25)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="vertical_height">Altezza verticale del carico dal suolo (cm) *</label>
                            <input type="number" id="vertical_height" name="vertical_height" 
                                   step="1" min="0" max="175" required
                                   value="<?= htmlspecialchars($_POST['vertical_height'] ?? '75') ?>"
                                   placeholder="Es: 75">
                            <small>Intervallo: 0 - 175 cm (Ideale: 75)</small>
                        </div>

                        <div class="form-group">
                            <label for="distance_moved">Distanza verticale di movimento (cm) *</label>
                            <input type="number" id="distance_moved" name="distance_moved" 
                                   step="1" min="0.1" max="300" required
                                   value="<?= htmlspecialchars($_POST['distance_moved'] ?? '25') ?>"
                                   placeholder="Es: 25">
                            <small>Intervallo: 0.1 - 300 cm (Ideale: 25)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="asymmetric_angle">Angolo asimmetrico (gradi) *</label>
                            <input type="number" id="asymmetric_angle" name="asymmetric_angle" 
                                   step="1" min="0" max="135" required
                                   value="<?= htmlspecialchars($_POST['asymmetric_angle'] ?? '0') ?>"
                                   placeholder="Es: 0">
                            <small>Intervallo: 0 - 135° (0° = ottimale)</small>
                        </div>

                        <div class="form-group">
                            <label for="frequency">Frequenza di sollevamento (sollevamenti/min) *</label>
                            <input type="number" id="frequency" name="frequency" 
                                   step="0.1" min="0.1" max="15" required
                                   value="<?= htmlspecialchars($_POST['frequency'] ?? '1') ?>"
                                   placeholder="Es: 1">
                            <small>Intervallo: 0.1 - 15</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration">Durata dell'attività *</label>
                            <select id="duration" name="duration" required>
                                <option value="short" <?= ($_POST['duration'] ?? '') === 'short' ? 'selected' : '' ?>>
                                    ⏱️ Breve (< 1 ora)
                                </option>
                                <option value="moderate" <?= ($_POST['duration'] ?? 'moderate') === 'moderate' ? 'selected' : '' ?>>
                                    ⏱️ Moderata (1-2 ore)
                                </option>
                                <option value="long" <?= ($_POST['duration'] ?? '') === 'long' ? 'selected' : '' ?>>
                                    ⏱️ Lunga (> 2 ore)
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="grip_quality">Qualità della presa *</label>
                            <select id="grip_quality" name="grip_quality" required>
                                <option value="good" <?= ($_POST['grip_quality'] ?? '') === 'good' ? 'selected' : '' ?>>
                                    👍 Buona
                                </option>
                                <option value="fair" <?= ($_POST['grip_quality'] ?? 'fair') === 'fair' ? 'selected' : '' ?>>
                                    👌 Discreta
                                </option>
                                <option value="poor" <?= ($_POST['grip_quality'] ?? '') === 'poor' ? 'selected' : '' ?>>
                                    👎 Scarsa
                                </option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <button type="submit" class="btn btn-primary">📊 Calcola Rischio</button>
            </form>

        <?php else: ?>
            <!-- RISULTATI -->
            <div class="results-container">
                <h2>📊 Risultati della Valutazione</h2>
                <p style="color: #6b7280;">
                    <strong><?= htmlspecialchars($result['eval_name'] ?? 'Valutazione') ?></strong> - 
                    <?= date('d/m/Y H:i') ?>
                </p>

                <!-- Risk Level Badge -->
                <div class="risk-badge risk-<?= $result['risk_level'] ?>">
                    <?php
                    $risk_icons = [
                        'basso' => '✅',
                        'moderato' => '⚠️',
                        'elevato' => '🔴',
                        'molto_elevato' => '🚨'
                    ];
                    $risk_labels = [
                        'basso' => 'RISCHIO BASSO',
                        'moderato' => 'RISCHIO MODERATO',
                        'elevato' => 'RISCHIO ELEVATO',
                        'molto_elevato' => 'RISCHIO MOLTO ELEVATO'
                    ];
                    ?>
                    <?= $risk_icons[$result['risk_level']] ?> 
                    <strong><?= $risk_labels[$result['risk_level']] ?></strong>
                </div>

                <!-- Input Data -->
                <fieldset>
                    <legend>📋 Dati Inseriti</legend>
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="label">Peso:</span>
                            <span class="value"><?= number_format($result['weight'], 2, ',', '.') ?> kg</span>
                        </div>
                        <div class="data-item">
                            <span class="label">Distanza orizzontale:</span>
                            <span class="value"><?= number_format($result['horizontal_distance'], 2, ',', '.') ?> cm</span>
                        </div>
                        <div class="data-item">
                            <span class="label">Altezza verticale:</span>
                            <span class="value"><?= number_format($result['vertical_height'], 2, ',', '.') ?> cm</span>
                        </div>
                        <div class="data-item">
                            <span class="label">Distanza di movimento:</span>
                            <span class="value"><?= number_format($result['distance_moved'], 2, ',', '.') ?> cm</span>
                        </div>
                        <div class="data-item">
                            <span class="label">Angolo asimmetrico:</span>
                            <span class="value"><?= number_format($result['asymmetric_angle'], 2, ',', '.') ?>°</span>
                        </div>
                        <div class="data-item">
                            <span class="label">Frequenza:</span>
                            <span class="value"><?= number_format($result['frequency'], 2, ',', '.') ?> sollevamenti/min</span>
                        </div>
                        <div class="data-item">
                            <span class="label">Durata:</span>
                            <span class="value">
                                <?php
                                $duration_labels = ['short' => 'Breve', 'moderate' => 'Moderata', 'long' => 'Lunga'];
                                echo $duration_labels[$result['duration']] ?? $result['duration'];
                                ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <span class="label">Qualità presa:</span>
                            <span class="value">
                                <?php
                                $grip_labels = ['good' => 'Buona', 'fair' => 'Discreta', 'poor' => 'Scarsa'];
                                echo $grip_labels[$result['grip_quality']] ?? $result['grip_quality'];
                                ?>
                            </span>
                        </div>
                    </div>
                </fieldset>

                <!-- Moltiplicatori -->
                <fieldset>
                    <legend>🔢 Moltiplicatori NIOSH</legend>
                    <div class="data-grid">
                        <div class="data-item">
                            <span class="label">HM (Orizzontale):</span>
                            <span class="value formula"><?= number_format($result['hm'], 4, ',', '.') ?></span>
                        </div>
                        <div class="data-item">
                            <span class="label">VM (Verticale):</span>
                            <span class="value formula"><?= number_format($result['vm'], 4, ',', '.') ?></span>
                        </div>
                        <div class="data-item">
                            <span class="label">DM (Distanza):</span>
                            <span class="value formula"><?= number_format($result['dm'], 4, ',', '.') ?></span>
                        </div>
                        <div class="data-item">
                            <span class="label">AM (Asimmetria):</span>
                            <span class="value formula"><?= number_format($result['am'], 4, ',', '.') ?></span>
                        </div>
                        <div class="data-item">
                            <span class="label">FM (Frequenza):</span>
                            <span class="value formula"><?= number_format($result['fm'], 4, ',', '.') ?></span>
                        </div>
                        <div class="data-item">
                            <span class="label">CM (Presa):</span>
                            <span class="value formula"><?= number_format($result['cm'], 4, ',', '.') ?></span>
                        </div>
                    </div>
                </fieldset>

                <!-- RWL e LI -->
                <fieldset>
                    <legend>📈 Risultati Finali</legend>
                    <div class="results-main">
                        <div class="result-item">
                            <div class="result-label">RWL (Recommended Weight Limit)</div>
                            <div class="result-value">
                                <?= number_format($result['rwl'], 2, ',', '.') ?> kg
                            </div>
                            <div class="result-formula">
                                23 kg × <?= number_format($result['hm'], 3, ',', '.') ?> × 
                                <?= number_format($result['vm'], 3, ',', '.') ?> × 
                                <?= number_format($result['dm'], 3, ',', '.') ?> × 
                                <?= number_format($result['am'], 3, ',', '.') ?> × 
                                <?= number_format($result['fm'], 3, ',', '.') ?> × 
                                <?= number_format($result['cm'], 3, ',', '.') ?>
                            </div>
                        </div>

                        <div class="result-item">
                            <div class="result-label">LI (Lift Index)</div>
                            <div class="result-value li-value-<?= $result['li'] <= 1 ? 'safe' : ($result['li'] <= 2 ? 'moderate' : ($result['li'] <= 3 ? 'high' : 'critical')) ?>">
                                <?= number_format($result['li'], 2, ',', '.') ?>
                            </div>
                            <div class="result-formula">
                                Peso / RWL = <?= number_format($result['weight'], 2, ',', '.') ?> / 
                                <?= number_format($result['rwl'], 2, ',', '.') ?>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- Risk Description -->
                <div class="risk-description">
                    <h3>📝 Valutazione del Rischio</h3>
                    <p><?= htmlspecialchars($result['risk_description']) ?></p>
                </div>

                <!-- Actions -->
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <form method="post" action="niosh_form.php" style="flex: 1;">
                        <button type="submit" name="reset" value="1" class="btn btn-secondary" style="width: 100%;">
                            ➕ Nuova Valutazione
                        </button>
                    </form>
                    <a href="history.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                        📊 Storico Valutazioni
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
