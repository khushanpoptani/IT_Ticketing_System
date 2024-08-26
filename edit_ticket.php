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

// Fetch the ticket ID from the URL
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$ticket_id) {
    die('Invalid ticket ID');
}

// Fetch the ticket details
$stmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    die('Ticket not found');
}

// Fetch products
$products = $db->query("SELECT id, product_name FROM products")->fetch_all(MYSQLI_ASSOC);

// Fetch teams
$teams = $db->query("SELECT id, team_name FROM teams")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $product = $_POST['product'];
    $sub_product = $_POST['sub_product'];
    $team = $_POST['team'];
    $assigned_to = $_POST['assigned_to'];
    $priority = $_POST['priority'];
    $importance = $_POST['importance'];
    $deadline = $_POST['deadline'];
    $attachments = $_FILES['attachments'];

    // Manage existing attachments
    $existing_attachments = isset($_POST['existing_attachments']) ? $_POST['existing_attachments'] : [];
    $attachment_paths = $existing_attachments;

    // Save new attachments
    foreach ($attachments['tmp_name'] as $index => $tmpName) {
        if ($tmpName) {
            $filename = basename($attachments['name'][$index]);
            $targetPath = __DIR__ . "/uploads/" . $filename;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $attachment_paths[] = "uploads/" . $filename;  // Store relative path for database
            }
        }
    }
    $attachments_json = json_encode($attachment_paths);

    // Update ticket in database
    $stmt = $db->prepare("UPDATE tickets SET title = ?, description = ?, product = ?, sub_product = ?, team = ?, assigned_to = ?, priority = ?, importance = ?, expected_deadline = ?, attachments = ? WHERE id = ?");
    $stmt->bind_param('sssssissssi', $title, $description, $product, $sub_product, $team, $assigned_to, $priority, $importance, $deadline, $attachments_json, $ticket_id);
    $stmt->execute();
    $stmt->close();

    header('Location: tickets.php');
    exit();
}

