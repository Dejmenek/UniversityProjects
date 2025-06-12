<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Wykonuje zapytanie HTTP z obsługą przekierowań
 */
function makeHttpRequest($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true,
            'follow_location' => 1 // Włącz śledzenie przekierowań
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return [
            'success' => false,
            'error' => 'Nie udało się połączyć z serwerem Open Library. Spróbuj ponownie później.'
        ];
    }

    // Sprawdź kod odpowiedzi HTTP
    $httpCode = $http_response_header[0] ?? '';
    $statusCode = 0;
    
    // Wyciągnij kod statusu z nagłówka
    if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $httpCode, $matches)) {
        $statusCode = (int)$matches[1];
    }

    // Obsługa różnych kodów odpowiedzi
    switch ($statusCode) {
        case 200:
        case 302:
            return [
                'success' => true,
                'data' => $response
            ];
            
        case 404:
            return [
                'success' => false,
                'error' => 'Nie znaleziono książki o podanym numerze ISBN.'
            ];
            
        case 400:
            return [
                'success' => false,
                'error' => 'Nieprawidłowe zapytanie do API Open Library.'
            ];
            
        case 401:
        case 403:
            return [
                'success' => false,
                'error' => 'Brak dostępu do API Open Library.'
            ];
            
        case 429:
            return [
                'success' => false,
                'error' => 'Przekroczono limit zapytań do API Open Library. Spróbuj ponownie za chwilę.'
            ];
            
        case 500:
        case 502:
        case 503:
        case 504:
            return [
                'success' => false,
                'error' => 'Serwer Open Library jest obecnie niedostępny. Spróbuj ponownie później.'
            ];
            
        default:
            return [
                'success' => false,
                'error' => 'Wystąpił nieoczekiwany błąd podczas pobierania danych książki (kod: ' . $statusCode . '). Spróbuj ponownie później.'
            ];
    }
}

/**
 * Pobiera dane książki z Open Library API na podstawie ISBN
 */
function getBookDataFromISBN($isbn) {
    // Sprawdź czy ISBN jest poprawny
    $isbn = preg_replace('/[^0-9X]/', '', $isbn); // Usuń wszystkie znaki oprócz cyfr i X
    if (strlen($isbn) != 10 && strlen($isbn) != 13) {
        return [
            'error' => 'Nieprawidłowy numer ISBN. Numer ISBN powinien mieć 10 lub 13 cyfr.'
        ];
    }

    // Pobierz podstawowe informacje o książce
    $url = "https://openlibrary.org/isbn/{$isbn}.json";
    $result = makeHttpRequest($url);
    
    if (!$result['success']) {
        return ['error' => $result['error']];
    }
    
    $data = json_decode($result['data'], true);
    if (!$data) {
        return [
            'error' => 'Nie udało się odczytać danych książki. Spróbuj ponownie później.'
        ];
    }

    // Pobierz dane o pracy (work) jeśli są dostępne
    $workData = null;
    if (isset($data['works']) && !empty($data['works'])) {
        $workId = $data['works'][0]['key'];
        $workUrl = "https://openlibrary.org{$workId}.json";
        $workResult = makeHttpRequest($workUrl);
        
        if ($workResult['success']) {
            $workData = json_decode($workResult['data'], true);
        }
    }
    
    // Pobierz informacje o autorach
    $authors = [];
    $authorKeys = [];
    $processedAuthorKeys = []; // Śledzenie już przetworzonych kluczy autorów
    
    // Najpierw sprawdź autorów z podstawowych danych
    if (isset($data['authors'])) {
        foreach ($data['authors'] as $authorRef) {
            if (!in_array($authorRef['key'], $processedAuthorKeys)) {
                $authorKeys[] = $authorRef['key'];
                $processedAuthorKeys[] = $authorRef['key'];
            }
        }
    }
    
    // Następnie sprawdź autorów z danych work
    if ($workData && isset($workData['authors'])) {
        foreach ($workData['authors'] as $authorRef) {
            if (isset($authorRef['author']['key']) && !in_array($authorRef['author']['key'], $processedAuthorKeys)) {
                $authorKeys[] = $authorRef['author']['key'];
                $processedAuthorKeys[] = $authorRef['author']['key'];
            }
        }
    }
    
    // Pobierz szczegółowe dane o autorach
    foreach ($authorKeys as $authorKey) {
        $authorUrl = "https://openlibrary.org{$authorKey}.json";
        $authorResult = makeHttpRequest($authorUrl);
        
        if ($authorResult['success']) {
            $authorData = json_decode($authorResult['data'], true);
            
            if ($authorData) {
                // Podziel imię i nazwisko
                $nameParts = explode(' ', $authorData['name'] ?? '');
                $lastName = array_pop($nameParts);
                $firstName = implode(' ', $nameParts);
                
                $authors[] = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $authorData['name'] ?? '',
                    'key' => $authorKey
                ];
            }
        }
    }
    
    // Pobierz informacje o okładce
    $coverId = null;
    
    // Najpierw sprawdź okładkę w podstawowych danych
    if (isset($data['covers']) && !empty($data['covers'])) {
        $coverId = $data['covers'][0];
    }
    // Jeśli nie znaleziono w podstawowych danych, sprawdź w work
    elseif ($workData && isset($workData['covers']) && !empty($workData['covers'])) {
        $coverId = $workData['covers'][0];
    }
    
    // Przygotuj dane do zwrócenia
    $bookInfo = [
        'title' => $data['title'] ?? '',
        'authors' => $authors,
        'publishers' => $data['publishers'] ?? [],
        'publish_date' => $data['publish_date'] ?? '',
        'cover_id' => $coverId,
        'description' => '',
        'isbn' => $isbn
    ];
    
    // Dodaj opis z work jeśli jest dostępny
    if ($workData) {
        if (isset($workData['description'])) {
            if (is_array($workData['description'])) {
                $bookInfo['description'] = $workData['description']['value'] ?? '';
            } else {
                $bookInfo['description'] = $workData['description'];
            }
        } elseif (isset($workData['excerpts'])) {
            $bookInfo['description'] = $workData['excerpts'][0]['excerpt'] ?? '';
        }
    }
    
    return $bookInfo;
}

