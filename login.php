<?php
// login.php
require_once __DIR__ . '/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = "Compila tutti i campi.";
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT * FROM t_user WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = "Credenziali non valide.";
            } else {
                $uid = (int)$user['uid'];

                insertLog($uid, 1, isKiosk() ? 1 : 0);

                session_regenerate_id(true);
                $_SESSION['uid'] = $uid;
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_kiosk'] = isKiosk() ? 1 : 0;

                header('Location: niosh_form.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Errore di connessione al database.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NIOSH Calculator</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Login</h1>
            <p>Accedi al tuo account</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Inserisci il tuo username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Inserisci la tua password">
            </div>

            <button type="submit" class="btn btn-primary">Accedi</button>
        </form>

        <div class="links">
            <a href="index.php">← Torna alla home</a>
        </div>
    </div>
</body>
</html>