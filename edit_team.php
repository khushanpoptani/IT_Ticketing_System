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

// Only admins and managers can edit teams
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Manager') {
    header('Location: index.php');
    exit();
}

$error_message = '';
$team_id = $_GET['id'] ?? null;

if (!$team_id) {
    header('Location: teams.php');
    exit();
}

$db = getDB();
$stmt = $db->prepare("SELECT team_name, team_manager, team_members FROM teams WHERE id = ?");
$stmt->bind_param('i', $team_id);
$stmt->execute();
$result = $stmt->get_result();
$team = $result->fetch_assoc();
$stmt->close();

if (!$team) {
    header('Location: teams.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = $_POST['team_name'];
    $team_manager = $_POST['team_manager'];
    $team_members = $_POST['team_members'] ?? [];

    if (empty($team_name) || empty($team_manager)) {
        $error_message = 'Team name and manager are required fields.';
    } else {
        $team_members_str = implode(',', $team_members);
        $stmt = $db->prepare("UPDATE teams SET team_name = ?, team_manager = ?, team_members = ? WHERE id = ?");
        $stmt->bind_param('sisi', $team_name, $team_manager, $team_members_str, $team_id);
        if ($stmt->execute()) {
            header('Location: teams.php');
            exit();
        } else {
            $error_message = 'Error updating team. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch all possible managers and users
$stmt = $db->prepare("SELECT id, name FROM users WHERE role IN ('Manager', 'Admin')");
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($db->error));
}
$stmt->execute();
$result = $stmt->get_result();
$managers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare("SELECT id, name FROM users");
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($db->error));
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

$team_members_array = explode(',', $team['team_members']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team</title>
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

        .edit-team-container {
            background: rgba(50, 50, 50, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .edit-team-container h1 {
            text-align: center;
            color: #00BFA5;
            margin-bottom: 20px;
        }

        .edit-team-container form {
            display: flex;
            flex-direction: column;
        }

        .edit-team-container label {
            margin-bottom: 5px;
            color: #E0E0E0;
        }

        .edit-team-container input[type="text"],
        .edit-team-container select {
            padding: 10px;
            margin-bottom: 20px;
            width: 95%;
            border-radius: 5px;
            border: 1px solid #555;
            background: rgba(255, 255, 255, 0.1);
            color: #E0E0E0;
            transition: background 0.3s, transform 0.3s;
        }

        .edit-team-container input[type="text"]:focus,
        .edit-team-container select:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .edit-team-container input[type="submit"] {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #00BFA5;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            width: 100%;
        }

        .edit-team-container input[type="submit"]:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .edit-team-container .cancel-button {
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

        .edit-team-container .cancel-button:hover {
            background: #CC0625;
            transform: scale(1.05);
        }

        .edit-team-container .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #FF073A;
            font-size: 24px;
            cursor: pointer;
        }

        .edit-team-container .close-button:hover {
            color: #CC0625;
        }

        .edit-team-container .team-container {
            max-height: 100px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .edit-team-container .team-container label {
            display: block;
        }

        .error-message {
            color: #FF073A;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="edit-team-container">
        <button class="close-button" onclick="window.location.href='teams.php'">&times;</button>
        <h1>Edit Team</h1>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST" action="edit_team.php?id=<?php echo htmlspecialchars($team_id); ?>">
            <label for="team_name">Team Name</label>
            <input type="text" id="team_name" name="team_name" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>

            <label for="team_manager">Team Manager</label>
            <select id="team_manager" name="team_manager" required>
                <option value="">Select Manager</option>
                <?php foreach ($managers as $manager): ?>
                    <option value="<?php echo $manager['id']; ?>" <?php if ($team['team_manager'] == $manager['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($manager['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Team Members</label>
            <div class="team-container">
                <?php foreach ($users as $user): ?>
                    <label>
                        <input type="checkbox" name="team_members[]" value="<?php echo $user['id']; ?>" <?php if (in_array($user['id'], $team_members_array)) echo 'checked'; ?>>
                        <?php echo htmlspecialchars($user['name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <input type="submit" value="Update Team">
        </form>
        <button class="cancel-button" onclick="window.location.href='teams.php'">Cancel</button>
    </div>
</body>
</html>
