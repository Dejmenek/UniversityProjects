<?php
if (!isLoggedIn()) {
    redirect('/?page=login');
}

// Parametry wyszukiwania i filtrowania
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$author = isset($_GET['author']) ? trim($_GET['author']) : '';
$publisher = isset($_GET['publisher']) ? trim($_GET['publisher']) : '';
$format = isset($_GET['format']) ? $_GET['format'] : '';
$yearFrom = isset($_GET['year_from']) ? (int)$_GET['year_from'] : null;
$yearTo = isset($_GET['year_to']) ? (int)$_GET['year_to'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'title_asc';

$query = "
    SELECT DISTINCT b.*, 
           GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
           p.name as publisher_name,
           COUNT(DISTINCT sb.shelf_id) as shelf_count
    FROM books b
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    LEFT JOIN publishers p ON b.publisher_id = p.id
    LEFT JOIN shelf_books sb ON b.id = sb.book_id
    WHERE 1=1
";

$params = [];

// Dodaj warunki wyszukiwania
if ($search) {
    $query .= " AND (b.title LIKE ? OR b.isbn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($author) {
    $query .= " AND (a.first_name LIKE ? OR a.last_name LIKE ?)";
    $params[] = "%$author%";
    $params[] = "%$author%";
}

if ($publisher) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$publisher%";
}

if ($format) {
    $query .= " AND b.format = ?";
    $params[] = $format;
}

if ($yearFrom) {
    $query .= " AND b.publication_year >= ?";
    $params[] = $yearFrom;
}

if ($yearTo) {
    $query .= " AND b.publication_year <= ?";
    $params[] = $yearTo;
}

// Grupowanie
$query .= " GROUP BY b.id";

// Sortowanie
switch ($sort) {
    case 'title_desc':
        $query .= " ORDER BY b.title DESC";
        break;
    case 'year_asc':
        $query .= " ORDER BY b.publication_year ASC";
        break;
    case 'year_desc':
        $query .= " ORDER BY b.publication_year DESC";
        break;
    case 'shelves_desc':
        $query .= " ORDER BY shelf_count DESC";
        break;
    default:
        $query .= " ORDER BY b.title ASC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

$stmt = $pdo->query("SELECT DISTINCT format FROM books ORDER BY format");
$formats = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("
    SELECT DISTINCT name 
    FROM publishers 
    ORDER BY name
");
$publishers = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("
    SELECT DISTINCT CONCAT(first_name, ' ', last_name) as name 
    FROM authors 
    ORDER BY name
");
$authors = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="books-container">
    <h1>Biblioteka</h1>

    <div class="search-filters">
        <form method="GET" action="" class="filters-form">
            <input type="hidden" name="page" value="books">
            
            <div class="search-row">
                <div class="form-group">
                    <label for="search">Szukaj</label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo escape($search); ?>" 
                           placeholder="Tytuł lub ISBN">
                </div>
                
                <div class="form-group">
                    <label for="author">Autor</label>
                    <input type="text" id="author" name="author" 
                           value="<?php echo escape($author); ?>" 
                           placeholder="Imię i nazwisko">
                </div>
                
                <div class="form-group">
                    <label for="publisher">Wydawnictwo</label>
                    <select id="publisher" name="publisher">
                        <option value="">Wszystkie</option>
                        <?php foreach ($publishers as $pub): ?>
                            <option value="<?php echo escape($pub); ?>" 
                                    <?php echo $publisher === $pub ? 'selected' : ''; ?>>
                                <?php echo escape($pub); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filters-row">
                <div class="form-group">
                    <label for="format">Format</label>
                    <select id="format" name="format">
                        <option value="">Wszystkie</option>
                        <?php foreach ($formats as $fmt): ?>
                            <option value="<?php echo escape($fmt); ?>" 
                                    <?php echo $format === $fmt ? 'selected' : ''; ?>>
                                <?php 
                                switch ($fmt) {
                                    case 'hardcover': echo 'Twarda oprawa'; break;
                                    case 'paperback': echo 'Miękka oprawa'; break;
                                    case 'ebook': echo 'E-book'; break;
                                    case 'audiobook': echo 'Audiobook'; break;
                                    default: echo escape($fmt);
                                }
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="year_from">Rok od</label>
                    <input type="number" id="year_from" name="year_from" 
                           value="<?php echo $yearFrom; ?>" 
                           min="1900" max="<?php echo date('Y'); ?>">
                </div>

                <div class="form-group">
                    <label for="year_to">Rok do</label>
                    <input type="number" id="year_to" name="year_to" 
                           value="<?php echo $yearTo; ?>" 
                           min="1900" max="<?php echo date('Y'); ?>">
                </div>

                <div class="form-group">
                    <label for="sort">Sortuj według</label>
                    <select id="sort" name="sort">
                        <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>
                            Tytuł (A-Z)
                        </option>
                        <option value="title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>
                            Tytuł (Z-A)
                        </option>
                        <option value="year_asc" <?php echo $sort === 'year_asc' ? 'selected' : ''; ?>>
                            Rok (rosnąco)
                        </option>
                        <option value="year_desc" <?php echo $sort === 'year_desc' ? 'selected' : ''; ?>>
                            Rok (malejąco)
                        </option>
                        <option value="shelves_desc" <?php echo $sort === 'shelves_desc' ? 'selected' : ''; ?>>
                            Popularność
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Szukaj</button>
                <a href="<?php echo APP_URL; ?>?page=books" class="btn btn-secondary">Wyczyść filtry</a>
            </div>
        </form>
    </div>

    <div class="search-results">
        <h2>Znalezione książki (<?php echo count($books); ?>)</h2>
        
        <?php if (empty($books)): ?>
            <p class="no-results">Nie znaleziono książek spełniających kryteria wyszukiwania.</p>
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
                            <?php if ($book['publication_year']): ?>
                                <p class="book-year"><?php echo $book['publication_year']; ?></p>
                            <?php endif; ?>
                            <p class="book-format">
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
                            <div class="book-actions">
                                <a href="<?php echo APP_URL; ?>?page=book-details&id=<?php echo $book['id']; ?>" 
                                   class="btn btn-primary btn-small">Szczegóły</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div> 