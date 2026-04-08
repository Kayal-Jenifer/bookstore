<?php
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/admin_auth.php';

ensureDefaultAdmin($conn);

$redirectTarget = normalizeRedirectTarget($_GET["redirect"] ?? $_POST["redirect"] ?? "admin.php", "admin.php");
$errorMessage = "";

if (isAdminLoggedIn()) {
    redirectTo($redirectTarget !== "" ? $redirectTarget : "admin.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $errorMessage = "Please enter your admin username and password.";
    } else {
        $adminQuery = $conn->prepare("SELECT admin_id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1");
        $adminQuery->bind_param("s", $username);
        $adminQuery->execute();
        $result = $adminQuery->get_result();
        $admin = $result ? $result->fetch_assoc() : null;
        $adminQuery->close();

        if ($admin && password_verify($password, $admin["password_hash"])) {
            logInAdmin($admin);
            redirectTo($redirectTarget !== "" ? $redirectTarget : "admin.php");
        }

        $errorMessage = "Invalid admin username or password.";
    }
}

include 'includes/header.php';
?>

<main class="sectionSpace">
    <div class="container">
        <section class="adminLoginCard mx-auto">
            <p class="adminKicker">Protected admin area</p>
            <h1 class="sectionTitle text-start mb-3">Admin Sign In</h1>
            <p class="text-muted mb-4">Only administrators can access the CRUD dashboard for authors, books, customers, and orders.</p>

            <?php if ($errorMessage !== ""): ?>
                <div class="alert alert-danger adminAlert mb-4" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="adminFormGrid">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget); ?>">

                <label class="adminField">
                    <span>Username</span>
                    <input type="text" name="username" class="form-control" required>
                </label>

                <label class="adminField">
                    <span>Password</span>
                    <input type="password" name="password" class="form-control" required>
                </label>

                <button type="submit" class="btn btn-warning rounded-pill px-4">Admin Sign In</button>
            </form>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
