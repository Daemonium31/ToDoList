<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Optionally, clear any cookies (if used)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear the specific username cookie
setcookie("username", "", time() - 3600, "/"); // Clear the username cookie

// Redirect to the login page
header("Location: login.php");
exit();
?>
