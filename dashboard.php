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
$todos = [];
$username = $_SESSION['username']; // Get the logged-in username

// Fetch the user's email from the username
$stmt = $conn->prepare("SELECT email FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_email);
$stmt->fetch();
$stmt->close();

// Fetch all todos from the database for the user
$sql = "SELECT * FROM list WHERE user_email = ?"; // No filters applied

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email); // Bind user_email
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $todos[] = $row;
}

$stmt->close();

// Handle form submission to update todo status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_todo'])) {
    $todo_id = $_POST['todo_id'];
    $status = isset($_POST['status']) ? 'completed' : 'pending';

    // Update the status in the database
    $updateStmt = $conn->prepare("UPDATE list SET status = ? WHERE id = ?");
    $updateStmt->bind_param("si", $status, $todo_id);
    $updateStmt->execute();
    $updateStmt->close();

    // Redirect to the same page to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
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
    <h1>Dashboard</h1>

    <div class="todos-container">
      <ul class="todos">
        <!-- Loop through tasks and display them -->
        <?php if (empty($todos)): ?>
          <p>Nothing To Do.</p> </br>
          <p> Lets go<a href="todolist.php"> make some</a></p>
        <?php else: ?>
          <?php foreach ($todos as $todo): ?>
            <li class="todo">
              <form action="" method="POST">
                <label for="todo-<?php echo $todo['id']; ?>">
                  <input id="todo-<?php echo $todo['id']; ?>" type="checkbox" name="status" onclick="this.form.submit()" <?php echo $todo['status'] == 'completed' ? 'checked' : ''; ?>>
                  <span><?php echo htmlspecialchars($todo['task']); ?></span>
                </label>
                <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                <input type="hidden" name="update_todo" value="1">
              </form>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>

      <!-- Show an empty image if no todos exist -->
      <?php if (empty($todos)): ?>
        <img class="empty-image" src="./empty.svg" alt="No todos">
      <?php endif; ?>
    </div>
  </div>

  <script src="./script.js"></script>
</body>
</html>