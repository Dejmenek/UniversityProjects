<?php
if (!isLoggedIn()) {
    redirect('/?page=login');
}

$error = '';
$success = '';

// Pobierz ID książki z URL
$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Pobierz szczegóły książki
$stmt = $pdo->prepare("
    SELECT b.*, 
           GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
           p.name as publisher_name,
           u.username as added_by_username,
           b.added_by,
           rs.status as reading_status,
           rs.start_date,
           rs.end_date,
           bn.rating,
           bn.notes
    FROM books b
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    LEFT JOIN publishers p ON b.publisher_id = p.id
    LEFT JOIN users u ON b.added_by = u.id
    LEFT JOIN reading_status rs ON b.id = rs.book_id AND rs.user_id = ?
    LEFT JOIN book_notes bn ON b.id = bn.book_id AND bn.user_id = ?
    WHERE b.id = ?
    GROUP BY b.id
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $bookId]);
$book = $stmt->fetch();

if (!$book) {
    redirect('/?page=books');
}

// Pobierz półki, na których znajduje się książka
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM shelves s
    JOIN shelf_books sb ON s.id = sb.shelf_id
    WHERE sb.book_id = ? AND s.user_id = ?
    ORDER BY s.name
");
$stmt->execute([$bookId, $_SESSION['user_id']]);
$shelves = $stmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $status = $_POST['status'];
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;
    $notes = trim($_POST['notes']);

    if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
        $error = 'Data rozpoczęcia nie może być późniejsza niż data zakończenia.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM reading_status WHERE book_id = ? AND user_id = ?");
            $stmt->execute([$bookId, $_SESSION['user_id']]);
            $existingStatus = $stmt->fetch();
            
            if ($existingStatus) {
                // Aktualizuj istniejący status
                $stmt = $pdo->prepare("
                    UPDATE reading_status 
                    SET status = ?, start_date = ?, end_date = ? 
                    WHERE book_id = ? AND user_id = ?
                ");
                $stmt->execute([$status, $startDate, $endDate, $bookId, $_SESSION['user_id']]);
            } else {
                // Dodaj nowy status
                $stmt = $pdo->prepare("
                    INSERT INTO reading_status (book_id, user_id, status, start_date, end_date) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$bookId, $_SESSION['user_id'], $status, $startDate, $endDate]);
            }
            
            $stmt = $pdo->prepare("
                SELECT status, start_date, end_date 
                FROM reading_status 
                WHERE book_id = ? AND user_id = ?
            ");
            $stmt->execute([$bookId, $_SESSION['user_id']]);
            $readingStatus = $stmt->fetch();

            $stmt = $pdo->prepare("SELECT id FROM book_notes WHERE book_id = ? AND user_id = ?");
            $stmt->execute([$bookId, $_SESSION['user_id']]);
            $existingNote = $stmt->fetch();
            
            if ($existingNote) {
                // Aktualizuj istniejącą notatkę
                $stmt = $pdo->prepare("
                    UPDATE book_notes 
                    SET rating = ?, notes = ? 
                    WHERE book_id = ? AND user_id = ?
                ");
                $stmt->execute([$rating, $notes, $bookId, $_SESSION['user_id']]);
            } else {
                // Dodaj nową notatkę
                $stmt = $pdo->prepare("
                    INSERT INTO book_notes (book_id, user_id, rating, notes) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$bookId, $_SESSION['user_id'], $rating, $notes]);
            }
            
            $pdo->commit();
            $success = 'Zmiany zostały zapisane.';

            // Odśwież dane książki
            $book['rating'] = $rating;
            $book['notes'] = $notes;
            $book['reading_status'] = $readingStatus['status'];
            $book['start_date'] = $readingStatus['start_date'];
            $book['end_date'] = $readingStatus['end_date'];
        } catch (Exception $e) {
            $pdo->rollback();
            $error = 'Wystąpił błąd podczas zapisu ' . $e->getMessage();
        }
    }
}
?>

