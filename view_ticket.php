<?php
session_start();
require_once 'assets/config.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: tickets.php');
    exit();
}

$ticket_id = $_GET['id'];

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

try {
    $db = getDB();
} catch (Exception $e) {
    die('Database connection error: ' . $e->getMessage());
}

// Fetch ticket details
$stmt = $db->prepare("SELECT t.id, t.title, t.description, t.product, t.sub_product, t.status, t.priority, t.importance, t.expected_deadline, t.attachments, u1.name as raised_by, u2.name as assigned_to, t.team, t.created_at
                      FROM tickets t 
                      LEFT JOIN users u1 ON t.raised_by = u1.id
                      LEFT JOIN users u2 ON t.assigned_to = u2.id
                      WHERE t.id = ?");
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();
$stmt->close();

if (!$ticket) {
    header('Location: tickets.php');
    exit();
}

// Fetch product, sub product, and team names
$product_stmt = $db->prepare("SELECT product_name FROM products WHERE id = ?");
$product_stmt->bind_param('i', $ticket['product']);
$product_stmt->execute();
$product_stmt->bind_result($product_name);
$product_stmt->fetch();
$product_stmt->close();

$sub_product_stmt = $db->prepare("SELECT sub_product_name FROM sub_products WHERE id = ?");
$sub_product_stmt->bind_param('i', $ticket['sub_product']);
$sub_product_stmt->execute();
$sub_product_stmt->bind_result($sub_product_name);
$sub_product_stmt->fetch();
$sub_product_stmt->close();

$team_stmt = $db->prepare("SELECT team_name FROM teams WHERE id = ?");
$team_stmt->bind_param('i', $ticket['team']);
$team_stmt->execute();
$team_stmt->bind_result($team_name);
$team_stmt->fetch();
$team_stmt->close();

// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment = $_POST['comment'];
    $attachments = $_FILES['attachments'];
    
    $attachment_paths = [];
    if ($attachments && $attachments['name'][0] !== '') {
        foreach ($attachments['tmp_name'] as $index => $tmpName) {
            if ($tmpName) {
                $filename = basename($attachments['name'][$index]);
                $targetPath = "uploads/comments/" . $filename;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $attachment_paths[] = $targetPath;
                }
            }
        }
    }
    $attachments_json = json_encode($attachment_paths);

    $comment_stmt = $db->prepare("INSERT INTO comments (ticket_id, user_id, comment, attachments) VALUES (?, ?, ?, ?)");
    $comment_stmt->bind_param('iiss', $ticket_id, $user_id, $comment, $attachments_json);
    $comment_stmt->execute();
    $comment_stmt->close();

    // Refresh the page to reflect changes
    header("Location: view_ticket.php?id=" . $ticket_id);
    exit();
}

