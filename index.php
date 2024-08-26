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

// Fetch counts for each section
$ticket_count = $db->query("SELECT COUNT(*) FROM tickets")->fetch_row()[0];
$user_count = $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$product_count = $db->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$team_count = $db->query("SELECT COUNT(*) FROM teams")->fetch_row()[0];

// Fetch open ticket count for the logged-in user
$open_ticket_count_stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE status = 'Open' AND assigned_to = ?");
$open_ticket_count_stmt->bind_param('i', $user_id);
$open_ticket_count_stmt->execute();
$open_ticket_count_stmt->bind_result($open_ticket_count);
$open_ticket_count_stmt->fetch();
$open_ticket_count_stmt->close();

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #1C1C1C;
            color: #E0E0E0;
            margin: 0;
            overflow-x: hidden;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #333333;
            color: #E0E0E0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
            color: #00BFA5;
        }

        .navbar .nav-links {
            display: flex;
            align-items: center;
        }

        .navbar .nav-links a {
            color: #E0E0E0;
            text-decoration: none;
            margin: 0 15px;
            font-size: 16px;
            transition: color 0.3s;
        }

        .navbar .nav-links a:hover {
            color: #FFD700;
        }

        .navbar .search-bar {
            position: relative;
        }

        .navbar .search-bar input {
            padding: 5px 10px;
            border-radius: 20px;
            border: 1px solid #555;
            background: rgba(68, 68, 68, 0.8);
            color: #E0E0E0;
            outline: none;
            transition: width 0.3s ease-in-out;
            width: 150px;
        }

        .navbar .search-bar input:focus {
            width: 200px;
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            margin-left: 20px;
            cursor: pointer;
        }

        .navbar .user-info i {
            font-size: 24px;
            color: #FFD700;
        }

        .dashboard-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        .dashboard-container h1 {
            font-size: 36px;
            margin-bottom: 20px;
            color: #00BFA5;
            animation: fadeIn 2s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            width: 100%;
            max-width: 1200px;
        }

        .card-row {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 20px;
        }

        .card {
            background: linear-gradient(145deg, #444, #555);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 30%;
            margin: 10px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: #E0E0E0;
            animation: slideUp 1s ease-in-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.4);
        }

        .card i {
            font-size: 48px;
            color: #00BFA5;
            margin-bottom: 10px;
        }

        .card h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 16px;
            color: #B0B0B0;
        }

        .footer {
            margin-top: 50px;
            padding: 10px 20px;
            background: #333333;
            color: #E0E0E0;
            text-align: center;
            width: 100%;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            bottom: 0;
        }
    </style>
</head>
<body>
    <?php include 'assets/header.php'; ?>
    <div class="dashboard-container">
        <h1>Welcome to the Dashboard</h1>
        <div class="cards">
            <div class="card-row">
                <a href="tickets.php" class="card">
                    <i class="fas fa-ticket-alt"></i>
                    <h3>Tickets</h3>
                    <p>Manage your tickets</p>
                    <p>Total: <?php echo $ticket_count; ?></p>
                </a>
                <a href="tickets.php?filter=open" class="card">
                    <i class="fas fa-user-tag"></i>
                    <h3>Open Tickets</h3>
                    <p>View your open tickets</p>
                    <p>Total: <?php echo $open_ticket_count; ?></p>
                </a>
                <a href="users.php" class="card">
                    <i class="fas fa-users"></i>
                    <h3>Users</h3>
                    <p>Manage system users</p>
                    <p>Total: <?php echo $user_count; ?></p>
                </a>
            </div>
            <div class="card-row">
                <a href="products.php" class="card">
                    <i class="fas fa-box"></i>
                    <h3>Products</h3>
                    <p>Manage your products</p>
                    <p>Total: <?php echo $product_count; ?></p>
                </a>
                <a href="teams.php" class="card">
                    <i class="fas fa-users-cog"></i>
                    <h3>Teams</h3>
                    <p>Manage your teams</p>
                    <p>Total: <?php echo $team_count; ?></p>
                </a>
            </div>
        </div>
    </div>
    <div class="footer">
        &copy; <?php echo date('Y'); ?> Inditech. All rights reserved.
    </div>
</body>
</html>
