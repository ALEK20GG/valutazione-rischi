<?php
// register.php
require_once __DIR__ . '/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isKiosk()) {
        $errors[] = "La registrazione è permessa solo dalla postazione dedicata.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if ($username === '' || $password === '') {
            $errors[] = "Username e password sono obbligatori.";
        } elseif ($password !== $password_confirm) {
            $errors[] = "Le password non coincidono.";
        } elseif (strlen($password) < 6) {
            $errors[] = "La password deve essere di almeno 6 caratteri.";
        } else {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT uid FROM t_user WHERE username = :username");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) {
                $errors[] = "Username già esistente.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO t_user (username, password, creation_date, online) VALUES (:username, :password, NOW(), 0)");
                $stmt->execute([':username' => $username, ':password' => $hash]);
                $success = true;
            }
        }
    }
}
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Registrazione</h1>
            <p>Crea un nuovo account</p>
            <?php if (isKiosk()): ?>
                <span class="badge kiosk">📍 Postazione Kiosk - Registrazione Abilitata</span>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>✓ Registrazione completata!</strong><br>
                Il tuo account è stato creato con successo.
            </div>
            <a href="login.php" class="btn btn-primary">Vai al Login</a>
            <div class="links">
                <a href="index.php">← Torna alla home</a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $e): ?>
                        <div><?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!isKiosk()): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Accesso Negato</strong><br>
                    La registrazione è disponibile solo dalla postazione kiosk dedicata.
                </div>
                <div class="links">
                    <a href="index.php">← Torna alla home</a>
                </div>
            <?php else: ?>
                <form method="post" action="register.php">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required 
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               placeholder="Scegli un username">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required
                               placeholder="Almeno 6 caratteri">
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Conferma Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" required
                               placeholder="Ripeti la password">
                    </div>

                    <button type="submit" class="btn btn-primary">Registrati</button>
                </form>

                <div class="links">
                    <a href="login.php">Hai già un account? Accedi</a>
                    <span style="margin: 0 8px">•</span>
                    <a href="index.php">← Torna alla home</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>