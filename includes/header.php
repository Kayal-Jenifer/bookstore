<?php
include_once __DIR__ . '/auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$isAdminLoggedIn = isAdminLoggedIn();
$isUserLoggedIn = isUserLoggedIn();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toronto Book Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=10">
</head>
<body>
<header class="siteHeader shadow-sm sticky-top">
    <nav class="navbar navbar-expand-lg customNavbar">
        <div class="container">
            <a class="navbar-brand brandLogo" href="index.php">Toronto Book Store</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link <?php echo $currentPage == 'index.php' ? 'activeLink' : ''; ?>" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $currentPage == 'books.php' ? 'activeLink' : ''; ?>" href="books.php">Books</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $currentPage == 'about.php' ? 'activeLink' : ''; ?>" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $currentPage == 'contact.php' ? 'activeLink' : ''; ?>" href="contact.php">Contact</a></li>
                    <?php if ($isUserLoggedIn && $currentUser): ?>
                        <li class="nav-item"><span class="nav-link userGreeting">Hi, <?php echo htmlspecialchars($currentUser["full_name"]); ?></span></li>
                        <li class="nav-item"><a class="btn btn-outline-dark rounded-pill px-4" href="logout.php">Sign Out</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-outline-dark rounded-pill px-4 <?php echo $currentPage == 'signin.php' ? 'activeLink' : ''; ?>" href="signin.php">Sign In</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="btn btn-dark rounded-pill px-4" href="<?php echo $isAdminLoggedIn ? 'admin.php' : 'admin-login.php'; ?>"><?php echo $isAdminLoggedIn ? 'Admin Dashboard' : 'Admin Sign In'; ?></a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>   
