<?php
include_once __DIR__ . '/auth.php';

function ensureDefaultAdmin(mysqli $conn): void
{
    $defaultUsername = "new_admin";
    $defaultPasswordHash = 'new_admin123';

    $checkAdmin = $conn->prepare("SELECT admin_id FROM admin_users WHERE username = ? LIMIT 1");
    $checkAdmin->bind_param("s", $defaultUsername);
    $checkAdmin->execute();
    $result = $checkAdmin->get_result();
    $adminExists = $result && $result->num_rows > 0;
    $checkAdmin->close();

    if ($adminExists) {
        return;
    }

    $insertAdmin = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
    $insertAdmin->bind_param("ss", $defaultUsername, $defaultPasswordHash);
    $insertAdmin->execute();
    $insertAdmin->close();
}

?>
