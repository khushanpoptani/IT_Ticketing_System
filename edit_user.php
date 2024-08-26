<?php
session_start();
require_once 'assets/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login page if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Only admins can edit users
if ($_SESSION['role'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

$error_message = '';
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header('Location: users.php');
    exit();
}

$db = getDB();
$stmt = $db->prepare("SELECT id, name, email, role, manager_id FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: users.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $manager_id = $_POST['manager_id'] ?? null;
    $team_ids = $_POST['team_ids'] ?? [];

    if ($manager_id === '') {
        $manager_id = null;
    }

    if (empty($name) || empty($email)) {
        $error_message = 'Name and email are required fields.';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, manager_id = ? WHERE id = ?");
            $stmt->bind_param('ssssii', $name, $email, $hashed_password, $role, $manager_id, $user_id);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, manager_id = ? WHERE id = ?");
            $stmt->bind_param('sssii', $name, $email, $role, $manager_id, $user_id);
        }

        if ($stmt->execute()) {
            // Update the teams
            $stmt = $db->prepare("UPDATE teams SET team_members = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', team_members, ','), CONCAT(',', ?, ','), ',')) WHERE FIND_IN_SET(?, team_members)");
            $stmt->bind_param('ii', $user_id, $user_id);
            $stmt->execute();
            $stmt->close();

            foreach ($team_ids as $team_id) {
                $stmt = $db->prepare("SELECT team_members FROM teams WHERE id = ?");
                $stmt->bind_param('i', $team_id);
                $stmt->execute();
                $stmt->bind_result($team_members);
                $stmt->fetch();
                $stmt->close();

                $team_members_array = empty($team_members) ? [] : explode(',', $team_members);
                $team_members_array[] = $user_id;
                $new_team_members = implode(',', $team_members_array);

                $stmt = $db->prepare("UPDATE teams SET team_members = ? WHERE id = ?");
                $stmt->bind_param('si', $new_team_members, $team_id);
                $stmt->execute();
                $stmt->close();
            }

            header('Location: users.php');
            exit();
        } else {
            $error_message = 'Error updating user. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch all possible managers and teams
$stmt = $db->prepare("SELECT id, name FROM users WHERE role IN ('Manager', 'Admin')");
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($db->error));
}
$stmt->execute();
$result = $stmt->get_result();
$managers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare("SELECT id, team_name FROM teams");
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($db->error));
}
$stmt->execute();
$result = $stmt->get_result();
$teams = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user's current teams
$stmt = $db->prepare("SELECT id FROM teams WHERE FIND_IN_SET(?, team_members)");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_teams = $result->fetch_all(MYSQLI_ASSOC);
$current_team_ids = array_column($current_teams, 'id');
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1C1C1C, #2C2C2C);
            color: #E0E0E0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .edit-user-container {
            background: rgba(50, 50, 50, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .edit-user-container h1 {
            text-align: center;
            color: #00BFA5;
            margin-bottom: 20px;
        }

        .edit-user-container form {
            display: flex;
            flex-direction: column;
        }

        .edit-user-container label {
            margin-bottom: 5px;
            color: #E0E0E0;
        }

        .edit-user-container input[type="text"],
        .edit-user-container input[type="email"],
        .edit-user-container input[type="password"],
        .edit-user-container select {
            padding: 10px;
            margin-bottom: 20px;
            width: 95%;
            border-radius: 5px;
            border: 1px solid #555;
            background: rgba(255, 255, 255, 0.1);
            color: #E0E0E0;
            transition: background 0.3s, transform 0.3s;
        }

        .edit-user-container input[type="text"]:focus,
        .edit-user-container input[type="email"]:focus,
        .edit-user-container input[type="password"]:focus,
        .edit-user-container select:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .edit-user-container .input-container {
            position: relative;
            width: 100%;
        }

        .edit-user-container .toggle-eye {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #E0E0E0;
            font-size: 18px;
            transition: color 0.3s;
            z-index: 1;
        }

        .edit-user-container .toggle-eye:hover {
            color: #00BFA5;
        }

        .edit-user-container input[type="submit"] {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #00BFA5;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            width: 100%;
        }

        .edit-user-container input[type="submit"]:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .edit-user-container .cancel-button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #FF073A;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        .edit-user-container .cancel-button:hover {
            background: #CC0625;
            transform: scale(1.05);
        }

        .edit-user-container .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #FF073A;
            font-size: 24px;
            cursor: pointer;
        }

        .edit-user-container .close-button:hover {
            color: #CC0625;
        }

        .edit-user-container .team-container {
            max-height: 100px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .edit-user-container .team-container label {
            display: block;
        }

        .error-message {
            color: #FF073A;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="edit-user-container">
        <button class="close-button" onclick="window.location.href='users.php'">&times;</button>
        <h1>Edit User</h1>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST" action="edit_user.php?id=<?php echo htmlspecialchars($user_id); ?>" onsubmit="return validatePasswords()">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

            <div class="input-container">
                <label for="password">Password (leave blank to keep current password)</label>
                <input type="password" id="password" name="password">
                <i class="fas fa-eye toggle-eye" id="toggle-password"></i>
            </div>

            <div class="input-container">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password">
                <i class="fas fa-eye toggle-eye" id="toggle-confirm-password"></i>
            </div>

            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="Intern" <?php if ($user['role'] == 'Intern') echo 'selected'; ?>>Intern</option>
                <option value="Manager" <?php if ($user['role'] == 'Manager') echo 'selected'; ?>>Manager</option>
                <option value="CEO" <?php if ($user['role'] == 'CEO') echo 'selected'; ?>>CEO</option>
                <option value="Freelancer" <?php if ($user['role'] == 'Freelancer') echo 'selected'; ?>>Freelancer</option>
                <option value="Admin" <?php if ($user['role'] == 'Admin') echo 'selected'; ?>>Admin</option>
            </select>

            <label for="manager_id">Manager</label>
            <select id="manager_id" name="manager_id">
                <option value="">None</option>
                <?php foreach ($managers as $manager): ?>
                    <option value="<?php echo $manager['id']; ?>" <?php if ($user['manager_id'] == $manager['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($manager['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Teams</label>
            <div class="team-container">
                <?php foreach ($teams as $team): ?>
                    <label>
                        <input type="checkbox" name="team_ids[]" value="<?php echo $team['id']; ?>" <?php if (in_array($team['id'], $current_team_ids)) echo 'checked'; ?>>
                        <?php echo htmlspecialchars($team['team_name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <input type="submit" value="Update User">
        </form>
        <button class="cancel-button" onclick="window.location.href='users.php'">Cancel</button>
    </div>
    <script>
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        document.getElementById('toggle-confirm-password').addEventListener('click', function() {
            const confirmPasswordField = document.getElementById('confirm_password');
            const type = confirmPasswordField.type === 'password' ? 'text' : 'password';
            confirmPasswordField.type = type;
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        function validatePasswords() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
