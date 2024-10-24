<?php
// Start a session
session_start();

// Initialize variables
$error = ""; // Initialize error variable
$success = ""; // Initialize success variable

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate password match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'userdatabase'); // Adjust as needed

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Prepare and bind
        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE username = ?");
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the new password
        $stmt->bind_param("ss", $hashed_password, $username);

        // Execute the statement
        if ($stmt->execute()) {
            // Successful reset
            // Redirect to the login page
            header("Location: login.php");
            exit(); // Make sure to call exit after header redirection
        } else {
            $error = "Failed to reset the password. Please try again."; // Set error message
        }

        // Close connections
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<div class="wrapper">
        <form action="" method="POST">
            <h1>Reset Password</h1>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="input-box">
                <input type="password" name="new_password" placeholder="New Password" required>
            </div>
            <button type="submit" class="btn">Reset Password</button>
        </form>
    </div>
</body>
</html>
