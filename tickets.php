<?php
session_start();
require_once 'assets/config.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

try {
    $db = getDB();
} catch (Exception $e) {
    die('Database connection error: ' . $e->getMessage());
}

$limit = 5; // Number of tickets per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $stmt = $db->prepare("SELECT t.id, t.title, t.status, t.priority, t.importance, t.expected_deadline, u1.name as raised_by, u2.name as assigned_to
                          FROM tickets t 
                          LEFT JOIN users u1 ON t.raised_by = u1.id
                          LEFT JOIN users u2 ON t.assigned_to = u2.id
                          WHERE t.title LIKE ? 
                          ORDER BY t.created_at DESC
                          LIMIT ?, ?");
    if (!$stmt) {
        throw new Exception($db->error);
    }

    $searchTerm = '%' . $search . '%';
    $stmt->bind_param('sii', $searchTerm, $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception($stmt->error);
    }

    $tickets = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total_stmt = $db->prepare("SELECT COUNT(t.id) as total 
                                FROM tickets t 
                                WHERE t.title LIKE ?");
    if (!$total_stmt) {
        throw new Exception($db->error);
    }

    $total_stmt->bind_param('s', $searchTerm);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    if (!$total_result) {
        throw new Exception($total_stmt->error);
    }

    $total_tickets = $total_result->fetch_assoc()['total'];
    $total_stmt->close();

    $total_pages = ceil($total_tickets / $limit);
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #1C1C1C;
            color: #E0E0E0;
            margin: 0;
            padding: 0;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #333333;
            color: #E0E0E0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: slideDown 1s ease-in-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1);
            }
        }

        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
            color: #00BFA5;
            animation: fadeIn 2s ease-in-out;
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
            transition: color 0.3s, transform 0.3s;
            animation: fadeIn 1.5s ease-in-out;
        }

        .navbar .nav-links a:hover {
            color: #FFD700;
            transform: scale(1.1);
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            margin-left: 20px;
            cursor: pointer;
            animation: fadeIn 2s ease-in-out;
        }

        .navbar .user-info i {
            font-size: 24px;
            color: #FFD700;
            transition: transform 0.3s;
        }

        .navbar .user-info i:hover {
            transform: rotate(360deg);
        }

        .tickets-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .tickets-container h1 {
            text-align: center;
            color: #00BFA5;
            margin-bottom: 20px;
        }

        .create-ticket-button {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .create-ticket-button button {
            background: #00BFA5;
            border: none;
            padding: 10px 20px;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            margin-right: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .create-ticket-button button:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .tickets-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 5px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
        }

        .tickets-table th, .tickets-table td {
            padding: 15px;
            text-align: center;
        }

        .tickets-table th {
            background: #4a4a4a;
            color: #E0E0E0;
        }

        .tickets-table td {
            background: #2e2e2e;
            border-bottom: 1px solid #444;
            cursor: pointer;
        }

        .tickets-table td:hover {
            background: #444;
        }

        .tickets-table tr:nth-child(even) td {
            background: #3a3a3a;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .action-buttons button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #E0E0E0;
            transition: color 0.3s, transform 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #444;
        }

        .action-buttons button:hover {
            color: #FFF;
            transform: scale(1.2);
        }

        .action-buttons .edit-button:hover {
            background: #00796B;
        }

        .action-buttons .delete-button:hover {
            background: #D32F2F;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            color: #E0E0E0;
            text-decoration: none;
            padding: 10px 15px;
            background: #4a4a4a;
            margin: 0 5px;
            border-radius: 5px;
            transition: background 0.3s, transform 0.3s;
        }

        .pagination a:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .pagination a.active {
            background: #00BFA5;
        }

        .ticket-info {
            text-align: center; /* Center-align the text */
            font-size: 14px;
            color: #ccc;
            margin-top: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px; /* Adjust spacing between icons and text */
        }

        .ticket-info i {
            color: #00BFA5;
        }

        .search-bar-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            align-items: center;
        }

        .search-bar {
            display: flex;
            align-items: center;
            position: relative;
        }

        .search-bar input[type="text"] {
            padding: 5px 10px;
            border-radius: 20px;
            border: 1px solid #555;
            background: rgba(68, 68, 68, 0.8);
            color: #E0E0E0;
            outline: none;
            transition: width 0.3s ease-in-out, opacity 0.3s ease-in-out;
            width: 0;
            opacity: 0;
        }

        .search-bar input[type="text"]:focus {
            width: 150px;
            opacity: 1;
        }

        .search-bar i {
            font-size: 18px;
            color: #E0E0E0;
            cursor: pointer;
            transition: color 0.3s, transform 0.3s;
            margin-left: 10px;
        }

        .search-bar i:hover {
            color: #FFD700;
            transform: scale(1.2);
        }

    </style>
</head>
<body>
    <?php include 'assets/header.php'; ?>
    <div class="tickets-container">
        <h1>Tickets</h1>
        <div class="search-bar-container">
            <div class="search-bar">
                <form method="GET" action="tickets.php">
                    <input type="text" name="search" placeholder="Search tickets" id="search-input" value="<?php echo htmlspecialchars($search); ?>">
                    <i class="fas fa-search" id="search-icon"></i>
                </form>
            </div>
            <div class="create-ticket-button">
                <button onclick="window.location.href='create_ticket.php'"><i class="fas fa-plus"></i> Create New Ticket</button>
            </div>
        </div>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Importance</th>
                    <th>Deadline</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td onclick="window.location.href='view_ticket.php?id=<?php echo $ticket['id']; ?>'">
                            <?php echo htmlspecialchars($ticket['id']); ?>
                        </td>
                        <td onclick="window.location.href='view_ticket.php?id=<?php echo $ticket['id']; ?>'">
                            <?php echo htmlspecialchars($ticket['title']); ?>
                            <div class="ticket-info">
                                <i class="fas fa-user"></i><?php echo htmlspecialchars($ticket['raised_by']); ?> 
                                <i class="fas fa-arrow-right"></i>
                                <i class="fas fa-user-check"></i><?php echo htmlspecialchars($ticket['assigned_to']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['priority']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['importance']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['expected_deadline']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="edit-button" onclick="window.location.href='edit_ticket.php?id=<?php echo $ticket['id']; ?>'">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="tickets.php?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>" class="<?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <script>
        document.getElementById('search-icon').addEventListener('click', function() {
            const searchInput = document.getElementById('search-input');
            searchInput.style.width = '150px';
            searchInput.style.opacity = '1';
            searchInput.focus();
        });

        document.getElementById('search-input').addEventListener('blur', function() {
            if (this.value === '') {
                this.style.width = '0';
                this.style.opacity = '0';
            }
        });
    </script>
</body>
</html>
