<?php
session_start();
require_once 'assets/config.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $user_name, $user_email, $hashed_password);
    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        $_SESSION['user'] = $user_email;
        header('Location: index.php');
        exit();
    } else {
        $error_message = 'Invalid email or password';
    }
    $stmt->close();
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
            overflow: hidden;
        }

        .login-container {
            background: rgba(68, 68, 68, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: left;
            animation: slideIn 1s ease-in-out;
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

        .login-container h1 {
            font-size: 30px;
            margin-bottom: 20px;
            color: #00BFA5;
            animation: fadeIn 2s ease-in-out;
            text-align: center;
        }

        .login-container form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
        }

        .login-container label {
            font-size: 16px;
            margin-bottom: 5px;
            color: #E0E0E0;
        }

        .login-container .input-container {
            position: relative;
            width: 100%;
        }

        .login-container input[type="email"], .login-container input[type="password"], .login-container input[type="text"] {
            padding: 10px;
            margin-bottom: 20px;
            width: 95%;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: #E0E0E0;
            border: 1px solid #555;
            transition: background 0.3s, transform 0.3s;
        }

        .login-container input[type="email"]:focus, .login-container input[type="password"]:focus, .login-container input[type="text"]:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .login-container .toggle-eye {
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

        .login-container .toggle-eye:hover {
            color: #00BFA5;
        }

        .login-container input[type="submit"] {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #00BFA5;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            width: 100%;
        }

        .login-container input[type="submit"]:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .login-container .forgot-password {
            margin-top: 10px;
            font-size: 14px;
            color: #FFD700;
            cursor: pointer;
            text-decoration: underline;
            transition: color 0.3s;
        }

        .login-container .forgot-password:hover {
            color: #FFEA00;
        }

        .error-message {
            color: #FF073A;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>
            <div class="input-container">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-eye" id="toggle-password"></i>
            </div>
            <input type="submit" value="Login">
        </form>
        <div class="forgot-password" onclick="window.location.href='forgot_password.php'">Forgot Password?</div>
    </div>
    <script>
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
            // Reapply styles explicitly
            passwordField.style.padding = '10px';
            passwordField.style.marginBottom = '20px';
            passwordField.style.width = '95%';
            passwordField.style.border = 'none';
            passwordField.style.borderRadius = '5px';
            passwordField.style.background = 'rgba(255, 255, 255, 0.1)';
            passwordField.style.color = '#E0E0E0';
            passwordField.style.border = '1px solid #555';
            passwordField.style.transition = 'background 0.3s, transform 0.3s';
        });
    </script>
</body>
</html>
