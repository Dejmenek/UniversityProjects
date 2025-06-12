<?php
if (!isLoggedIn()) {
    redirect('/?page=login');
}

// Pobierz statystyki użytkownika
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'read' THEN 1 END) as read_count,
        COUNT(CASE WHEN status = 'reading' THEN 1 END) as reading_count,
        COUNT(CASE WHEN status = 'to_read' THEN 1 END) as to_read_count
    FROM reading_status 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Pobierz ostatnio dodane książki
$stmt = $pdo->query("
    SELECT b.*, u.username as added_by_username,
       GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors
    FROM books b 
    JOIN users u ON b.added_by = u.id 
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    GROUP BY b.id
    ORDER BY b.created_at DESC 
    LIMIT 6
");
?>

<div class="welcome-section">
    <h1>Witaj w <?php echo APP_NAME; ?>!</h1>
    <p>Zarządzaj swoją domową biblioteką w prosty i przyjemny sposób.</p>
</div>

<div class="quick-actions">
    <h2>Szybkie akcje</h2>
    <div class="action-buttons">
        <a href="<?php echo APP_URL; ?>?page=add-book" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Dodaj nową książkę</a>
        <a href="<?php echo APP_URL; ?>?page=books" class="btn btn-primary"><i class="fa-solid fa-book"></i> Przeglądaj bibliotekę</a>
        <a href="<?php echo APP_URL; ?>?page=shelves" class="btn btn-primary"><i class="fa-solid fa-layer-group"></i> Moje półki</a>
    </div>
</div>

<div class="recent-books">
    <h2>Ostatnio dodane książki</h2>
    <div class="book-grid">
        <?php
        while ($book = $stmt->fetch()) {
        ?>
            <div class="book-card">
                <?php if ($book['cover_image']): ?>
                    <img src="<?php echo APP_URL . '/uploads/covers/' . $book['cover_image']; ?>" 
                         alt="<?php echo escape($book['title']); ?>">
                <?php else: ?>
                    <div class="book-cover no-cover">
                        <span>Brak okładki</span>
                    </div>
                <?php endif; ?>
                <div class="book-info">
                    <div class="book-title"><?php echo escape($book['title']); ?></div>
                    <div class="book-author"><?php echo escape($book['authors'] ?? 'Autor nieznany'); ?></div>
                    <div class="book-meta">Dodano przez: <?php echo escape($book['added_by_username']); ?></div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<div class="reading-stats">
    <h2>Twoje statystyki czytania</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Przeczytane</h3>
            <p class="stat-number"><?php echo $stats['read_count']; ?></p>
        </div>
        <div class="stat-card">
            <h3>W trakcie</h3>
            <p class="stat-number"><?php echo $stats['reading_count']; ?></p>
        </div>
        <div class="stat-card">
            <h3>Do przeczytania</h3>
            <p class="stat-number"><?php echo $stats['to_read_count']; ?></p>
        </div>
    </div>
</div>