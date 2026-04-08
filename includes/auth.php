<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirectTo(string $path): void
{
    header("Location: " . $path);
    exit;
}

function isUserLoggedIn(): bool
{
    return !empty($_SESSION["user_logged_in"]);
}

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION["admin_logged_in"]);
}

function getCurrentUser(): ?array
{
    if (!isUserLoggedIn()) {
        return null;
    }

    return [
        "user_id" => (int) ($_SESSION["user_id"] ?? 0),
        "full_name" => (string) ($_SESSION["user_full_name"] ?? ""),
        "email" => (string) ($_SESSION["user_email"] ?? ""),
        "phone" => (string) ($_SESSION["user_phone"] ?? "")
    ];
}

function logInUser(array $user): void
{
    $_SESSION["user_logged_in"] = true;
    $_SESSION["user_id"] = (int) $user["user_id"];
    $_SESSION["user_full_name"] = (string) $user["full_name"];
    $_SESSION["user_email"] = (string) $user["email"];
    $_SESSION["user_phone"] = (string) ($user["phone"] ?? "");
}

function logOutUser(): void
{
    unset(
        $_SESSION["user_logged_in"],
        $_SESSION["user_id"],
        $_SESSION["user_full_name"],
        $_SESSION["user_email"],
        $_SESSION["user_phone"]
    );
}

function logInAdmin(array $admin): void
{
    $_SESSION["admin_logged_in"] = true;
    $_SESSION["admin_username"] = (string) $admin["username"];
}

function logOutAdmin(): void
{
    unset($_SESSION["admin_logged_in"], $_SESSION["admin_username"]);
}

function buildLoginRedirect(string $loginPage): string
{
    $requestUri = $_SERVER["REQUEST_URI"] ?? "";

    if ($requestUri === "") {
        return $loginPage;
    }

    return $loginPage . "?redirect=" . urlencode($requestUri);
}

function normalizeRedirectTarget(?string $target, string $default = "index.php"): string
{
    $target = trim((string) $target);

    if ($target === "" || str_contains($target, "://") || str_starts_with($target, "//")) {
        return $default;
    }

    return $target;
}

function requireUserLogin(string $loginPage = "signin.php"): void
{
    if (!isUserLoggedIn()) {
        redirectTo(buildLoginRedirect($loginPage));
    }
}

function requireUserOrAdmin(string $loginPage = "signin.php"): void
{
    if (!isUserLoggedIn() && !isAdminLoggedIn()) {
        redirectTo(buildLoginRedirect($loginPage));
    }
}

function requireAdminLogin(string $loginPage = "admin-login.php"): void
{
    if (!isAdminLoggedIn()) {
        redirectTo(buildLoginRedirect($loginPage));
    }
}
?>
