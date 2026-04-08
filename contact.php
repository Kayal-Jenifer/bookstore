<?php
include 'includes/db.php';
include 'includes/auth.php';

requireUserOrAdmin("signin.php");

$currentUser = getCurrentUser();
$isAdminSession = isAdminLoggedIn();

$booksResult = $conn->query(
    "SELECT books.book_id, books.title, books.price, books.stock, authors.name AS author_name
    FROM books
    INNER JOIN authors ON authors.author_id = books.author_id
    ORDER BY books.title ASC"
);
$books = $booksResult ? $booksResult->fetch_all(MYSQLI_ASSOC) : [];

$selectedBookId = (int) ($_GET["book_id"] ?? 0);
$successMessage = $_GET["order"] ?? "" ? "Your order was submitted successfully." : "";
$errorMessage = trim($_GET["error"] ?? "");

include 'includes/header.php';
?>

<main class="sectionSpace">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="sectionTitle">Order a Book</h1>
            <p class="sectionSubtitle">Choose a book from our catalog, enter your details, and we will record the order for the admin dashboard right away.</p>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert alert-success mx-auto adminAlert" role="alert">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ""): ?>
            <div class="alert alert-danger mx-auto adminAlert" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="formWrapper mx-auto">
            <?php if (!$books): ?>
                <div class="emptyBooksState">
                    <h3>No books are available to order yet.</h3>
                    <p class="mb-0">Please add books from the admin dashboard first.</p>
                </div>
            <?php else: ?>
                <form id="reservationForm" action="save_reservation.php" method="POST" novalidate>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo htmlspecialchars($currentUser["full_name"] ?? ""); ?>" required>
                            <small class="errorText"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($currentUser["email"] ?? ""); ?>" <?php echo $currentUser ? 'readonly' : ''; ?> required>
                            <small class="errorText"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($currentUser["phone"] ?? ""); ?>" required>
                            <small class="errorText"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="bookId" class="form-label">Book</label>
                            <select class="form-select" id="bookId" name="bookId" required>
                                <option value="">Select a book</option>
                                <?php foreach ($books as $book): ?>
                                    <option value="<?php echo (int) $book["book_id"]; ?>" <?php echo $selectedBookId === (int) $book["book_id"] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($book["title"] . " by " . $book["author_name"] . " - $" . number_format((float) $book["price"], 2)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="errorText"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                            <small class="errorText"></small>
                        </div>
                        <div class="col-12">
                            <label for="additionalComments" class="form-label">Additional comments</label>
                            <textarea class="form-control" id="additionalComments" name="additionalComments" rows="4" placeholder="Pickup timing, special request, or delivery notes"></textarea>
                        </div>
                        <?php if ($isAdminSession && !$currentUser): ?>
                            <div class="col-12">
                                <div class="alert alert-warning mb-0" role="alert">
                                    You are ordering as an admin session. Enter the customer details you want attached to this order.
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-warning btn-lg rounded-pill px-5">Submit Order</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
