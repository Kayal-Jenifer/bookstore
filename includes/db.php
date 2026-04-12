<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "bookstoreManagementDb";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->query(
    "CREATE DATABASE IF NOT EXISTS `$database`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci"
);

if (!$conn->select_db($database)) {
    die("Unable to select database: " . $conn->error);
}

$conn->set_charset("utf8mb4");

$schemaStatements = [
    "CREATE TABLE IF NOT EXISTS authors (
        author_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        dob DATE NULL,
        nationality VARCHAR(100) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS books (
        book_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        isbn VARCHAR(30) NOT NULL UNIQUE,
        genre VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        author_id INT NOT NULL,
        publisher VARCHAR(120) NOT NULL,
        cover_image VARCHAR(255) NULL,
        FOREIGN KEY (author_id) REFERENCES authors(author_id)
            ON UPDATE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS customers (
        customer_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(120) NOT NULL,
        phone VARCHAR(20) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(120) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        total_amount DECIMAL(10,2) NOT NULL,
        notes TEXT NULL,
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
            ON UPDATE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        book_id INT NOT NULL,
        quantity INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(order_id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(book_id)
            ON UPDATE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS admin_users (
        admin_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($schemaStatements as $statement) {
    if (!$conn->query($statement)) {
        die("Database setup failed: " . $conn->error);
    }
}

$ordersNotesColumn = $conn->query("SHOW COLUMNS FROM orders LIKE 'notes'");
if ($ordersNotesColumn && $ordersNotesColumn->num_rows === 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER total_amount");
}

$bookCoverColumn = $conn->query("SHOW COLUMNS FROM books LIKE 'cover_image'");
if ($bookCoverColumn && $bookCoverColumn->num_rows === 0) {
    $conn->query("ALTER TABLE books ADD COLUMN cover_image VARCHAR(255) NULL AFTER publisher");
}
?>
