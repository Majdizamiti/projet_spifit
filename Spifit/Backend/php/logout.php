<?php
session_start();

// Store user info before clearing session
$user_name = $_SESSION['user_name'] ?? 'User';

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - SPIFIT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .icon {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        p {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .login-link {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .login-link:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <i class="fas fa-sign-out-alt icon"></i>
        <h1>Logged Out Successfully</h1>
        <p>Thank you for using SPIFIT, <?php echo htmlspecialchars($user_name); ?>!</p>
        <a href="login.php" class="login-link">
            <i class="fas fa-sign-in-alt"></i> Login Again
        </a>
    </div>
    <script>
        // Redirect to login page after 5 seconds
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 5000);
    </script>
</body>
</html> 