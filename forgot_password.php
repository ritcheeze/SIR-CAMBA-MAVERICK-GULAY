<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/db.php';

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    if ($email === '' || $new_password === '') {
        $message = 'Please complete both fields.';
        $status = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $status = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $status = 'error';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
            $update->bind_param('ss', $hashed_password, $email);

            if ($update->execute()) {
                $message = 'Password updated successfully. Please sign in.';
                $status = 'success';
            } else {
                $message = 'Unable to update password. Please try again.';
                $status = 'error';
            }
            $update->close();
        } else {
            $message = 'No account found with that email.';
            $status = 'error';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="forgot-card">
        <div class="forgot-header">
            <span class="badge">Password Reset</span>
            <h2>Reset your password</h2>
        </div>

        <form class="forgot-form" action="forgot_password.php" method="post">
            <div class="input-group">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" required>
            </div>

            <div class="input-group">
                <label for="new_password">New password</label>
                <input id="new_password" type="password" name="new_password" required>
            </div>

            <button type="submit" class="btn">Update Password</button>

            <?php if ($message !== ''): ?>
                <div class="message-box <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <p class="switch-form">Remembered your password? <a href="index.php">Sign in</a></p>
        </form>
    </div>
</body>
</html>