// Fetch sub-products based on selected product
$sub_products = [];
if ($ticket['product']) {
    $stmt = $db->prepare("SELECT id, sub_product_name FROM sub_products WHERE product_id = ?");
    $stmt->bind_param('i', $ticket['product']);
    $stmt->execute();
    $sub_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch team users based on selected team
$team_users = [];
if ($ticket['team']) {
    $stmt = $db->prepare("SELECT team_members FROM teams WHERE id = ?");
    $stmt->bind_param('i', $ticket['team']);
    $stmt->execute();
    $stmt->bind_result($team_members);
    $stmt->fetch();
    $stmt->close();

    $team_member_ids = explode(',', $team_members);

    if (count($team_member_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($team_member_ids), '?'));
        $types = str_repeat('i', count($team_member_ids));

        $stmt = $db->prepare("SELECT id, name FROM users WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$team_member_ids);
        $stmt->execute();
        $team_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ticket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #1C1C1C;
            color: #E0E0E0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .edit-container {
            background: rgba(68, 68, 68, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 800px;
            animation: slideIn 1s ease-in-out;
            position: relative;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .edit-container h1 {
            font-size: 30px;
            margin-bottom: 20px;
            color: #00BFA5;
            animation: fadeIn 2s ease-in-out;
            text-align: center;
        }

        .edit-container form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
        }

        .edit-container label {
            display: block;
            margin-bottom: 5px;
            font-size: 16px;
            color: #E0E0E0;
        }

        .edit-container input[type="text"],
        .edit-container select,
        .edit-container textarea,
        .edit-container input[type="date"] {
            padding: 10px;
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: #E0E0E0;
            border: 1px solid #555;
            transition: background 0.3s, transform 0.3s;
        }

        .edit-container input[type="file"] {
            display: none;
        }

        .edit-container input[type="text"]:focus,
        .edit-container select:focus,
        .edit-container textarea:focus,
        .edit-container input[type="date"]:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .edit-container .submit-button {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }

        .edit-container .submit-button button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            width: 48%;
        }

        .edit-container .submit-button .update {
            background: #00BFA5;
            color: #E0E0E0;
        }

        .edit-container .submit-button .cancel {
            background: #FF073A;
            color: #E0E0E0;
        }

        .edit-container .submit-button button:hover {
            transform: scale(1.05);
        }

        .edit-container .submit-button .update:hover {
            background: #009688;
        }

        .edit-container .submit-button .cancel:hover {
            background: #CC0625;
        }

        .edit-container .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #FF073A;
            font-size: 24px;
            cursor: pointer;
        }

        .edit-container .close-button:hover {
            color: #CC0625;
        }

        .attachment-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .attachment-container label {
            padding: 10px 20px;
            background: #00BFA5;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            color: #E0E0E0;
            margin-right: 20px;
        }

        .attachment-container label:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .attachment-container ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .attachment-container ul li {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #555;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .download-button {
            color: #00BFA5;
            text-decoration: none;
            font-size: 18px;
            cursor: pointer;
        }

        .download-button:hover {
            color: #009688;
        }


        .attachment-container ul li button {
            background: none;
            border: none;
            color: #FF073A;
            cursor: pointer;
            font-size: 16px;
        }

        .attachment-container ul li button:hover {
            color: #CC0625;
        }

        .row {
            display: flex;
            gap: 20px;
            width: 100%;
            justify-content: space-between;
        }

        .row .column {
            flex: 1;
        }

    </style>
</head>
<body>
    <div class="edit-container">
        <button class="close-button" onclick="window.location.href='tickets.php'">&times;</button>
        <h1>Edit Ticket</h1>
        <form method="POST" action="edit_ticket.php?id=<?php echo $ticket_id; ?>" enctype="multipart/form-data">
            <div class="column">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ticket['title']); ?>" required>
            </div>

            <div class="row">
                <div class="column">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" required>
                        <option value="Low" <?php echo $ticket['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo $ticket['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo $ticket['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Very High" <?php echo $ticket['priority'] === 'Very High' ? 'selected' : ''; ?>>Very High</option>
                    </select>
                </div>
                <div class="column">
                    <label for="importance">Importance</label>
                    <select id="importance" name="importance" required>
                        <option value="Low" <?php echo $ticket['importance'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo $ticket['importance'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo $ticket['importance'] === 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="column">
                    <label for="product">Product</label>
                    <select id="product" name="product" required onchange="fetchSubProducts(this.value)">
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo $ticket['product'] == $product['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($product['product_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="column">
                    <label for="sub_product">Sub Product</label>
                    <select id="sub_product" name="sub_product" required>
                        <option value="">Select Sub Product</option>
                        <?php foreach ($sub_products as $sub_product): ?>
                            <option value="<?php echo $sub_product['id']; ?>" <?php echo $ticket['sub_product'] == $sub_product['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sub_product['sub_product_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="column">
                    <label for="team">Team</label>
                    <select id="team" name="team" required onchange="fetchTeamUsers(this.value)">
                        <option value="">Select Team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['id']; ?>" <?php echo $ticket['team'] == $team['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($team['team_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="column">
                    <label for="assigned_to">To</label>
                    <select id="assigned_to" name="assigned_to" required>
                        <option value="">Select User</option>
                        <?php foreach ($team_users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $ticket['assigned_to'] == $user['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="column">
                <label for="deadline">Deadline</label>
                <input type="date" id="deadline" name="deadline" value="<?php echo htmlspecialchars($ticket['expected_deadline']); ?>" required>
            </div>

            <div class="row">
                <div class="column">
                    <label for="attachments">Attachments</label>
                    <div class="attachment-container">
                        <label for="attachments" class="attachment-button">
                            <i class="fa-solid fa-paperclip"></i> Choose files
                        </label>
                        <input type="file" id="attachments" name="attachments[]" multiple onchange="showAttachmentList()" style="display: none;">
                        <ul id="attachment-list">
                            <?php 
                                $current_attachments = json_decode($ticket['attachments'], true);
                                foreach ($current_attachments as $index => $attachment): ?>
                                    <li>
                                        <span><?php echo htmlspecialchars(basename($attachment)); ?></span>
                                        <a href="<?php echo htmlspecialchars($attachment); ?>" download class="download-button">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" onclick="removeExistingFile(<?php echo $index; ?>)">x</button>
                                        <input type="hidden" name="existing_attachments[]" value="<?php echo htmlspecialchars($attachment); ?>">
                                    </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($ticket['description']); ?></textarea>

            <div class="submit-button">
                <button type="submit" class="update">Update Ticket</button>
                <button type="button" class="cancel" onclick="window.location.href='tickets.php'">Cancel</button>
            </div>
        </form>
    </div>

    <script>
        function fetchSubProducts(productId) {
            if (productId) {
                fetch('edit_ticket.php?id=<?php echo $ticket_id; ?>&fetch=sub_products&product_id=' + productId)
                    .then(response => response.json())
                    .then(data => {
                        const subProductSelect = document.getElementById('sub_product');
                        subProductSelect.innerHTML = '<option value="">Select Sub Product</option>';
                        data.forEach(subProduct => {
                            const option = document.createElement('option');
                            option.value = subProduct.id;
                            option.textContent = subProduct.sub_product_name;
                            subProductSelect.appendChild(option);
                        });
                    });
            } else {
                document.getElementById('sub_product').innerHTML = '<option value="">Select Sub Product</option>';
            }
        }

        function fetchTeamUsers(teamId) {
            if (teamId) {
                fetch('edit_ticket.php?id=<?php echo $ticket_id; ?>&fetch=team_users&team_id=' + teamId)
                    .then(response => response.json())
                    .then(data => {
                        const userSelect = document.getElementById('assigned_to');
                        userSelect.innerHTML = '<option value="">Select User</option>';
                        data.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            option.textContent = user.name;
                            userSelect.appendChild(option);
                        });
                    });
            } else {
                document.getElementById('assigned_to').innerHTML = '<option value="">Select User</option>';
            }
        }

        function showAttachmentList() {
            const attachmentList = document.getElementById('attachment-list');
            attachmentList.innerHTML = '';
            const files = document.getElementById('attachments').files;

            for (let i = 0; i < files.length; i++) {
                const listItem = document.createElement('li');
                listItem.textContent = files[i].name;

                const removeButton = document.createElement('button');
                removeButton.textContent = 'x';
                removeButton.onclick = (function(index) {
                    return function() {
                        removeFile(index);
                    };
                })(i);

                listItem.appendChild(removeButton);
                attachmentList.appendChild(listItem);
            }
        }

        function removeFile(index) {
            const fileInput = document.getElementById('attachments');
            const dataTransfer = new DataTransfer();

            const files = fileInput.files;
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dataTransfer.items.add(files[i]);
                }
            }

            fileInput.files = dataTransfer.files;
            showAttachmentList();
        }

        function removeExistingFile(index) {
            const attachmentList = document.getElementById('attachment-list');
            const existingAttachments = document.getElementsByName('existing_attachments[]');
            const listItem = existingAttachments[index].closest('li');
            attachmentList.removeChild(listItem);
        }
    </script>
</body>
</html>
