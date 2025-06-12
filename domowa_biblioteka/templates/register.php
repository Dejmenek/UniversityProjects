<?php
if (isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Wszystkie pola są wymagane.';
    } elseif ($password !== $password_confirm) {
        $error = 'Hasła nie są identyczne.';
    } elseif (strlen($password) < 8) {
        $error = 'Hasło musi mieć co najmniej 8 znaków.';
    } else {
        // Sprawdź czy użytkownik już istnieje
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Użytkownik o podanej nazwie lub adresie email już istnieje.';
        } else {
            // Dodaj nowego użytkownika
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            
            try {
                $stmt->execute([$username, $email, $password_hash]);
                $success = 'Rejestracja zakończona sukcesem. Możesz się teraz zalogować.';
            } catch (PDOException $e) {
                $error = 'Wystąpił błąd podczas rejestracji. Spróbuj ponownie później.';
            }
        }
    }
}
?>

<div class="auth-container">
    <h1>Rejestracja</h1>
    
    <?php if ($error): ?>
        <div class="error-message">
            <?php echo escape($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message">
            <?php echo escape($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="auth-form">
        <div class="form-group">
            <label for="username">Nazwa użytkownika</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="email">Adres email</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password">Hasło</label>
            <input type="password" id="password" name="password" required>
            <small>Hasło musi mieć co najmniej 8 znaków.</small>
        </div>

        <div class="form-group">
            <label for="password_confirm">Potwierdź hasło</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>

        <button type="submit" class="btn">Zarejestruj się</button>
    </form>

    <p class="auth-links">
        Masz już konto? <a href="<?php echo APP_URL; ?>?page=login">Zaloguj się</a>
    </p>
</div> 