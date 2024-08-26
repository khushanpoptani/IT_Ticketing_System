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

// Only admins and managers can create teams
if ($user_role !== 'Admin' && $user_role !== 'Manager') {
    header('Location: index.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = $_POST['team_name'];
    $team_manager = $_POST['team_manager'];
    $team_members = $_POST['team_members'] ?? [];

    if (empty($team_name) || empty($team_manager)) {
        $error_message = 'Team name and manager are required fields.';
    } else {
        $team_members_str = implode(',', $team_members);
        $stmt = $db->prepare("INSERT INTO teams (team_name, team_manager, team_members) VALUES (?, ?, ?)");
        $stmt->bind_param('sis', $team_name, $team_manager, $team_members_str);
        if ($stmt->execute()) {
            header('Location: teams.php');
            exit();
        } else {
            $error_message = 'Error creating team. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch all possible managers and users
$stmt = $db->prepare("SELECT id, name FROM users WHERE role IN ('Manager', 'Admin')");
$stmt->execute();
$managers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare("SELECT id, name FROM users");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Team</title>
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

        .create-team-container {
            background: rgba(50, 50, 50, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .create-team-container h1 {
            text-align: center;
            color: #00BFA5;
            margin-bottom: 20px;
        }

        .create-team-container form {
            display: flex;
            flex-direction: column;
        }

        .create-team-container label {
            margin-bottom: 5px;
            color: #E0E0E0;
        }

        .create-team-container input[type="text"],
        .create-team-container select {
            padding: 10px;
            margin-bottom: 20px;
            width: 95%;
            border-radius: 5px;
            border: 1px solid #555;
            background: rgba(255, 255, 255, 0.1);
            color: #E0E0E0;
            transition: background 0.3s, transform 0.3s;
        }

        .create-team-container input[type="text"]:focus,
        .create-team-container select:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .create-team-container input[type="submit"] {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #00BFA5;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            width: 100%;
        }

        .create-team-container input[type="submit"]:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .create-team-container .cancel-button {
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

        .create-team-container .cancel-button:hover {
            background: #CC0625;
            transform: scale(1.05);
        }

        .create-team-container .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #FF073A;
            font-size: 24px;
            cursor: pointer;
        }

        .create-team-container .close-button:hover {
            color: #CC0625;
        }

        .create-team-container .team-container {
            max-height: 100px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .create-team-container .team-container label {
            display: block;
        }

        .error-message {
            color: #FF073A;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="create-team-container">
        <button class="close-button" onclick="window.location.href='teams.php'">&times;</button>
        <h1>Create Team</h1>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST" action="create_team.php">
            <label for="team_name">Team Name</label>
            <input type="text" id="team_name" name="team_name" required>

            <label for="team_manager">Team Manager</label>
            <select id="team_manager" name="team_manager" required>
                <option value="">Select Manager</option>
                <?php foreach ($managers as $manager): ?>
                    <option value="<?php echo $manager['id']; ?>"><?php echo htmlspecialchars($manager['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Team Members</label>
            <div class="team-container">
                <?php foreach ($users as $user): ?>
                    <label>
                        <input type="checkbox" name="team_members[]" value="<?php echo $user['id']; ?>">
                        <?php echo htmlspecialchars($user['name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <input type="submit" value="Create Team">
        </form>
        <button class="cancel-button" onclick="window.location.href='teams.php'">Cancel</button>
    </div>
</body>
</html>
