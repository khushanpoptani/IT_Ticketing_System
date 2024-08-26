<?php
session_start();
require_once 'assets/config.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Fetch the logged-in user's email
$user_email = $_SESSION['user'];
$db = getDB();

// Fetch the logged-in user's role
$stmt = $db->prepare("SELECT role FROM users WHERE email = ?");
$stmt->bind_param('s', $user_email);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();

$is_admin = ($user_role === 'Admin');
$is_manager = ($user_role === 'Manager');

// Fetch the user details to be viewed
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $stmt = $db->prepare("SELECT id, name, email, role, manager_id FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($id, $name, $email, $role, $manager_id);
    $stmt->fetch();
    $stmt->close();
} else {
    header('Location: users.php');
    exit();
}

// Fetch available managers for display
$manager_name = 'No Manager';
if ($manager_id != 0) {
    $manager_stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $manager_stmt->bind_param('i', $manager_id);
    $manager_stmt->execute();
    $manager_stmt->bind_result($manager_name);
    $manager_stmt->fetch();
    $manager_stmt->close();
}

$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #1C1C1C;
            color: #E0E0E0;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 20px;
            max-width: 600px;
            margin: 50px auto;
            background: #333;
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #00BFA5;
        }
        .user-details {
            display: flex;
            flex-direction: column;
        }
        .user-details label {
            margin-bottom: 10px;
            font-weight: bold;
        }
        .user-details p {
            padding: 10px;
            margin-bottom: 20px;
            background: #444;
            border-radius: 5px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #00BFA5;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Details</h1>
        <div class="user-details">
            <label for="name">Name:</label>
            <p id="name"><?php echo htmlspecialchars($name); ?></p>
            
            <label for="email">Email:</label>
            <p id="email"><?php echo htmlspecialchars($email); ?></p>
            
            <label for="role">Role:</label>
            <p id="role"><?php echo htmlspecialchars($role); ?></p>
            
            <label for="manager">Manager:</label>
            <p id="manager"><?php echo htmlspecialchars($manager_name); ?></p>
        </div>
        <div class="back-link">
            <a href="users.php">Back to Users List</a>
        </div>
    </div>
</body>
</html>