<div class="book-details-container">
    <div class="book-header">
        <div class="book-cover-large">
            <?php if ($book['cover_image']): ?>
                <img src="<?php echo APP_URL . '/uploads/covers/' . $book['cover_image']; ?>" 
                     alt="<?php echo escape($book['title']); ?>">
            <?php else: ?>
                <div class="no-cover">
                    <span>Brak okładki</span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="book-info-main">
            <h1><?php echo escape($book['title']); ?></h1>
            
            <div class="book-meta">
                <p class="book-author">
                    <strong>Autor:</strong> <?php echo escape($book['authors'] ?? 'Autor nieznany'); ?>
                </p>
                
                <?php if ($book['publisher_name']): ?>
                    <p class="book-publisher">
                        <strong>Wydawnictwo:</strong> <?php echo escape($book['publisher_name']); ?>
                    </p>
                <?php endif; ?>
                
                <?php if ($book['publication_year']): ?>
                    <p class="book-year">
                        <strong>Rok wydania:</strong> <?php echo $book['publication_year']; ?>
                    </p>
                <?php endif; ?>
                
                <p class="book-format">
                    <strong>Format:</strong> 
                    <?php 
                    switch ($book['format']) {
                        case 'hardcover': echo 'Twarda oprawa'; break;
                        case 'paperback': echo 'Miękka oprawa'; break;
                        case 'ebook': echo 'E-book'; break;
                        case 'audiobook': echo 'Audiobook'; break;
                        default: echo escape($book['format']);
                    }
                    ?>
                </p>
                
                <?php if ($book['isbn']): ?>
                    <p class="book-isbn">
                        <strong>ISBN:</strong> <?php echo escape($book['isbn']); ?>
                    </p>
                <?php endif; ?>
                
                <p class="book-added">
                    <strong>Dodano przez:</strong> <?php echo escape($book['added_by_username']); ?>
                </p>
            </div>
            
            <?php if ($book['description']): ?>
                <div class="book-description">
                    <h2>Opis</h2>
                    <p><?php echo nl2br(escape($book['description'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

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

    <form method="POST" action="" id="bookDetailsForm" onsubmit="return validateForm(event)">
        <div class="book-details-grid">
            <div class="book-section">
                <h2>Status czytania</h2>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required onchange="handleStatusChange()">
                        <option value="to_read" <?php echo $book['reading_status'] === 'to_read' ? 'selected' : ''; ?>>
                            Do przeczytania
                        </option>
                        <option value="reading" <?php echo $book['reading_status'] === 'reading' ? 'selected' : ''; ?>>
                            W trakcie czytania
                        </option>
                        <option value="read" <?php echo $book['reading_status'] === 'read' ? 'selected' : ''; ?>>
                            Przeczytana
                        </option>
                    </select>
                </div>
                <div class="form-group date-group" id="startDateGroup">
                    <label for="start_date">Data rozpoczęcia</label>
                    <input type="date" id="start_date" name="start_date" 
                           value="<?php echo $book['start_date']; ?>">
                    <div class="error-message" id="startDateError"></div>
                </div>
                <div class="form-group date-group" id="endDateGroup">
                    <label for="end_date">Data zakończenia</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?php echo $book['end_date']; ?>">
                    <div class="error-message" id="endDateError"></div>
                </div>
                <button type="submit" class="btn">Aktualizuj status</button>
            </div>

            <div class="book-section">
                <h2>Twoje notatki</h2>
                <div class="form-group" id="ratingGroup">
                    <label for="rating">Ocena</label>
                    <select id="rating" name="rating">
                        <option value="">-- Wybierz ocenę --</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" 
                                    <?php echo $book['rating'] == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?> <?php echo $i == 1 ? 'gwiazdka' : ($i < 5 ? 'gwiazdki' : 'gwiazdek'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                        
                <div class="form-group">
                    <label for="notes">Notatki</label>
                    <textarea id="notes" name="notes" rows="6"><?php echo escape($book['notes'] ?? ''); ?></textarea>
                </div>
                        
                <button type="submit" class="btn">Zapisz notatki</button>
            </div>

            <div class="book-section">
                <h2>Twoje półki</h2>
                <?php if (empty($shelves)): ?>
                    <p class="no-shelves">Ta książka nie znajduje się na żadnej z Twoich półek.</p>
                <?php else: ?>
                    <ul class="shelves-list">
                        <?php foreach ($shelves as $shelf): ?>
                            <li>
                                <a href="<?php echo APP_URL; ?>?page=shelf-details&id=<?php echo $shelf['id']; ?>">
                                    <?php echo escape($shelf['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                        
                <a href="<?php echo APP_URL; ?>?page=shelves" class="btn btn-primary btn-small">Zarządzaj półkami</a>
            </div>
        </div>
    </form>
    
</div>

<script>
let formSubmitted = false;

function handleStatusChange() {
    const status = document.getElementById('status').value;
    const startDateGroup = document.getElementById('startDateGroup');
    const endDateGroup = document.getElementById('endDateGroup');
    const ratingGroup = document.getElementById('ratingGroup');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const ratingInput = document.getElementById('rating');
    const startDateError = document.getElementById('startDateError');
    const endDateError = document.getElementById('endDateError');

    // Wyczyść komunikaty błędów tylko jeśli formularz nie był jeszcze wysłany
    if (!formSubmitted) {
        startDateError.textContent = '';
        endDateError.textContent = '';
    }

    if (status === 'to_read') {
        // Ukryj pola dat i oceny dla statusu "do przeczytania"
        startDateGroup.style.display = 'none';
        endDateGroup.style.display = 'none';
        ratingGroup.style.display = 'none';
        
        startDateInput.value = '';
        endDateInput.value = '';
        ratingInput.value = '';
        startDateInput.removeAttribute('required');
        endDateInput.removeAttribute('required');
    } else {
        // Pokaż pola dat dla innych statusów
        startDateGroup.style.display = 'block';
        startDateInput.setAttribute('required', 'required');
        
        if (status === 'reading') {
            // Dla statusu "w trakcie czytania" ukryj datę zakończenia
            endDateGroup.style.display = 'none';
            endDateInput.value = '';
            endDateInput.removeAttribute('required');
        } else if (status === 'read') {
            // Dla statusu "przeczytana" pokaż i wymagaj obu dat
            endDateGroup.style.display = 'block';
            endDateInput.setAttribute('required', 'required');
        }
        
        ratingGroup.style.display = 'block';
    }
}

function validateForm(event) {
    event.preventDefault();
    formSubmitted = true;
    
    const status = document.getElementById('status').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const startDateError = document.getElementById('startDateError');
    const endDateError = document.getElementById('endDateError');
    
    startDateError.textContent = '';
    endDateError.textContent = '';
    
    let isValid = true;
    const errors = [];
    
    if (status === 'to_read') {
        document.getElementById('bookDetailsForm').submit();
        return;
    }
    
    // Sprawdź czy wymagane daty są wypełnione
    if (status === 'reading' && !startDate) {
        errors.push({ field: 'startDate', message: 'Data rozpoczęcia jest wymagana dla książki w trakcie czytania' });
        isValid = false;
    }
    
    if (status === 'read') {
        if (!startDate) {
            errors.push({ field: 'startDate', message: 'Data rozpoczęcia jest wymagana dla przeczytanej książki' });
            isValid = false;
        }
        if (!endDate) {
            errors.push({ field: 'endDate', message: 'Data zakończenia jest wymagana dla przeczytanej książki' });
            isValid = false;
        }
    }
    
    // Sprawdź poprawność dat
    if (startDate && endDate && (new Date(startDate) > new Date(endDate))) {
        errors.push({ field: 'endDate', message: 'Data zakończenia nie może być wcześniejsza niż data rozpoczęcia' });
        isValid = false;
    }
    
    // Wyświetl wszystkie błędy
    if (!isValid) {
        errors.forEach(error => {
            if (error.field === 'startDate') {
                startDateError.textContent = error.message;
                startDateError.style.display = 'block'
            } else if (error.field === 'endDate') {
                endDateError.textContent = error.message;
                endDateError.style.display = 'block'
            }
        });
        return;
    }
    
    document.getElementById('bookDetailsForm').submit();
    return
}

document.addEventListener('DOMContentLoaded', function() {
    handleStatusChange();
});
</script>