// Fetch comments
$comments_stmt = $db->prepare("SELECT c.comment, c.attachments, u.name as user_name, c.created_at 
                               FROM comments c 
                               JOIN users u ON c.user_id = u.id 
                               WHERE c.ticket_id = ? 
                               ORDER BY c.created_at ASC");
$comments_stmt->bind_param('i', $ticket_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
$comments = $comments_result->fetch_all(MYSQLI_ASSOC);
$comments_stmt->close();

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket - <?php echo htmlspecialchars($ticket['title']); ?></title>
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
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: rgba(68, 68, 68, 0.9);
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .left-panel, .right-panel {
            width: 48%;
        }

        .left-panel h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #00BFA5;
        }

        .ticket-info {
            margin-bottom: 15px;
        }

        .ticket-info label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #FFD700;
        }

        .ticket-info p, .ticket-info textarea {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #555;
            margin-bottom: 10px;
            color: #E0E0E0;
        }

        .ticket-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .ticket-row div {
            width: 48%;
        }

        .description-textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #555;
            background: rgba(255, 255, 255, 0.1);
            color: #E0E0E0;
            resize: vertical;
            height: 100px;
            font-size: 14px;
            font-family: 'Roboto', sans-serif;
            transition: background 0.3s, box-shadow 0.3s;
        }

        .description-textarea:focus {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(0, 191, 165, 0.5);
            outline: none;
        }

        .attachments ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .attachments ul li {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .attachments ul li a {
            color: #00BFA5;
            text-decoration: none;
            transition: color 0.3s;
        }

        .attachments ul li a:hover {
            color: #009688;
        }

        .right-panel {
            padding-left: 20px;
            border-left: 1px solid #444;
        }

        .comments h2 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #FFD700;
        }

        .comment {
            margin-bottom: 20px;
            padding: 15px;
            background: #2e2e2e;
            border-radius: 10px;
            border: 1px solid #444;
        }

        .comment p {
            margin: 0;
        }

        .comment .meta {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 10px;
        }

        .comment .attachments ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .comment .attachments ul li {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .comment .attachments ul li a {
            color: #00BFA5;
            text-decoration: none;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .comment .attachments ul li a:hover {
            color: #009688;
        }

        .comment .attachments ul li i {
            color: #00BFA5;
        }

        .comment-input {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .comment-input textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #555;
            background: rgba(255, 255, 255, 0.1);
            resize: vertical;
            height: 100px;
            color: #E0E0E0;
        }

        .comment-input button {
            padding: 10px 20px;
            background: #00BFA5;
            color: #E0E0E0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .comment-input button:hover {
            background: #009688;
        }

        .back-button {
            text-align: left;
            margin-top: 30px;
        }

        .back-button a {
            background: #FF073A;
            color: #E0E0E0;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s, transform 0.3s;
        }

        .back-button a:hover {
            background: #CC0625;
            transform: scale(1.05);
        }

        .delete-button {
            text-align: left;
            margin-top: 40px;
        }

        .delete-button a {
            background: #FF073A;
            color: #E0E0E0;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s, transform 0.3s;
        }

        .delete-button a:hover {
            background: #CC0625;
            transform: scale(1.05);
        }

        .close-btn {
            position: absolute;
            top: 15px;
            left: 96%;
            background-color: #FF073A;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.3s;
        }

        .close-btn i {
            font-size: 16px;
        }

        .close-btn:hover {
            transform: scale(1.1);
        }

        .download-btn {
            padding: 10px 25px;
            background-color: #00BFA5;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .download-btn i {
            font-size: 18px;
        }

        .download-btn:hover {
            background-color: #009688;
            transform: scale(1.05);
        }

        .file-input {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input input[type="file"] {
            position: absolute;
            top: 0;
            right: 0;
            margin: 0;
            padding: 0;
            font-size: 20px;
            cursor: pointer;
            opacity: 0;
            filter: alpha(opacity=0);
        }

        .file-input-label {
            background-color: #00BFA5;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .file-input-label i {
            font-size: 16px;
        }

        .selected-attachments ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        .selected-attachments ul li {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #555;
            padding: 5px 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .selected-attachments ul li button {
            background: none;
            border: none;
            color: #FF073A;
            cursor: pointer;
            font-size: 14px;
        }

        .selected-attachments ul li button:hover {
            color: #CC0625;
        }

        .selected-attachments ul li span {
            color: #fff;
            font-size: 14px;
        }

        .ticket-attachments ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .ticket-attachments ul li {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ticket-attachments ul li a {
            color: #00BFA5;
            text-decoration: none;
            transition: color 0.3s;
        }

        .ticket-attachments ul li a:hover {
            color: #009688;
        }

        .ticket-attachments ul li i {
            font-size: 18px;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Close Button -->
        <button class="close-btn" onclick="window.location.href='tickets.php'"><i class="fas fa-times"></i></button>

        <!-- Left Panel: Ticket Details -->
        <div class="left-panel">
            <h1>Ticket: <?php echo htmlspecialchars($ticket['title']); ?> (Raised by: <?php echo htmlspecialchars($ticket['raised_by']); ?>)</h1>

            <div class="ticket-row">
                <div class="ticket-info">
                    <label>Priority:</label>
                    <p><?php echo htmlspecialchars($ticket['priority']); ?></p>
                </div>
                <div class="ticket-info">
                    <label>Importance:</label>
                    <p><?php echo htmlspecialchars($ticket['importance']); ?></p>
                </div>
            </div>

            <div class="ticket-row">
                <div class="ticket-info">
                    <label>Product:</label>
                    <p><?php echo htmlspecialchars($product_name); ?></p>
                </div>
                <div class="ticket-info">
                    <label>Sub Product:</label>
                    <p><?php echo htmlspecialchars($sub_product_name); ?></p>
                </div>
            </div>

            <div class="ticket-row">
                <div class="ticket-info">
                    <label>Team:</label>
                    <p><?php echo htmlspecialchars($team_name); ?></p>
                </div>
                <div class="ticket-info">
                    <label>Assigned to:</label>
                    <p><?php echo htmlspecialchars($ticket['assigned_to']); ?></p>
                </div>
            </div>

            <div class="ticket-row">
                <div class="ticket-info">
                    <label>Description:</label>
                    <textarea class="description-textarea" readonly><?php echo htmlspecialchars($ticket['description']); ?></textarea>
                </div>
            </div>

            <div class="ticket-info ticket-attachments">
                <label>Attachments:</label>
                <?php if (!empty($ticket['attachments'])): ?>
                    <ul>
                        <?php 
                        $attachments = json_decode($ticket['attachments'], true);
                        foreach ($attachments as $attachment): ?>
                            <li><i class="fas fa-download"></i><?php echo basename($attachment); ?> 
                                <a href="<?php echo htmlspecialchars($attachment); ?>" download class="download-btn">Download</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No attachments.</p>
                <?php endif; ?>
            </div>

            <?php if ($is_admin || $is_manager): ?>
                <div class="delete-button">
                    <a href="delete_ticket.php?id=<?php echo $ticket['id']; ?>" onclick="return confirm('Are you sure you want to delete this ticket?')">Delete Ticket</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Panel: Comments -->
        <div class="right-panel">
            <div class="comments">
                <h2>Ticket Discussion</h2>

                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="meta">
                                <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong> - <?php echo htmlspecialchars($comment['created_at']); ?>
                            </div>
                            <p><?php echo htmlspecialchars($comment['comment']); ?></p>

                            <?php if (!empty($comment['attachments'])): ?>
                                <div class="attachments">
                                    <ul>
                                        <?php 
                                        $comment_attachments = json_decode($comment['attachments'], true);
                                        foreach ($comment_attachments as $attachment): ?>
                                            <li><a href="<?php echo htmlspecialchars($attachment); ?>" download><i class="fas fa-download"></i><?php echo basename($attachment); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No comments yet.</p>
                <?php endif; ?>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="comment-input">
                    <textarea name="comment" id="comment" placeholder="Add a comment..." required></textarea>
                    <button type="submit" name="add_comment"><i class="fas fa-paper-plane"></i></button>
                </div>

                <div class="file-input">
                    <label for="attachments" class="file-input-label"><i class="fas fa-paperclip"></i> Attach File:</label>
                    <input type="file" id="attachments" name="attachments[]" multiple onchange="showAttachmentList()" style="display: none;">
                </div>

                <div class="selected-attachments">
                    <ul id="attachment-list"></ul>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAttachmentList() {
            const attachmentList = document.getElementById('attachment-list');
            attachmentList.innerHTML = '';
            const files = document.getElementById('attachments').files;

            for (let i = 0; i < files.length; i++) {
                const listItem = document.createElement('li');
                const fileName = document.createElement('span');
                fileName.textContent = files[i].name;

                const removeButton = document.createElement('button');
                removeButton.textContent = 'x';
                removeButton.onclick = (function(index) {
                    return function() {
                        removeFile(index);
                    };
                })(i);

                listItem.appendChild(fileName);
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
    </script>
</body>
</html>
