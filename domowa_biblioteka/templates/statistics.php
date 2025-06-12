<?php
// Sprawdzenie czy użytkownik jest zalogowany
if (!isLoggedIn()) {
    redirect('/?page=login');
}

$stats = [];

// Całkowita liczba książek
$query = "SELECT COUNT(*) as total FROM books";
$stmt = $pdo->query($query);
$stats['total_books'] = $stmt->fetch()['total'];

// Liczba książek według formatu
$query = "SELECT format, COUNT(*) as count FROM books GROUP BY format";
$stmt = $pdo->query($query);
$stats['format_stats'] = [];
while ($row = $stmt->fetch()) {
    $stats['format_stats'][$row['format']] = $row['count'];
}

// Liczba książek według statusu czytania
$query = "SELECT status, COUNT(*) as count FROM reading_status WHERE user_id = ? GROUP BY status";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['reading_stats'] = [];
while ($row = $stmt->fetch()) {
    $stats['reading_stats'][$row['status']] = $row['count'];
}

// Średnia ocena książek
$query = "SELECT AVG(rating) as avg_rating FROM book_notes WHERE user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['avg_rating'] = round($stmt->fetch()['avg_rating'], 2);

// Najczęściej czytani autorzy (top 5)
$query = "SELECT a.first_name, a.last_name, COUNT(*) as count 
          FROM reading_status rs 
          JOIN books b ON rs.book_id = b.id 
          JOIN book_authors ba ON b.id = ba.book_id 
          JOIN authors a ON ba.author_id = a.id 
          WHERE rs.user_id = ? AND rs.status = 'read' 
          GROUP BY a.id 
          ORDER BY count DESC 
          LIMIT 5";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['top_authors'] = $stmt->fetchAll();

// Najpopularniejsze wydawnictwa (top 5)
$query = "SELECT p.name, COUNT(*) as count 
          FROM reading_status rs 
          JOIN books b ON rs.book_id = b.id 
          JOIN publishers p ON b.publisher_id = p.id 
          WHERE rs.user_id = ? AND rs.status = 'read' 
          GROUP BY p.id 
          ORDER BY count DESC 
          LIMIT 5";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['top_publishers'] = $stmt->fetchAll();

// Książki przeczytane w ostatnich 6 miesiącach
$query = "SELECT DATE_FORMAT(end_date, '%Y-%m') as month, COUNT(*) as count 
          FROM reading_status 
          WHERE user_id = ? AND status = 'read' 
          AND end_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH) 
          GROUP BY month 
          ORDER BY month";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['monthly_reading'] = $stmt->fetchAll();
?>

<div class="statistics-container">
    <h1>Statystyki biblioteki</h1>

    <div class="statistics-grid">
        <div class="stat-card">
            <h5><i class="fa-solid fa-book"></i> Ogólne statystyki</h5>
            <p>
                Całkowita liczba książek
                <span class="stat-value"><?php echo $stats['total_books']; ?></span>
            </p>
            <p>
                Średnia ocena książek
                <span class="stat-value"><?php echo $stats['avg_rating']; ?>/5</span>
            </p>
            
            <h5><i class="fa-solid fa-bookmark"></i> Książki według formatu</h5>
            <ul>
                <?php foreach ($stats['format_stats'] as $format => $count): ?>
                    <li>
                        <?php echo ucfirst($format); ?>
                        <span class="stat-value"><?php echo $count; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5><i class="fa-solid fa-book-open"></i> Status czytania</h5>
            <ul>
                <?php 
                $status_labels = [
                    'to_read' => 'Do przeczytania',
                    'reading' => 'W trakcie',
                    'read' => 'Przeczytane'
                ];
                foreach ($stats['reading_stats'] as $status => $count): 
                ?>
                    <li>
                        <?php echo $status_labels[$status]; ?>
                        <span class="stat-value"><?php echo $count; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="stat-card">
            <h5><i class="fa-solid fa-user-pen"></i> Statystyki czytania</h5>
            <h5><i class="fa-solid fa-crown"></i> Najczęściej czytani autorzy</h5>
            <ul>
                <?php foreach ($stats['top_authors'] as $author): ?>
                    <li>
                        <?php echo htmlspecialchars($author['first_name'] . ' ' . $author['last_name']); ?>
                        <span class="stat-value"><?php echo $author['count']; ?> książek</span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5><i class="fa-solid fa-building"></i> Najpopularniejsze wydawnictwa</h5>
            <ul>
                <?php foreach ($stats['top_publishers'] as $publisher): ?>
                    <li>
                        <?php echo htmlspecialchars($publisher['name']); ?>
                        <span class="stat-value"><?php echo $publisher['count']; ?> książek</span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5><i class="fa-solid fa-calendar"></i> Książki przeczytane w ostatnich 6 miesiącach</h5>
            <ul>
                <?php foreach ($stats['monthly_reading'] as $month): ?>
                    <li>
                        <?php echo date('F Y', strtotime($month['month'] . '-01')); ?>
                        <span class="stat-value"><?php echo $month['count']; ?> książek</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>