<?php
require_once __DIR__ . '/../includes/book_functions.php';

if (!isLoggedIn()) {
    redirect('/?page=login');
}

$error = '';
$success = '';
$bookData = null;

// Obsługa wyszukiwania po ISBN
if (isset($_POST['search_isbn'])) {
    $isbn = trim($_POST['isbn']);
    if (empty($isbn)) {
        $error = 'Wprowadź numer ISBN.';
    } else {
        $bookData = getBookDataFromISBN($isbn);
        if (isset($bookData['error'])) {
            $error = $bookData['error'];
            $bookData = null;
        }
    }
}

// Obsługa dodawania książki
if (isset($_POST['add_book'])) {
    try {
        $bookData = [
            'isbn' => $_POST['isbn'] ?? '',
            'title' => $_POST['title'] ?? '',
            'authors' => [],
            'publisher' => $_POST['publisher'] ?? '',
            'publication_year' => $_POST['publication_year'] ?? null,
            'description' => $_POST['description'] ?? '',
            'format' => $_POST['format'] ?? 'paperback',
            'cover_image' => null
        ];

        // Obsługa autorów
        $authorNames = explode(',', $_POST['authors']);
        foreach ($authorNames as $authorName) {
            $nameParts = explode(' ', trim($authorName));
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);
            $bookData['authors'][] = [
                'first_name' => $firstName,
                'last_name' => $lastName
            ];
        }

        // Obsługa okładki
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cover'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('cover_') . '.' . $ext;
            $filepath = COVERS_PATH . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $bookData['cover_image'] = $filename;
            }
        } elseif (isset($_POST['cover_id']) && !empty($_POST['cover_id'])) {
            // Pobierz okładkę z Open Library
            $coverId = $_POST['cover_id'];
            $coverFilename = getBookCover($coverId);
            if ($coverFilename) {
                $bookData['cover_image'] = $coverFilename;
            }
        }

        $bookId = addBook($bookData, $_SESSION['user_id']);
        $success = 'Książka została dodana pomyślnie.';
        $bookData = null; // Wyczyść dane formularza
        
    } catch (Exception $e) {
        $error = 'Wystąpił błąd podczas dodawania książki: ' . $e->getMessage();
    }
}
?>

<div class="add-book-container">
    <h1>Dodaj nową książkę</h1>

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

    <div class="isbn-search">
        <h2>Wyszukaj po ISBN</h2>
        <form method="POST" action="" class="isbn-form">
            <div class="form-group">
                <label for="isbn">Numer ISBN</label>
                <input type="text" id="isbn" name="isbn" required 
                       value="<?php echo isset($_POST['isbn']) ? escape($_POST['isbn']) : ''; ?>">
            </div>
            <button type="submit" name="search_isbn" class="btn">Wyszukaj</button>
        </form>
    </div>

    <form method="POST" action="" enctype="multipart/form-data" class="add-book-form">
        <div class="form-group">
            <label for="title">Tytuł</label>
            <input type="text" id="title" name="title" required
                   value="<?php echo $bookData ? escape($bookData['title']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="isbn">Numer ISBN</label>
            <input type="text" id="isbn" name="isbn" 
                   value="<?php echo $bookData ? escape($bookData['isbn']) : ''; ?>"
                   placeholder="Opcjonalnie">
        </div>

        <div class="form-group">
            <label for="authors">Autorzy (oddzieleni przecinkami)</label>
            <input type="text" id="authors" name="authors" required
                   value="<?php 
                   if ($bookData && !empty($bookData['authors'])) {
                       $authorNames = array_map(function($author) {
                           return $author['name'];
                       }, $bookData['authors']);
                       echo escape(implode(', ', $authorNames));
                   }
                   ?>">
        </div>

        <div class="form-group">
            <label for="publisher">Wydawnictwo</label>
            <input type="text" id="publisher" name="publisher"
                   value="<?php 
                   if ($bookData && !empty($bookData['publishers'])) {
                       echo escape($bookData['publishers'][0]);
                   }
                   ?>">
        </div>

        <div class="form-group">
            <label for="publication_year">Rok wydania</label>
            <input type="number" id="publication_year" name="publication_year"
                   value="<?php 
                   if ($bookData && !empty($bookData['publish_date'])) {
                       echo escape($bookData['publish_date']);
                   }
                   ?>">
        </div>

        <div class="form-group">
            <label for="format">Format</label>
            <select id="format" name="format" required>
                <option value="hardcover">Twarda oprawa</option>
                <option value="paperback">Miękka oprawa</option>
                <option value="ebook">E-book</option>
                <option value="audiobook">Audiobook</option>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Opis</label>
            <textarea id="description" name="description" rows="4"><?php 
                if ($bookData && !empty($bookData['description'])) {
                    echo escape($bookData['description']);
                }
            ?></textarea>
        </div>

        <div class="form-group">
            <label for="cover">Okładka</label>
            <input type="file" id="cover" name="cover" accept="image/*">
            <?php if ($bookData && !empty($bookData['cover_id'])): ?>
                <div class="cover-preview">
                    <img src="https://covers.openlibrary.org/b/id/<?php echo $bookData['cover_id']; ?>-M.jpg" 
                         alt="Podgląd okładki">
                    <input type="hidden" name="cover_id" value="<?php echo $bookData['cover_id']; ?>">
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" name="add_book" class="btn">Dodaj książkę</button>
    </form>
</div>