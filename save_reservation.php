<?php
include 'includes/db.php';
include 'includes/auth.php';

requireUserOrAdmin("signin.php");

function redirectWithError(string $message): void
{
    header("Location: contact.php?error=" . urlencode($message));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contact.php");
    exit;
}

$currentUser = getCurrentUser();
$isUserSession = isUserLoggedIn();
$fullName = trim($_POST["fullName"] ?? ($currentUser["full_name"] ?? ""));
$email = trim($isUserSession ? (string) ($currentUser["email"] ?? "") : ($_POST["email"] ?? ""));
$phone = trim($_POST["phone"] ?? "");
$bookId = (int) ($_POST["bookId"] ?? 0);
$quantity = (int) ($_POST["quantity"] ?? 0);
$additionalComments = trim($_POST["additionalComments"] ?? "");

if ($fullName === "" || $email === "" || $phone === "" || $bookId <= 0 || $quantity <= 0) {
    redirectWithError("Please complete all required order fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithError("Please enter a valid email address.");
}

try {
    $conn->begin_transaction();

    if ($isUserSession && $currentUser) {
        $updateUser = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
        $updateUser->bind_param("ssi", $fullName, $phone, $currentUser["user_id"]);
        $updateUser->execute();
        $updateUser->close();

        $_SESSION["user_full_name"] = $fullName;
        $_SESSION["user_phone"] = $phone;
    }

    $bookLookup = $conn->prepare("SELECT book_id, title, price, stock FROM books WHERE book_id = ? LIMIT 1");
    $bookLookup->bind_param("i", $bookId);
    $bookLookup->execute();
    $bookResult = $bookLookup->get_result();
    $book = $bookResult ? $bookResult->fetch_assoc() : null;
    $bookLookup->close();

    if (!$book) {
        throw new RuntimeException("The selected book could not be found.");
    }

    if ((int) $book["stock"] < $quantity) {
        throw new RuntimeException("Only " . (int) $book["stock"] . " copies of " . $book["title"] . " are available right now.");
    }

    $customerLookup = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? LIMIT 1");
    $customerLookup->bind_param("s", $email);
    $customerLookup->execute();
    $customerResult = $customerLookup->get_result();
    $existingCustomer = $customerResult ? $customerResult->fetch_assoc() : null;
    $customerLookup->close();

    if ($existingCustomer) {
        $customerId = (int) $existingCustomer["customer_id"];
        $updateCustomer = $conn->prepare("UPDATE customers SET name = ?, phone = ? WHERE customer_id = ?");
        $updateCustomer->bind_param("ssi", $fullName, $phone, $customerId);
        $updateCustomer->execute();
        $updateCustomer->close();
    } else {
        $insertCustomer = $conn->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
        $insertCustomer->bind_param("sss", $fullName, $email, $phone);
        $insertCustomer->execute();
        $customerId = $insertCustomer->insert_id;
        $insertCustomer->close();
    }

    $bookPrice = (float) $book["price"];
    $subtotal = $bookPrice * $quantity;

    $insertOrder = $conn->prepare("INSERT INTO orders (customer_id, order_date, total_amount, notes) VALUES (?, NOW(), ?, ?)");
    $insertOrder->bind_param("ids", $customerId, $subtotal, $additionalComments);
    $insertOrder->execute();
    $orderId = $insertOrder->insert_id;
    $insertOrder->close();

    $insertOrderItem = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
    $insertOrderItem->bind_param("iiid", $orderId, $bookId, $quantity, $subtotal);
    $insertOrderItem->execute();
    $insertOrderItem->close();

    $updateStock = $conn->prepare("UPDATE books SET stock = stock - ? WHERE book_id = ?");
    $updateStock->bind_param("ii", $quantity, $bookId);
    $updateStock->execute();
    $updateStock->close();

    $conn->commit();
    header("Location: contact.php?order=success");
    exit;
} catch (Throwable $exception) {
    $conn->rollback();
    redirectWithError($exception->getMessage());
}
?>
