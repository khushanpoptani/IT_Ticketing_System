<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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

// Only admins can create new users
if ($user_role !== 'Admin') {
    header('Location: index.php');
    exit();
}

$error_message = '';

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

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error_message = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user into the database
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, manager_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssi', $name, $email, $hashed_password, $role, $manager_id);
        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;

            // Update the team if a team was selected
            foreach ($team_ids as $team_id) {
                $stmt = $db->prepare("SELECT team_members FROM teams WHERE id = ?");
                $stmt->bind_param('i', $team_id);
                $stmt->execute();
                $stmt->bind_result($team_members);
                $stmt->fetch();
                $stmt->close();

                $team_members_array = empty($team_members) ? [] : explode(',', $team_members);
                $team_members_array[] = $new_user_id;
                $new_team_members = implode(',', $team_members_array);

                $stmt = $db->prepare("UPDATE teams SET team_members = ? WHERE id = ?");
                $stmt->bind_param('si', $new_team_members, $team_id);
                $stmt->execute();
                $stmt->close();
            }

            header('Location: users.php');
            exit();
        } else {
            $error_message = 'Error creating user. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch all possible managers and teams
$stmt = $db->prepare("SELECT id, name FROM users WHERE role IN ('Manager', 'Admin')");
$stmt->execute();
$managers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare("SELECT id, team_name FROM teams");
$stmt->execute();
$teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
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

        .create-user-container {
            background: rgba(50, 50, 50, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .create-user-container h1 {
            text-align: center;
            color: #00BFA5;
            margin-bottom: 20px;
        }

        .create-user-container form {
            display: flex;
            flex-direction: column;
        }

        .create-user-container label {
            margin-bottom: 5px;
            color: #E0E0E0;
        }

        .create-user-container input[type="text"],
        .create-user-container input[type="email"],
        .create-user-container input[type="password"],
        .create-user-container select {
            padding: 10px;
            margin-bottom: 20px;
            width: 95%;
            border-radius: 5px;
            border: 1px solid #555;
            background: rgba(255, 255, 255, 0.1);
            color: #E0E0E0;
            transition: background 0.3s, transform 0.3s;
        }

        .create-user-container input[type="text"]:focus,
        .create-user-container input[type="email"]:focus,
        .create-user-container input[type="password"]:focus,
        .create-user-container select:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .create-user-container .input-container {
            position: relative;
            width: 100%;
        }

        .create-user-container .toggle-eye {
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

        .create-user-container .toggle-eye:hover {
            color: #00BFA5;
        }

        .create-user-container input[type="submit"] {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #00BFA5;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            width: 100%;
        }

        .create-user-container input[type="submit"]:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .create-user-container .cancel-button {
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

        .create-user-container .cancel-button:hover {
            background: #CC0625;
            transform: scale(1.05);
        }

        .create-user-container .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #FF073A;
            font-size: 24px;
            cursor: pointer;
        }

        .create-user-container .close-button:hover {
            color: #CC0625;
        }

        .create-user-container .team-container {
            max-height: 100px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .create-user-container .team-container label {
            display: block;
        }

        .error-message {
            color: #FF073A;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="create-user-container">
        <button class="close-button" onclick="window.location.href='users.php'">&times;</button>
        <h1>Create User</h1>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST" action="create_user.php" onsubmit="return validatePasswords()">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <div class="input-container">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <i class="fas fa-eye toggle-eye" id="toggle-password"></i>
            </div>

            <div class="input-container">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <i class="fas fa-eye toggle-eye" id="toggle-confirm-password"></i>
            </div>

            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="Intern">Intern</option>
                <option value="Manager">Manager</option>
                <option value="CEO">CEO</option>
                <option value="Freelancer">Freelancer</option>
                <option value="Admin">Admin</option>
            </select>

            <label for="manager_id">Manager</label>
            <select id="manager_id" name="manager_id">
                <option value="">None</option>
                <?php foreach ($managers as $manager): ?>
                    <option value="<?php echo $manager['id']; ?>"><?php echo htmlspecialchars($manager['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Teams</label>
            <div class="team-container">
                <?php foreach ($teams as $team): ?>
                    <label>
                        <input type="checkbox" name="team_ids[]" value="<?php echo $team['id']; ?>">
                        <?php echo htmlspecialchars($team['team_name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <input type="submit" value="Create User">
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
