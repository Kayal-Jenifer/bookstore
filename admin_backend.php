<?php
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/admin_auth.php';

ensureDefaultAdmin($conn);
requireAdminLogin("admin-login.php");

function redirectToAdminSection(string $section): void
{
    header("Location: admin.php?section=" . urlencode($section) . "#" . urlencode($section));
    exit;
}

function setAdminFlash(string $type, string $message): void
{
    $_SESSION["admin_flash"] = [
        "type" => $type,
        "message" => $message
    ];
}

function normalizeDateOrNull(?string $value): ?string
{
    $value = trim((string) $value);

    return $value === "" ? null : $value;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectTo("admin.php");
}

$activeSection = $_POST["section"] ?? "authors";
$allowedSections = ["authors", "books", "customers", "orders"];

if (!in_array($activeSection, $allowedSections, true)) {
    $activeSection = "authors";
}

$action = $_POST["action"] ?? "";

if ($action === "logout") {
    logOutAdmin();
    setAdminFlash("success", "You have been logged out.");
    redirectTo("admin-login.php");
}

try {
    switch ($action) {
        case "save_author":
            $authorId = (int) ($_POST["author_id"] ?? 0);
            $name = trim($_POST["name"] ?? "");
            $dob = normalizeDateOrNull($_POST["dob"] ?? null);
            $nationality = trim($_POST["nationality"] ?? "");

            if ($name === "" || $nationality === "") {
                throw new RuntimeException("Author name and nationality are required.");
            }

            if ($authorId > 0) {
                $statement = $conn->prepare("UPDATE authors SET name = ?, dob = ?, nationality = ? WHERE author_id = ?");
                $statement->bind_param("sssi", $name, $dob, $nationality, $authorId);
                $statement->execute();
                $statement->close();
                setAdminFlash("success", "Author updated successfully.");
            } else {
                $statement = $conn->prepare("INSERT INTO authors (name, dob, nationality) VALUES (?, ?, ?)");
                $statement->bind_param("sss", $name, $dob, $nationality);
                $statement->execute();
                $statement->close();
                setAdminFlash("success", "Author created successfully.");
            }

            redirectToAdminSection("authors");
            break;

        case "delete_author":
            $authorId = (int) ($_POST["author_id"] ?? 0);
            $conn->begin_transaction();

            $statement = $conn->prepare(
                "DELETE order_items
                FROM order_items
                INNER JOIN books ON books.book_id = order_items.book_id
                WHERE books.author_id = ?"
            );
            $statement->bind_param("i", $authorId);
            $statement->execute();
            $statement->close();

            $statement = $conn->prepare("DELETE FROM books WHERE author_id = ?");
            $statement->bind_param("i", $authorId);
            $statement->execute();
            $statement->close();

            $statement = $conn->prepare("DELETE FROM authors WHERE author_id = ?");
            $statement->bind_param("i", $authorId);
            $statement->execute();
            $statement->close();

            $conn->commit();
            setAdminFlash("success", "Author deleted successfully.");
            redirectToAdminSection("authors");
            break;

        case "save_book":
            $bookId = (int) ($_POST["book_id"] ?? 0);
            $title = trim($_POST["title"] ?? "");
            $isbn = trim($_POST["isbn"] ?? "");
            $genre = trim($_POST["genre"] ?? "");
            $price = (float) ($_POST["price"] ?? 0);
            $stock = (int) ($_POST["stock"] ?? 0);
            $authorId = (int) ($_POST["author_id"] ?? 0);
            $publisher = trim($_POST["publisher"] ?? "");

            if ($title === "" || $isbn === "" || $genre === "" || $publisher === "" || $authorId <= 0) {
                throw new RuntimeException("All book fields are required.");
            }

            if ($bookId > 0) {
                $statement = $conn->prepare("UPDATE books SET title = ?, isbn = ?, genre = ?, price = ?, stock = ?, author_id = ?, publisher = ? WHERE book_id = ?");
                $statement->bind_param("sssdiisi", $title, $isbn, $genre, $price, $stock, $authorId, $publisher, $bookId);
                $statement->execute();
                $statement->close();
                setAdminFlash("success", "Book updated successfully.");
            } else {
                $statement = $conn->prepare("INSERT INTO books (title, isbn, genre, price, stock, author_id, publisher) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $statement->bind_param("sssdiis", $title, $isbn, $genre, $price, $stock, $authorId, $publisher);
                $statement->execute();
                $statement->close();
                setAdminFlash("success", "Book created successfully.");
            }

            redirectToAdminSection("books");
            break;

        case "delete_book":
            $bookId = (int) ($_POST["book_id"] ?? 0);
            $conn->begin_transaction();

            $statement = $conn->prepare("DELETE FROM order_items WHERE book_id = ?");
            $statement->bind_param("i", $bookId);
            $statement->execute();
            $statement->close();

            $statement = $conn->prepare("DELETE FROM books WHERE book_id = ?");
            $statement->bind_param("i", $bookId);
            $statement->execute();
            $statement->close();

            $conn->commit();
            setAdminFlash("success", "Book deleted successfully.");
            redirectToAdminSection("books");
            break;

        case "save_customer":
            $customerId = (int) ($_POST["customer_id"] ?? 0);
            $name = trim($_POST["name"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $phone = trim($_POST["phone"] ?? "");

            if ($name === "" || $email === "" || $phone === "") {
                throw new RuntimeException("Customer name, email, and phone are required.");
            }

            if ($customerId > 0) {
                $statement = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE customer_id = ?");
                $statement->bind_param("sssi", $name, $email, $phone, $customerId);
                $statement->execute();
                $statement->close();
                setAdminFlash("success", "Customer updated successfully.");
            } else {
                $statement = $conn->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
                $statement->bind_param("sss", $name, $email, $phone);
                $statement->execute();
                $statement->close();
                setAdminFlash("success", "Customer created successfully.");
            }

            redirectToAdminSection("customers");
            break;

        case "delete_customer":
            $customerId = (int) ($_POST["customer_id"] ?? 0);
            $statement = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
            $statement->bind_param("i", $customerId);
            $statement->execute();
            $statement->close();
            setAdminFlash("success", "Customer deleted successfully.");
            redirectToAdminSection("customers");
            break;

        case "save_order":
            $orderId = (int) ($_POST["order_id"] ?? 0);
            $customerId = (int) ($_POST["customer_id"] ?? 0);
            $orderDate = trim($_POST["order_date"] ?? "");
            $totalAmount = (float) ($_POST["total_amount"] ?? 0);
            $notes = trim($_POST["notes"] ?? "");

            if ($customerId <= 0 || $orderDate === "") {
                throw new RuntimeException("Customer and order date are required.");
            }

            if ($orderId > 0) {
                $statement = $conn->prepare("UPDATE orders SET customer_id = ?, order_date = ?, total_amount = ?, notes = ? WHERE order_id = ?");
                $statement->bind_param("isdsi", $customerId, $orderDate, $totalAmount, $notes, $orderId);
                $statement->execute();
                $statement->close();
                setAdminFlash("success", "Order updated successfully.");
            } else {
                $statement = $conn->prepare("INSERT INTO orders (customer_id, order_date, total_amount, notes) VALUES (?, ?, ?, ?)");
                $statement->bind_param("isds", $customerId, $orderDate, $totalAmount, $notes);
                $statement->execute();
                $statement->close();
                setAdminFlash("success", "Order created successfully.");
            }

            redirectToAdminSection("orders");
            break;

        case "delete_order":
            $orderId = (int) ($_POST["order_id"] ?? 0);
            $statement = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
            $statement->bind_param("i", $orderId);
            $statement->execute();
            $statement->close();
            setAdminFlash("success", "Order deleted successfully.");
            redirectToAdminSection("orders");
            break;

        default:
            throw new RuntimeException("Unknown admin action.");
    }
} catch (Throwable $exception) {
    try {
        $conn->rollback();
    } catch (Throwable $rollbackException) {
        // Ignore rollback errors and report the original exception.
    }

    $message = $exception->getMessage();

    if (str_contains($message, "Duplicate entry")) {
        $message = "That record already exists. Please use a unique value.";
    } elseif (str_contains($message, "foreign key constraint fails")) {
        $message = "This record is linked to other data and cannot be deleted yet.";
    }

    setAdminFlash("error", $message);
    redirectToAdminSection($activeSection);
}
?>
