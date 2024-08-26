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
$stmt = $db->prepare("SELECT role FROM users WHERE email = ?");
$stmt->bind_param('s', $user_email);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();

// Check if the user is an admin
if ($user_role !== 'Admin') {
    header('Location: teams.php');
    exit();
}

// Check if the team ID is provided and valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $team_id = $_GET['id'];

    // Delete the team from the database
    $stmt = $db->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->bind_param('i', $team_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to the teams page
    header('Location: teams.php');
    exit();
} else {
    // Redirect to the teams page if the team ID is not valid
    header('Location: teams.php');
    exit();
}
?>
