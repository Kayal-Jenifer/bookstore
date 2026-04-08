<?php
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/admin_auth.php';

ensureDefaultAdmin($conn);
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

function renderAlertClass(string $type): string
{
    return $type === "success" ? "alert-success" : "alert-danger";
}


$activeSection = $_GET["section"] ?? "authors";
$allowedSections = ["authors", "books", "customers", "orders"];

if (!in_array($activeSection, $allowedSections, true)) {
    $activeSection = "authors";
}

requireAdminLogin("admin-login.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";   
    $section = $_POST["section"] ?? $activeSection;


//   Handle create, update, and delete actions for authors, books, customers, and orders
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
        redirectToAdminSection($section);
    }
}

// Fetch data to display in admin sections

$flash = $_SESSION["admin_flash"] ?? null;
unset($_SESSION["admin_flash"]);

$editingAuthor = null;
$editingBook = null;
$editingCustomer = null;
$editingOrder = null;
$authors = [];
$books = [];
$customers = [];
$orders = [];

if (isAdminLoggedIn()) {
    $editingAuthorId = (int) ($_GET["edit_author"] ?? 0);
    $editingBookId = (int) ($_GET["edit_book"] ?? 0);
    $editingCustomerId = (int) ($_GET["edit_customer"] ?? 0);
    $editingOrderId = (int) ($_GET["edit_order"] ?? 0);

    $authorsResult = $conn->query("SELECT * FROM authors ORDER BY name ASC");
    $authors = $authorsResult ? $authorsResult->fetch_all(MYSQLI_ASSOC) : [];

    $booksResult = $conn->query(
        "SELECT books.*, authors.name AS author_name
        FROM books
        INNER JOIN authors ON authors.author_id = books.author_id
        ORDER BY books.title ASC"
    );
    $books = $booksResult ? $booksResult->fetch_all(MYSQLI_ASSOC) : [];

    $customersResult = $conn->query("SELECT * FROM customers ORDER BY name ASC");
    $customers = $customersResult ? $customersResult->fetch_all(MYSQLI_ASSOC) : [];

    $ordersResult = $conn->query(
        "SELECT
            orders.*,
            customers.name AS customer_name,
            GROUP_CONCAT(CONCAT(books.title, ' x', order_items.quantity) ORDER BY books.title SEPARATOR ', ') AS order_books
        FROM orders
        INNER JOIN customers ON customers.customer_id = orders.customer_id
        LEFT JOIN order_items ON order_items.order_id = orders.order_id
        LEFT JOIN books ON books.book_id = order_items.book_id
        GROUP BY orders.order_id, orders.customer_id, orders.order_date, orders.total_amount, orders.notes, customers.name
        ORDER BY orders.order_date DESC"
    );
    $orders = $ordersResult ? $ordersResult->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($authors as $author) {
        if ((int) $author["author_id"] === $editingAuthorId) {
            $editingAuthor = $author;
            break;
        }
    }

    foreach ($books as $book) {
        if ((int) $book["book_id"] === $editingBookId) {
            $editingBook = $book;
            break;
        }
    }

    foreach ($customers as $customer) {
        if ((int) $customer["customer_id"] === $editingCustomerId) {
            $editingCustomer = $customer;
            break;
        }
    }

    foreach ($orders as $order) {
        if ((int) $order["order_id"] === $editingOrderId) {
            $editingOrder = $order;
            break;
        }
    }
}
include 'includes/header.php';
?>

