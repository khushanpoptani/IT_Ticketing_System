<?php
session_start();
require_once 'assets/config.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Fetch user information
$user_email = $_SESSION['user'];
$db = getDB();
$stmt = $db->prepare("SELECT id, name, role FROM users WHERE email = ?");
$stmt->bind_param('s', $user_email);
$stmt->execute();
$stmt->bind_result($user_id, $user_name, $user_role);
$stmt->fetch();
$stmt->close();

// Check if the user is an admin or manager
$is_admin = ($user_role === 'Admin');
$is_manager = ($user_role === 'Manager');

if (!$is_admin && !$is_manager) {
    header('Location: products.php');
    exit();
}

// Delete product and its sub-products
$product_id = $_GET['id'];
$stmt = $db->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->close();

header('Location: products.php');
exit();
?>
