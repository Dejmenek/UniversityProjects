<?php
if (isLoggedIn()) {
    redirect('/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Wszystkie pola są wymagane.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            redirect('/');
        } else {
            $error = 'Nieprawidłowa nazwa użytkownika lub hasło.';
        }
    }
}
?>

<div class="auth-container">
    <h1>Logowanie</h1>
    
    <?php if ($error): ?>
        <div class="error-message">
            <?php echo escape($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="auth-form">
        <div class="form-group">
            <label for="username">Nazwa użytkownika</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="password">Hasło</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn">Zaloguj się</button>
    </form>

    <p class="auth-links">
        Nie masz konta? <a href="<?php echo APP_URL; ?>?page=register">Zarejestruj się</a>
    </p>
</div> 