<main class="adminPage py-5">
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert <?php echo renderAlertClass($flash["type"]); ?> adminAlert" role="alert">
                <?php echo htmlspecialchars($flash["message"]); ?>
            </div>
        <?php endif; ?>

        <section class="adminHero mb-4">
            <div>
                <p class="adminKicker">Signed in as <?php echo htmlspecialchars($_SESSION["admin_username"] ?? "admin"); ?></p>
                <h1 class="sectionTitle text-start mb-2">Bookstore Operations Dashboard</h1>
                <p class="mb-0 text-muted">Create, read, update, and delete bookstore data from one protected admin workspace.</p>
            </div>
            <a href="logout.php?role=admin" class="btn btn-outline-dark rounded-pill px-4">Log Out</a>
        </section>

        <nav class="adminTabs mb-4">
            <a class="adminTab <?php echo $activeSection === 'authors' ? 'active' : ''; ?>" href="admin.php?section=authors#authors">Authors</a>
            <a class="adminTab <?php echo $activeSection === 'books' ? 'active' : ''; ?>" href="admin.php?section=books#books">Books</a>
            <a class="adminTab <?php echo $activeSection === 'customers' ? 'active' : ''; ?>" href="admin.php?section=customers#customers">Customers</a>
            <a class="adminTab <?php echo $activeSection === 'orders' ? 'active' : ''; ?>" href="admin.php?section=orders#orders">Orders</a>
        </nav>

        <section id="authors" class="adminSection <?php echo $activeSection === 'authors' ? 'activeSection' : ''; ?>">
                <div class="adminSectionHeader">
                    <div>
                        <h2>Authors</h2>
                        <p>Manage author profiles used by your book catalog.</p>
                    </div>
                </div>
                <div class="adminGrid">
                    <form method="post" class="adminPanel adminFormGrid">
                        <input type="hidden" name="action" value="save_author">
                        <input type="hidden" name="section" value="authors">
                        <input type="hidden" name="author_id" value="<?php echo (int) ($editingAuthor["author_id"] ?? 0); ?>">

                        <h3><?php echo $editingAuthor ? 'Edit Author' : 'Add Author'; ?></h3>

                        <label class="adminField">
                            <span>Name</span>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editingAuthor["name"] ?? ""); ?>" required>
                        </label>

                        <label class="adminField">
                            <span>Date of birth</span>
                            <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($editingAuthor["dob"] ?? ""); ?>">
                        </label>

                        <label class="adminField">
                            <span>Nationality</span>
                            <input type="text" name="nationality" class="form-control" value="<?php echo htmlspecialchars($editingAuthor["nationality"] ?? ""); ?>" required>
                        </label>

                        <div class="adminButtonRow">
                            <button type="submit" class="btn btn-warning rounded-pill px-4"><?php echo $editingAuthor ? 'Update Author' : 'Create Author'; ?></button>
                            <?php if ($editingAuthor): ?>
                                <a href="admin.php?section=authors#authors" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="adminPanel">
                        <h3>Author Records</h3>
                        <div class="table-responsive">
                            <table class="table adminTable align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Birth Date</th>
                                        <th>Nationality</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$authors): ?>
                                        <tr><td colspan="4" class="text-center text-muted">No authors added yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($authors as $author): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($author["name"]); ?></td>
                                            <td><?php echo htmlspecialchars($author["dob"] ?: "N/A"); ?></td>
                                            <td><?php echo htmlspecialchars($author["nationality"]); ?></td>
                                            <td class="adminActionCell">
                                                <a href="admin.php?section=authors&edit_author=<?php echo (int) $author["author_id"]; ?>#authors" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this author?');">
                                                    <input type="hidden" name="action" value="delete_author">
                                                    <input type="hidden" name="section" value="authors">
                                                    <input type="hidden" name="author_id" value="<?php echo (int) $author["author_id"]; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </section>

        <section id="books" class="adminSection <?php echo $activeSection === 'books' ? 'activeSection' : ''; ?>">
                <div class="adminSectionHeader">
                    <div>
                        <h2>Books</h2>
                    </div>
                </div>
                <div class="adminGrid">
                    <form method="post" class="adminPanel adminFormGrid">
                        <input type="hidden" name="action" value="save_book">
                        <input type="hidden" name="section" value="books">
                        <input type="hidden" name="book_id" value="<?php echo (int) ($editingBook["book_id"] ?? 0); ?>">

                        <h3><?php echo $editingBook ? 'Edit Book' : 'Add Book'; ?></h3>

                        <label class="adminField">
                            <span>Title</span>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editingBook["title"] ?? ""); ?>" required>
                        </label>

                        <label class="adminField">
                            <span>ISBN</span>
                            <input type="text" name="isbn" class="form-control" value="<?php echo htmlspecialchars($editingBook["isbn"] ?? ""); ?>" required>
                        </label>

                        <label class="adminField">
                            <span>Genre</span>
                            <input type="text" name="genre" class="form-control" value="<?php echo htmlspecialchars($editingBook["genre"] ?? ""); ?>" placeholder="Kids, Teens, Adults, Fantasy..." required>
                        </label>

                        <label class="adminField">
                            <span>Price</span>
                            <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?php echo htmlspecialchars($editingBook["price"] ?? ""); ?>" required>
                        </label>

                        <label class="adminField">
                            <span>Stock</span>
                            <input type="number" min="0" name="stock" class="form-control" value="<?php echo htmlspecialchars($editingBook["stock"] ?? "0"); ?>" required>
                        </label>

                        <label class="adminField">
                            <span>Author</span>
                            <select name="author_id" class="form-select" required>
                                <option value="">Select an author</option>
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?php echo (int) $author["author_id"]; ?>" <?php echo ((int) ($editingBook["author_id"] ?? 0) === (int) $author["author_id"]) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($author["name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="adminField">
                            <span>Publisher</span>
                            <input type="text" name="publisher" class="form-control" value="<?php echo htmlspecialchars($editingBook["publisher"] ?? ""); ?>" required>
                        </label>

                        <div class="adminButtonRow">
                            <button type="submit" class="btn btn-warning rounded-pill px-4"><?php echo $editingBook ? 'Update Book' : 'Create Book'; ?></button>
                            <?php if ($editingBook): ?>
                                <a href="admin.php?section=books#books" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="adminPanel">
                        <h3>Book Records</h3>
                        <div class="table-responsive">
                            <table class="table adminTable align-middle">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Genre</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$books): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No books added yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($book["title"]); ?></td>
                                            <td><?php echo htmlspecialchars($book["author_name"]); ?></td>
                                            <td><?php echo htmlspecialchars($book["genre"]); ?></td>
                                            <td>$<?php echo number_format((float) $book["price"], 2); ?></td>
                                            <td><?php echo (int) $book["stock"]; ?></td>
                                            <td class="adminActionCell">
                                                <a href="admin.php?section=books&edit_book=<?php echo (int) $book["book_id"]; ?>#books" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this book?');">
                                                    <input type="hidden" name="action" value="delete_book">
                                                    <input type="hidden" name="section" value="books">
                                                    <input type="hidden" name="book_id" value="<?php echo (int) $book["book_id"]; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </section>

            <section id="customers" class="adminSection <?php echo $activeSection === 'customers' ? 'activeSection' : ''; ?>">
                <div class="adminSectionHeader">
                    <div>
                        <h2>Customers</h2>
                        <p>Track customer contact records for orders and follow-ups.</p>
                    </div>
                </div>
                <div class="adminGrid">
                    <form method="post" class="adminPanel adminFormGrid">
                        <input type="hidden" name="action" value="save_customer">
                        <input type="hidden" name="section" value="customers">
                        <input type="hidden" name="customer_id" value="<?php echo (int) ($editingCustomer["customer_id"] ?? 0); ?>">

                        <h3><?php echo $editingCustomer ? 'Edit Customer' : 'Add Customer'; ?></h3>

                        <label class="adminField">
                            <span>Name</span>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editingCustomer["name"] ?? ""); ?>" required>
                        </label>

                        <label class="adminField">
                            <span>Email</span>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editingCustomer["email"] ?? ""); ?>" required>
                        </label>

                        <label class="adminField">
                            <span>Phone</span>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editingCustomer["phone"] ?? ""); ?>" required>
                        </label>

                        <div class="adminButtonRow">
                            <button type="submit" class="btn btn-warning rounded-pill px-4"><?php echo $editingCustomer ? 'Update Customer' : 'Create Customer'; ?></button>
                            <?php if ($editingCustomer): ?>
                                <a href="admin.php?section=customers#customers" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="adminPanel">
                        <h3>Customer Records</h3>
                        <div class="table-responsive">
                            <table class="table adminTable align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$customers): ?>
                                        <tr><td colspan="4" class="text-center text-muted">No customers added yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($customer["name"]); ?></td>
                                            <td><?php echo htmlspecialchars($customer["email"]); ?></td>
                                            <td><?php echo htmlspecialchars($customer["phone"]); ?></td>
                                            <td class="adminActionCell">
                                                <a href="admin.php?section=customers&edit_customer=<?php echo (int) $customer["customer_id"]; ?>#customers" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this customer?');">
                                                    <input type="hidden" name="action" value="delete_customer">
                                                    <input type="hidden" name="section" value="customers">
                                                    <input type="hidden" name="customer_id" value="<?php echo (int) $customer["customer_id"]; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <section id="orders" class="adminSection <?php echo $activeSection === 'orders' ? 'activeSection' : ''; ?>">
                <div class="adminSectionHeader">
                    <div>
                        <h2>Orders</h2>
                        <p>Maintain order totals and their linked customers.</p>
                    </div>
                </div>
                <div class="adminGrid">
                    <form method="post" class="adminPanel adminFormGrid">
                        <input type="hidden" name="action" value="save_order">
                        <input type="hidden" name="section" value="orders">
                        <input type="hidden" name="order_id" value="<?php echo (int) ($editingOrder["order_id"] ?? 0); ?>">

                        <h3><?php echo $editingOrder ? 'Edit Order' : 'Add Order'; ?></h3>

                        <label class="adminField">
                            <span>Customer</span>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Select a customer</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo (int) $customer["customer_id"]; ?>" <?php echo ((int) ($editingOrder["customer_id"] ?? 0) === (int) $customer["customer_id"]) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($customer["name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="adminField">
                            <span>Order date</span>
                            <input type="datetime-local" name="order_date" class="form-control" value="<?php echo htmlspecialchars(isset($editingOrder["order_date"]) ? date('Y-m-d\TH:i', strtotime($editingOrder["order_date"])) : date('Y-m-d\TH:i')); ?>" required>
                        </label>

                        <label class="adminField">
                            <span>Total amount</span>
                            <input type="number" step="0.01" min="0" name="total_amount" class="form-control" value="<?php echo htmlspecialchars($editingOrder["total_amount"] ?? "0.00"); ?>" required>
                        </label>

                        <label class="adminField">
                            <span>Notes</span>
                            <textarea name="notes" class="form-control" rows="4"><?php echo htmlspecialchars($editingOrder["notes"] ?? ""); ?></textarea>
                        </label>

                        <div class="adminButtonRow">
                            <button type="submit" class="btn btn-warning rounded-pill px-4"><?php echo $editingOrder ? 'Update Order' : 'Create Order'; ?></button>
                            <?php if ($editingOrder): ?>
                                <a href="admin.php?section=orders#orders" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="adminPanel">
                        <h3>Order Records</h3>
                        <div class="table-responsive">
                            <table class="table adminTable align-middle">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Books</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$orders): ?>
                                        <tr><td colspan="7" class="text-center text-muted">No orders added yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo (int) $order["order_id"]; ?></td>
                                            <td><?php echo htmlspecialchars($order["customer_name"]); ?></td>
                                            <td><?php echo htmlspecialchars($order["order_books"] ?: "Manual order"); ?></td>
                                            <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($order["order_date"]))); ?></td>
                                            <td>$<?php echo number_format((float) $order["total_amount"], 2); ?></td>
                                            <td><?php echo htmlspecialchars($order["notes"] ?: "No notes"); ?></td>
                                            <td class="adminActionCell">
                                                <a href="admin.php?section=orders&edit_order=<?php echo (int) $order["order_id"]; ?>#orders" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this order?');">
                                                    <input type="hidden" name="action" value="delete_order">
                                                    <input type="hidden" name="section" value="orders">
                                                    <input type="hidden" name="order_id" value="<?php echo (int) $order["order_id"]; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
