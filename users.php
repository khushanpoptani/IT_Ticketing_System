<?php
session_start();
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
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

$is_admin = ($user_role === 'Admin');
$is_manager = ($user_role === 'Manager');

// Fetch all users
$limit = 5; // Number of users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$search = isset($_GET['search']) ? $_GET['search'] : '';

$stmt = $db->prepare("SELECT u.id, u.name, u.email, u.role, GROUP_CONCAT(t.team_name SEPARATOR ', ') as teams, m.name as manager_name 
                      FROM users u 
                      LEFT JOIN teams t ON FIND_IN_SET(u.id, t.team_members)
                      LEFT JOIN users m ON u.manager_id = m.id
                      WHERE u.name LIKE ? OR u.email LIKE ? OR u.role LIKE ? 
                      GROUP BY u.id 
                      LIMIT ?, ?");
$searchTerm = '%' . $search . '%';
$stmt->bind_param('ssssi', $searchTerm, $searchTerm, $searchTerm, $start, $limit);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_stmt = $db->prepare("SELECT COUNT(DISTINCT u.id) as total 
                            FROM users u 
                            LEFT JOIN teams t ON FIND_IN_SET(u.id, t.team_members)
                            LEFT JOIN users m ON u.manager_id = m.id
                            WHERE u.name LIKE ? OR u.email LIKE ? OR u.role LIKE ?");
$total_stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_users = $total_result->fetch_assoc()['total'];
$total_stmt->close();

$total_pages = ceil($total_users / $limit);

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
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
                opacity: 1;
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

        .users-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .users-container h1 {
            text-align: center;
            color: #00BFA5;
            margin-bottom: 20px;
        }

        .create-user-button {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .create-user-button button {
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

        .create-user-button button:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 5px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
        }

        .users-table th, .users-table td {
            padding: 15px;
            text-align: center;
        }

        .users-table th {
            background: #4a4a4a;
            color: #E0E0E0;
        }

        .users-table td {
            background: #2e2e2e;
            border-bottom: 1px solid #444;
        }

        .users-table tr:nth-child(even) td {
            background: #3a3a3a;
        }

        .users-table ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .users-table ul li {
            padding: 5px 0;
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
    <div class="users-container">
        <h1>Users</h1>
        <div class="search-bar-container">
            <div class="search-bar">
                <form method="GET" action="users.php">
                    <input type="text" name="search" placeholder="Search users" id="search-input" value="<?php echo htmlspecialchars($search); ?>">
                    <i class="fas fa-search" id="search-icon"></i>
                </form>
            </div>
            <?php if ($is_admin): ?>
            <div class="create-user-button">
                <button onclick="window.location.href='create_user.php'"><i class="fas fa-plus"></i> Create New User</button>
                <button class="export-button" onclick="window.location.href='export_users.php'"><i class="fas fa-file-export"></i> Export</button>
            </div>
            <?php endif; ?>
        </div>
        <table class="users-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Teams</th>
                    <th>Manager</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $index => $user): ?>
                    <tr>
                        <td><?php echo ($start + $index + 1); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <ul>
                                <?php 
                                $teams = explode(', ', $user['teams']);
                                foreach ($teams as $team): ?>
                                    <li><?php echo htmlspecialchars($team); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td><?php 
                            if ($user['manager_name'] == null){
                                $user['manager_name'] = "No Manager";
                            }
                        echo htmlspecialchars($user['manager_name']); 
                        
                        ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($is_admin || $is_manager): ?>
                                <button class="edit-button" onclick="window.location.href='edit_user.php?id=<?php echo $user['id']; ?>'">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($is_admin): ?>
                                <button class="delete-button" onclick="if(confirm('Are you sure you want to delete this user?')) window.location.href='delete_user.php?id=<?php echo $user['id']; ?>'">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="users.php?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>" class="<?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
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
