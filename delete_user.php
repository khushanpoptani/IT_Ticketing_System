<?php
session_start();
require_once 'assets/config.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Check if user ID is provided
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Connect to the database
    $db = getDB();

    // Delete the user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
    $db->close();

    // Redirect back to users.php after deletion
    header('Location: users.php');
    exit();
} else {
    // Redirect back to users.php if no user ID is provided
    header('Location: users.php');
    exit();
}
?>
