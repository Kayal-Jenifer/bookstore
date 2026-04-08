<?php
include 'includes/auth.php';

$role = $_GET["role"] ?? "user";

if ($role === "admin") {
    logOutAdmin();
    redirectTo("admin-login.php");
}

logOutUser();
redirectTo("index.php");
?>
