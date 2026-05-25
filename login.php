<?php

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Database connection
require __DIR__ . '/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']);

    // Fetch user by email
    $stmt = $conn->prepare("SELECT id, fullname, password, role FROM users WHERE email = ?");

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $user['password'])) {

            // Save session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['role'] = $user['role'] ?? null;

            if ($remember) {
                setcookie('remember_me_user', $email, time() + (86400 * 30), "/");
            } else {
                setcookie('remember_me_user', '', time() - 3600, "/");
            }

            // Redirect based on role
            if (isset($user['role']) && trim(strtolower($user['role'])) === 'admin') {
                header("Location: prof_dashboard.php");
                exit();
            }

            header("Location: dashboard.php");
            exit();


        } else {

            echo "<script>alert('Incorrect password!'); window.history.back();</script>";
            exit();

        }

    } else {

        echo "<script>alert('No account found with that email!'); window.history.back();</script>";
        exit();

    }

    $stmt->close();
    $conn->close();
}
?>
