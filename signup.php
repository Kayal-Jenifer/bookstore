<?php
include 'includes/db.php';
include 'includes/auth.php';

$redirectTarget = normalizeRedirectTarget($_GET["redirect"] ?? $_POST["redirect"] ?? "index.php");
$errorMessage = "";

if (isUserLoggedIn()) {
    redirectTo($redirectTarget !== "" ? $redirectTarget : "index.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($fullName === "" || $email === "" || $phone === "" || $password === "" || $confirmPassword === "") {
        $errorMessage = "Please complete all sign-up fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $errorMessage = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirmPassword) {
        $errorMessage = "Passwords do not match.";
    } else {
        $existingUser = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $existingUser->bind_param("s", $email);
        $existingUser->execute();
        $existingResult = $existingUser->get_result();
        $emailTaken = $existingResult && $existingResult->num_rows > 0;
        $existingUser->close();

        if ($emailTaken) {
            $errorMessage = "An account with that email already exists.";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertUser = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
            $insertUser->bind_param("ssss", $fullName, $email, $phone, $passwordHash);
            $insertUser->execute();
            $userId = $insertUser->insert_id;
            $insertUser->close();

            logInUser([
                "user_id" => $userId,
                "full_name" => $fullName,
                "email" => $email,
                "phone" => $phone
            ]);

            redirectTo($redirectTarget !== "" ? $redirectTarget : "index.php");
        }
    }
}

include 'includes/header.php';
?>

<main class="sectionSpace">
    <div class="container">
        <section class="adminLoginCard mx-auto">
            <p class="adminKicker">Customer account</p>
            <h1 class="sectionTitle text-start mb-3">Create Account</h1>
            <p class="text-muted mb-4">Sign up as a user to browse the store and place book orders.</p>

            <?php if ($errorMessage !== ""): ?>
                <div class="alert alert-danger adminAlert mb-4" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="adminFormGrid">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget); ?>">

                <label class="adminField">
                    <span>Full name</span>
                    <input type="text" name="full_name" class="form-control" required>
                </label>

                <label class="adminField">
                    <span>Email</span>
                    <input type="email" name="email" class="form-control" required>
                </label>

                <label class="adminField">
                    <span>Phone</span>
                    <input type="text" name="phone" class="form-control" required>
                </label>

                <label class="adminField">
                    <span>Password</span>
                    <input type="password" name="password" class="form-control" required>
                </label>

                <label class="adminField">
                    <span>Confirm password</span>
                    <input type="password" name="confirm_password" class="form-control" required>
                </label>

                <button type="submit" class="btn btn-warning rounded-pill px-4">Sign Up</button>
            </form>

            <p class="authSwitchLink mt-4 mb-0">Already have an account? <a href="signin.php<?php echo $redirectTarget !== "" ? '?redirect=' . urlencode($redirectTarget) : ''; ?>">Sign in</a>.</p>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
