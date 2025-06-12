<?php
require_once 'config/config.php';
require_once 'config/database.php';

$page = $_GET['page'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Inter font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-layout">
    <?php if (isLoggedIn()): ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <i class="fa-solid fa-book-open"></i> <span>Domowa Biblioteka</span>
            <a class="sidebar-toggle" id="sidebarToggle">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
        </div>
        <ul class="sidebar-nav">
            <li><a href="?page=home" class="<?php echo ($_GET['page'] ?? 'home') === 'home' ? 'active' : ''; ?>"><i class="fa-solid fa-compass"></i> <span>Odkrywaj</span></a></li>
            <li><a href="?page=books" class="<?php echo ($_GET['page'] ?? '') === 'books' ? 'active' : ''; ?>"><i class="fa-solid fa-book"></i> <span>Książki</span></a></li>
            <li><a href="?page=shelves" class="<?php echo ($_GET['page'] ?? '') === 'shelves' ? 'active' : ''; ?>"><i class="fa-solid fa-layer-group"></i> <span>Półki</span></a></li>
            <li><a href="?page=statistics" class="<?php echo ($_GET['page'] ?? '') === 'statistics' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-bar"></i> <span>Statystyki</span></a></li>
            <li><a href="?page=add-book" class="<?php echo ($_GET['page'] ?? '') === 'add-book' ? 'active' : ''; ?>"><i class="fa-solid fa-plus"></i> <span>Dodaj książkę</span></a></li>
            <li><a href="./logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Wyloguj</span></a></li>
        </ul>
    </aside>
    <?php endif; ?>
    <section class="main-content">
    <?php
        switch ($page) {
            case 'home':
                include 'templates/home.php';
                break;
            case 'login':
                include 'templates/login.php';
                break;
            case 'register':
                include 'templates/register.php';
                break;
            case 'books':
                include 'templates/books.php';
                break;
            case 'add-book':
                include 'templates/add-book.php';
                break;
            case 'login':
                include 'templates/login.php';
                break;
            case 'register':
                include 'templates/register.php';
                break;
            case 'books':
                include 'templates/books.php';
                break;
            case 'add-book':
                include 'templates/add-book.php';
                break;
            case 'book-details':
                include 'templates/book-details.php';
                break;
            case 'shelves':
                if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM shelves WHERE id = ? AND user_id = ?");
                        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
                        $_SESSION['success'] = 'Półka została usunięta pomyślnie.';
                        redirect('/?page=shelves');
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Wystąpił błąd podczas usuwania półki: ' . $e->getMessage();
                        redirect('/?page=shelves');
                    }
                }
                include 'templates/shelves.php';
                break;
            case 'shelf-details':
                include 'templates/shelf-details.php';
                break;
            case 'statistics':
                include 'templates/statistics.php';
                break;
            default:
                include 'templates/404.php';
                break;
        }
    ?>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('sidebar-collapsed');
            }
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('sidebar-collapsed'));
            });
        });
    </script>
</div>
<nav class="bottom-nav">
    <a href="?page=home" class="nav-item<?php echo ($_GET['page'] ?? 'home') === 'home' ? ' active' : ''; ?>">
        <i class="fa-solid fa-compass"></i>
    </a>
    <a href="?page=books" class="nav-item<?php echo ($_GET['page'] ?? '') === 'books' ? ' active' : ''; ?>">
        <i class="fa-solid fa-book"></i>
    </a>
    <a href="?page=shelves" class="nav-item<?php echo ($_GET['page'] ?? '') === 'shelves' ? ' active' : ''; ?>">
        <i class="fa-solid fa-layer-group"></i>
    </a>
    <a href="?page=statistics" class="nav-item<?php echo ($_GET['page'] ?? '') === 'statistics' ? ' active' : ''; ?>">
        <i class="fa-solid fa-chart-bar"></i>
    </a>
    <a href="?page=add-book" class="nav-item<?php echo ($_GET['page'] ?? '') === 'add-book' ? ' active' : ''; ?>">
        <i class="fa-solid fa-plus"></i>
    </a>
    <a href="./logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i></a>
</nav>
</body>
</html>