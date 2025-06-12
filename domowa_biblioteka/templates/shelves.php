<?php
if (!isLoggedIn()) {
    redirect('/?page=login');
}

$error = '';
$success = '';

// Obsługa komunikatów sesji
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Obsługa dodawania nowej półki
if (isset($_POST['add_shelf'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $error = 'Nazwa półki jest wymagana.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO shelves (name, description, user_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$name, $description, $_SESSION['user_id']]);
            $success = 'Półka została dodana pomyślnie.';
        } catch (Exception $e) {
            $error = 'Wystąpił błąd podczas dodawania półki: ' . $e->getMessage();
        }
    }
}

// Pobierz wszystkie półki użytkownika
$stmt = $pdo->prepare("
    SELECT s.*, COUNT(sb.book_id) as book_count 
    FROM shelves s 
    LEFT JOIN shelf_books sb ON s.id = sb.shelf_id 
    WHERE s.user_id = ? 
    GROUP BY s.id 
    ORDER BY s.name
");
$stmt->execute([$_SESSION['user_id']]);
$shelves = $stmt->fetchAll();
?>

<div class="shelves-container">
    <h1>Moje półki</h1>

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

    <div class="add-shelf-form">
        <h2>Dodaj nową półkę</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Nazwa półki</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Opis (opcjonalnie)</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <button type="submit" name="add_shelf" class="btn">Dodaj półkę</button>
        </form>
    </div>

    <div class="shelves-list">
        <h2>Twoje półki</h2>
        <?php if (empty($shelves)): ?>
            <p class="no-shelves">Nie masz jeszcze żadnych półek. Dodaj pierwszą półkę!</p>
        <?php else: ?>
            <div class="shelves-grid">
                <?php foreach ($shelves as $shelf): ?>
                    <div class="shelf-card">
                        <h3><?php echo escape($shelf['name']); ?></h3>
                        <?php if ($shelf['description']): ?>
                            <p class="shelf-description"><?php echo escape($shelf['description']); ?></p>
                        <?php endif; ?>
                        <p class="shelf-stats">
                            <span class="book-count"><?php echo $shelf['book_count']; ?> książek</span>
                        </p>
                        <div class="shelf-actions">
                            <a href="<?php echo APP_URL; ?>?page=shelf-details&id=<?php echo $shelf['id']; ?>" 
                               class="btn btn-small">Zobacz książki</a>
                            <button class="btn btn-small btn-danger" 
                                    onclick="deleteShelf(<?php echo $shelf['id']; ?>)">Usuń</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteShelf(shelfId) {
    if (confirm('Czy na pewno chcesz usunąć tę półkę? Wszystkie powiązania z książkami zostaną usunięte.')) {
        window.location.href = `<?php echo APP_URL; ?>?page=shelves&action=delete&id=${shelfId}`;
    }
}
</script> 