/**
 * Pobiera okładkę książki z Open Library
 */
function getBookCover($coverId) {
    if (!$coverId) {
        return null;
    }
    
    $url = "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg";
    $imageData = file_get_contents($url);
    
    if ($imageData === false) {
        return null;
    }
    
    $filename = "cover_{$coverId}.jpg";
    $filepath = COVERS_PATH . '/' . $filename;
    
    if (file_put_contents($filepath, $imageData)) {
        return $filename;
    }
    
    return null;
}

/**
 * Dodaje lub aktualizuje wydawcę w bazie danych
 */
function addOrUpdatePublisher($publisherName) {
    global $pdo;
    
    if (empty($publisherName)) {
        return null;
    }
    
    // Sprawdź czy wydawca już istnieje
    $stmt = $pdo->prepare("SELECT id FROM publishers WHERE name = ?");
    $stmt->execute([$publisherName]);
    $publisher = $stmt->fetch();
    
    if ($publisher) {
        return $publisher['id'];
    }
    
    // Dodaj nowego wydawcę
    $stmt = $pdo->prepare("INSERT INTO publishers (name) VALUES (?)");
    $stmt->execute([$publisherName]);
    return $pdo->lastInsertId();
}

/**
 * Dodaje lub aktualizuje autora w bazie danych
 */
function addOrUpdateAuthor($firstName, $lastName) {
    global $pdo;
    
    if (empty($firstName) || empty($lastName)) {
        return null;
    }
    
    // Sprawdź czy autor już istnieje
    $stmt = $pdo->prepare("SELECT id FROM authors WHERE first_name = ? AND last_name = ?");
    $stmt->execute([$firstName, $lastName]);
    $author = $stmt->fetch();
    
    if ($author) {
        return $author['id'];
    }
    
    // Dodaj nowego autora
    $stmt = $pdo->prepare("INSERT INTO authors (first_name, last_name) VALUES (?, ?)");
    $stmt->execute([$firstName, $lastName]);
    return $pdo->lastInsertId();
}

/**
 * Dodaje książkę do bazy danych
 */
function addBook($bookData, $userId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Dodaj wydawcę
        $publisherId = addOrUpdatePublisher($bookData['publisher']);
        
        // Dodaj książkę
        $stmt = $pdo->prepare("
            INSERT INTO books (
                isbn, title, publisher_id, publication_year, 
                description, cover_image, format, added_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $bookData['isbn'],
            $bookData['title'],
            $publisherId,
            $bookData['publication_year'],
            $bookData['description'],
            $bookData['cover_image'],
            $bookData['format'],
            $userId
        ]);
        
        $bookId = $pdo->lastInsertId();
        
        // Dodaj autorów
        foreach ($bookData['authors'] as $author) {
            $authorId = addOrUpdateAuthor($author['first_name'], $author['last_name']);
            if ($authorId) {
                $stmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
                $stmt->execute([$bookId, $authorId]);
            }
        }
        
        $pdo->commit();
        return $bookId;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} 