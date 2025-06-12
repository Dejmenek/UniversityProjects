<?php
// Podstawowe ustawienia aplikacji
define('APP_NAME', 'Domowa Biblioteka');
define('APP_URL', 'http://localhost/domowa_biblioteka');

// Ścieżki do katalogów
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('COVERS_PATH', UPLOADS_PATH . '/covers');

// Ustawienia sesji
session_start();

// Funkcja do przekierowania
function redirect($path) {
    header("Location: " . APP_URL . $path);
    exit();
}

// Funkcja do sprawdzania czy użytkownik jest zalogowany
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Funkcja do zabezpieczenia przed XSS
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Tworzenie katalogów jeśli nie istnieją
if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0777, true);
}
if (!file_exists(COVERS_PATH)) {
    mkdir(COVERS_PATH, 0777, true);
}
?>