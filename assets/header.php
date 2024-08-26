<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once 'assets/config.php';

// Fetch the logged-in user's ID
$user_email = $_SESSION['user'];
$db = getDB();
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $user_email);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #141414;
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .navbar .logo a {
            color: white;
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
        }

        .navbar .nav-links {
            display: flex;
            align-items: center;
        }

        .navbar .nav-links a {
            color: #FFFFFF;
            text-decoration: none;
            margin: 0 15px;
            font-size: 16px;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar .nav-links a:hover {
            color: #FF073A;
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            margin-left: 20px;
            cursor: pointer;
        }

        .navbar .user-info i {
            font-size: 32px; /* Increased size */
            color: #39FF14;
        }

        .navbar .user-info a {
            display: flex;
            align-items: center;
            color: inherit;
            text-decoration: none;
        }

        .navbar .nav-links a i {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">KPDesk</a>
        </div>
        <div class="nav-links">
            <a href="tickets.php"><i class="fas fa-ticket-alt"></i> Tickets</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <a href="products.php"><i class="fas fa-box"></i> Products</a>
            <a href="teams.php"><i class="fas fa-users-cog"></i> Teams</a>
            <div class="user-info">
                <a href="view_user.php?id=<?php echo $user_id; ?>"><i class="fas fa-user-circle"></i></a>
            </div>
        </div>
    </nav>
</body>
</html>
