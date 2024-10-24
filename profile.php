<?php
// Start session
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'userdatabase'); // Adjust if necessary

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$username = $_SESSION['username']; // Get the logged-in username
$user_email = '';
$update_success = false;

// Fetch the user's details from the database
$stmt = $conn->prepare("SELECT email, username FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_email, $username);
$stmt->fetch();
$stmt->close();

// Handle form submission to update user details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_password = $_POST['password'];

    // Update the user's details in the database
    $updateStmt = $conn->prepare("UPDATE user SET email = ?, username = ?, password = ? WHERE username = ?");
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt->bind_param("ssss", $new_email, $new_username, $hashed_password, $username);

    if ($updateStmt->execute()) {
        $update_success = true; // Set success flag
        // Update session variables
        $_SESSION['username'] = $new_username;
    }

    $updateStmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="containerlist">
        <h1>Website Navigation</h1>
        <div class="sidebar">
            <table class="sidebar-table">
                <tr>
                    <td><a href="profile.php">Profile</a></td>
                </tr>
                <tr>
                    <td><a href="dashboard.php">Dashboard</a></td>
                </tr>
                <tr>
                    <td><a href="todolist.php">List Management</a></td>
                </tr>
                <tr>
                    <td><a href="logout.php">Logout</a></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="container">
        <h1>Profile</h1>

        <?php if ($update_success): ?>
            <p style="color: green;">Profile updated successfully!</p>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="input-box">
                <label for="username">Username:</label>
                <i class="bx bx-user"></i>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="input-box">
                <label for="email">Email:</label>
                <i class='bx bxs-envelope'></i>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
            </div>
            <div class="input-box">
                <label for="password">Password:</label>
                <i class='bx bxs-lock-alt'></i>
                <input type="password" name="password" required>
                <small>Leave blank if no change</small>
            </div>
            <div>
                <button type="submit" class="update-button">Update Profile</button>
            </div>
        </form>
    </div>

    <script src="./script.js"></script>
</body>
</html>