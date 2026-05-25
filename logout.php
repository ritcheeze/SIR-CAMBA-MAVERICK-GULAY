<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the actual session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session on the server
session_destroy();

// Redirect back to your login page
header("Location: index.php");
exit;
?>