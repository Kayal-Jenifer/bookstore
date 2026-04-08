<?php
include 'includes/db.php';
include 'includes/auth.php';

$redirectTarget = normalizeRedirectTarget($_GET["redirect"] ?? $_POST["redirect"] ?? "index.php");
$errorMessage = "";

if (isUserLoggedIn()) {
    redirectTo($redirectTarget !== "" ? $redirectTarget : "index.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $errorMessage = "Please enter your email and password.";
    } else {
        $userQuery = $conn->prepare("SELECT user_id, full_name, email, phone, password_hash FROM users WHERE email = ? LIMIT 1");
        $userQuery->bind_param("s", $email);
        $userQuery->execute();
        $result = $userQuery->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $userQuery->close();

        if ($user && password_verify($password, $user["password_hash"])) {
            logInUser($user);
            redirectTo($redirectTarget !== "" ? $redirectTarget : "index.php");
        }

        $errorMessage = "Invalid email or password.";
    }
}

include 'includes/header.php';
?>

<main class="sectionSpace">
    <div class="container">
        <section class="adminLoginCard mx-auto">
            <p class="adminKicker">User access</p>
            <h1 class="sectionTitle text-start mb-3">Sign In</h1>
            <p class="text-muted mb-4">Sign in to place orders and manage your customer account details.</p>

            <?php if ($errorMessage !== ""): ?>
                <div class="alert alert-danger adminAlert mb-4" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="adminFormGrid">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget); ?>">

                <label class="adminField">
                    <span>Email</span>
                    <input type="email" name="email" class="form-control" required>
                </label>

                <label class="adminField">
                    <span>Password</span>
                    <input type="password" name="password" class="form-control" required>
                </label>

                <button type="submit" class="btn btn-warning rounded-pill px-4">Sign In</button>
            </form>

            <p class="authSwitchLink mt-4 mb-0">New here? <a href="signup.php<?php echo $redirectTarget !== "" ? '?redirect=' . urlencode($redirectTarget) : ''; ?>">Create an account</a>.</p>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
