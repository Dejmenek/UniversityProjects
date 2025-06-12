<?php
if (!isLoggedIn()) {
    redirect('/?page=login');
}

$error = '';
$success = '';

// Pobierz ID półki z URL
$shelfId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Sprawdź czy półka istnieje i należy do użytkownika
$stmt = $pdo->prepare("SELECT * FROM shelves WHERE id = ? AND user_id = ?");
$stmt->execute([$shelfId, $_SESSION['user_id']]);
$shelf = $stmt->fetch();

if (!$shelf) {
    redirect('/?page=shelves');
}

// Obsługa dodawania książki do półki
if (isset($_POST['add_book'])) {
    $bookId = (int)$_POST['book_id'];
    
    try {
        // Sprawdź czy książka już jest na półce
        $stmt = $pdo->prepare("SELECT 1 FROM shelf_books WHERE shelf_id = ? AND book_id = ?");
        $stmt->execute([$shelfId, $bookId]);
        
        if ($stmt->fetch()) {
            $error = 'Ta książka jest już na tej półce.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO shelf_books (shelf_id, book_id) VALUES (?, ?)");
            $stmt->execute([$shelfId, $bookId]);
            $success = 'Książka została dodana do półki.';
        }
    } catch (Exception $e) {
        $error = 'Wystąpił błąd podczas dodawania książki do półki: ' . $e->getMessage();
    }
}

// Obsługa usuwania książki z półki
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['book_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM shelf_books WHERE shelf_id = ? AND book_id = ?");
        $stmt->execute([$shelfId, $_GET['book_id']]);
        $success = 'Książka została usunięta z półki.';
    } catch (Exception $e) {
        $error = 'Wystąpił błąd podczas usuwania książki z półki: ' . $e->getMessage();
    }
}

// Pobierz książki z półki
$stmt = $pdo->prepare("
    SELECT b.*, 
           GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
           p.name as publisher_name
    FROM shelf_books sb
    JOIN books b ON sb.book_id = b.id
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    LEFT JOIN publishers p ON b.publisher_id = p.id
    WHERE sb.shelf_id = ?
    GROUP BY b.id
    ORDER BY b.title
");
$stmt->execute([$shelfId]);
$books = $stmt->fetchAll();

// Pobierz wszystkie książki użytkownika (do wyboru przy dodawaniu)
$stmt = $pdo->prepare("
    SELECT b.*, 
           GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors
    FROM books b
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    GROUP BY b.id
    ORDER BY b.title
");
$stmt->execute();
$allBooks = $stmt->fetchAll();
?>

<div class="shelf-details-container">
    <h1><?php echo escape($shelf['name']); ?></h1>
    
    <?php if ($shelf['description']): ?>
        <p class="shelf-description"><?php echo escape($shelf['description']); ?></p>
    <?php endif; ?>

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

    <div class="add-book-to-shelf">
        <h2>Dodaj książkę do półki</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="book_id">Wybierz książkę</label>
                <select id="book_id" name="book_id" required>
                    <option value="">-- Wybierz książkę --</option>
                    <?php foreach ($allBooks as $book): ?>
                        <?php
                        // Sprawdź czy książka już jest na półce
                        $isOnShelf = false;
                        foreach ($books as $shelfBook) {
                            if ($shelfBook['id'] === $book['id']) {
                                $isOnShelf = true;
                                break;
                            }
                        }
                        if (!$isOnShelf):
                        ?>
                            <option value="<?php echo $book['id']; ?>">
                                <?php echo escape($book['title']); ?> 
                                (<?php echo escape($book['authors'] ?? 'Autor nieznany'); ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="add_book" class="btn">Dodaj do półki</button>
        </form>
    </div>

    <div class="shelf-books">
        <h2>Książki na półce</h2>
        <?php if (empty($books)): ?>
            <p class="no-books">Na tej półce nie ma jeszcze żadnych książek.</p>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <?php if ($book['cover_image']): ?>
                            <img src="<?php echo APP_URL . '/uploads/covers/' . $book['cover_image']; ?>" 
                                 alt="<?php echo escape($book['title']); ?>" 
                                 class="book-cover">
                        <?php else: ?>
                            <div class="book-cover no-cover">
                                <span>Brak okładki</span>
                            </div>
                        <?php endif; ?>
                        <div class="book-info">
                            <h3 class="book-title"><?php echo escape($book['title']); ?></h3>
                            <p class="book-author"><?php echo escape($book['authors'] ?? 'Autor nieznany'); ?></p>
                            <?php if ($book['publisher_name']): ?>
                                <p class="book-publisher"><?php echo escape($book['publisher_name']); ?></p>
                            <?php endif; ?>
                            <div class="book-actions">
                                <a href="<?php echo APP_URL; ?>?page=book-details&id=<?php echo $book['id']; ?>" 
                                   class="btn btn-small btn-primary">Szczegóły</a>
                                <a href="<?php echo APP_URL; ?>?page=shelf-details&id=<?php echo $shelfId; ?>&action=remove&book_id=<?php echo $book['id']; ?>" 
                                   class="btn btn-small btn-danger"
                                   onclick="return confirm('Czy na pewno chcesz usunąć tę książkę z półki?')">Usuń z półki</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div> 