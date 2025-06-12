-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS domowa_biblioteka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE domowa_biblioteka;

-- Tabela użytkowników
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela wydawców
CREATE TABLE publishers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela autorów
CREATE TABLE authors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_author (first_name, last_name)
);

-- Tabela książek
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    isbn VARCHAR(13),
    title VARCHAR(255) NOT NULL,
    publisher_id INT,
    publication_year INT,
    description TEXT,
    cover_image VARCHAR(255),
    format ENUM('hardcover', 'paperback', 'ebook', 'audiobook') NOT NULL,
    added_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id),
    FOREIGN KEY (added_by) REFERENCES users(id)
);

-- Tabela powiązań książek z autorami (relacja many-to-many)
CREATE TABLE book_authors (
    book_id INT NOT NULL,
    author_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (book_id, author_id),
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (author_id) REFERENCES authors(id)
);

-- Tabela statusów czytania
CREATE TABLE reading_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('to_read', 'reading', 'read') NOT NULL,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela ocen i notatek
CREATE TABLE book_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    rating DECIMAL(2,1) CHECK (rating >= 0 AND rating <= 5),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela wirtualnych półek
CREATE TABLE shelves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela powiązań książek z półkami
CREATE TABLE shelf_books (
    shelf_id INT NOT NULL,
    book_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (shelf_id, book_id),
    FOREIGN KEY (shelf_id) REFERENCES shelves(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Przykładowi użytkownicy
INSERT INTO users (username, email, password_hash) VALUES
('janek', 'janek@example.com', '$2y$10$examplehash1'),
('ania', 'ania@example.com', '$2y$10$examplehash2');

-- Przykładowi wydawcy
INSERT INTO publishers (name) VALUES
('Wydawnictwo Literackie'),
('Penguin Books');

-- Przykładowi autorzy
INSERT INTO authors (first_name, last_name) VALUES
('J.K.', 'Rowling'),
('George', 'Orwell'),
('Adam', 'Mickiewicz');

-- Przykładowe książki
INSERT INTO books (isbn, title, publisher_id, publication_year, description, cover_image, format, added_by)
VALUES
('9780747532743', 'Harry Potter and the Philosopher\'s Stone', 1, 1997, 'Pierwsza część przygód Harry\'ego Pottera.', NULL, 'hardcover', 1),
('9780141036137', '1984', 2, 1949, 'Dystopijna powieść o totalitarnym państwie.', NULL, 'paperback', 2),
('9788373271890', 'Pan Tadeusz', 1, 1834, 'Epopeja narodowa.', NULL, 'ebook', 1);

-- Powiązania książek z autorami
INSERT INTO book_authors (book_id, author_id) VALUES
(1, 1), -- Harry Potter - Rowling
(2, 2), -- 1984 - Orwell
(3, 3); -- Pan Tadeusz - Mickiewicz

-- Przykładowe statusy czytania
INSERT INTO reading_status (book_id, user_id, status, start_date, end_date) VALUES
(1, 1, 'read', '2023-01-01', '2023-01-15'),
(2, 2, 'reading', '2023-02-01', NULL),
(3, 1, 'to_read', NULL, NULL);

-- Przykładowe notatki/oceny
INSERT INTO book_notes (book_id, user_id, rating, notes) VALUES
(1, 1, 5.0, 'Świetna książka!'),
(2, 2, 4.5, 'Klasyka, warto przeczytać.');

-- Przykładowe półki
INSERT INTO shelves (name, description, user_id) VALUES
('Ulubione', 'Moje ulubione książki', 1),
('Do przeczytania', 'Książki na przyszłość', 2);

-- Przykładowe powiązania książek z półkami
INSERT INTO shelf_books (shelf_id, book_id) VALUES
(1, 1),
(1, 3),
(2, 2);