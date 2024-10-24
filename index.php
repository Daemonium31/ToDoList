<?php
// Start a session
session_start();

// Initialize variables
$username = "";
$password = "";
$error = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve username and password from the form
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if the remember me checkbox is set
    $remember = isset($_POST['remember']);

    // Add reCAPTCHA verification
    $recaptcha_secret = '6Lcf22oqAAAAALQdMGvEquSTADMpQDMYdlE3xXTQ'; // Replace with your actual secret key
    $recaptcha_response = $_POST['g-recaptcha-response'];

    // Verify the reCAPTCHA response
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);

    if ($recaptcha->success) {
        // reCAPTCHA is successful, proceed with login
        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'userdatabase'); // Adjust username and password if needed

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Prepare and bind
        $stmt = $conn->prepare("SELECT password FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        // Check if the user exists
        if ($stmt->num_rows > 0) {
            // User exists, now fetch the password
            $stmt->bind_result($hashed_password);
            $stmt->fetch();

            // Verify the password against the hashed password in the database
            if (password_verify($password, $hashed_password)) {
                // Password is correct, log in the user
                $_SESSION['username'] = $username; // Save username in session
                
                // Set a cookie if the user chose to be remembered
                if ($remember) {
                    // Set a cookie that expires in 1 hour
                    setcookie("username", $username, time() + 3600, "/"); // 3600 = 1 hour
                } else {
                    // Clear the cookie if the user didn't choose "Remember Me"
                    setcookie("username", "", time() - 3600, "/"); // Clear the cookie
                }

                header("Location: dashboard.php"); // Redirect to dashboard
                exit();
            } else {
                $error = "Invalid username or password."; // Invalid password
            }
        } else {
            $error = "Invalid username or password."; // User does not exist
        }

        // Close connections
        $stmt->close();
        $conn->close();
    } else {
        $error = "reCAPTCHA verification failed. Please try again.";
    }
} else {
    // Check if the cookie is set to auto-login the user
    if (isset($_COOKIE["username"])) {
        $username = $_COOKIE["username"];
        $_SESSION['username'] = $username; // Set session variable
        header("Location: dashboard.php"); // Redirect to dashboard
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="wrapper">
        <form action="" method="POST">
            <h1>Login</h1>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="input-box">
                <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required>
                <i class="bx bx-user"></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>
            <div class="remember-forget">
                <label><input type="checkbox" name="remember" <?php if (isset($_COOKIE["username"])) echo "checked"; ?>> Remember me</label>
                <a href="forgotpassword.php">Forgot Password?</a>
            </div>
            <!-- Add reCAPTCHA widget -->
            <div class="g-recaptcha" data-sitekey="6Lcf22oqAAAAAJG5oDwHsKjKYvRQuql1O-QKYmGc"></div> <!-- Replace with your actual site key -->
            <button type="submit" class="btn">Login</button>
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </form>
    </div>

    <!-- Load the reCAPTCHA v2 API script -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>
</html>
