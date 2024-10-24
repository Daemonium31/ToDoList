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
$filter = '';  // Default: no filter
$search = '';
$username = $_SESSION['username']; // Get the logged-in username

// Fetch the user's email from the username
$stmt = $conn->prepare("SELECT email FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_email);
$stmt->fetch();
$stmt->close();

// Add todo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_todo'])) {
    $todo = trim($_POST['todo']);
    if (!empty($todo)) {
        $stmt = $conn->prepare("INSERT INTO list (user_email, task, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ss", $user_email, $todo);
        $stmt->execute();
        $stmt->close();
    }
}

// Update status of todo
if (isset($_POST['update_todo'])) {
    $todo_id = $_POST['todo_id'];
    $status = isset($_POST['status']) ? 'completed' : 'pending';
    $stmt = $conn->prepare("UPDATE list SET status = ? WHERE id = ? AND user_email = ?");
    $stmt->bind_param("sis", $status, $todo_id, $user_email);
    $stmt->execute();
    $stmt->close();
}

// Delete todo
if (isset($_POST['delete_todo'])) {
    $todo_id = $_POST['todo_id'];
    $stmt = $conn->prepare("DELETE FROM list WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $todo_id, $user_email);
    $stmt->execute();
    $stmt->close();
}

// Delete all todos
if (isset($_POST['delete_all'])) {
    $stmt = $conn->prepare("DELETE FROM list WHERE user_email = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $stmt->close();
}

// Filter todos based on URL filter parameter
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];

    // Toggle filter if the same link is clicked
    if (isset($_SESSION['filter']) && $_SESSION['filter'] === $filter) {
        // Clear the filter
        unset($_SESSION['filter']);
        $filter = ''; // Set to empty for unfiltered
    } else {
        // Store the filter in the session
        if ($filter === 'completed' || $filter === 'pending') {
            $_SESSION['filter'] = $filter;
        }
    }
} elseif (isset($_SESSION['filter'])) {
    // Retrieve filter from session if it exists
    $filter = $_SESSION['filter'];
}

// Search todos
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Fetch todos from the database
$sql = "SELECT * FROM list WHERE user_email = ?";

if (!empty($filter)) {
    $sql .= " AND status = ?";
}
if (!empty($search)) {
    $sql .= " AND task LIKE ?";
}

$stmt = $conn->prepare($sql);

// Bind parameters based on filter and search values
if (!empty($filter) && !empty($search)) {
    $searchTerm = "%{$search}%";
    $stmt->bind_param("sss", $user_email, $filter, $searchTerm);
} elseif (!empty($filter)) {
    $stmt->bind_param("ss", $user_email, $filter);
} elseif (!empty($search)) {
    $searchTerm = "%{$search}%";
    $stmt->bind_param("ss", $user_email, $searchTerm);
} else {
    $stmt->bind_param("s", $user_email);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $todos[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>To Do List</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="todolist.css">
  <script>
    // Function to confirm deletion
    function confirmDelete() {
      return confirm("Are you sure you want to delete this task?");
    }
  </script>
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
  <div class="container">
    <h1>To do List</h1>
    
    <!-- Search Form -->
    <form action="" method="GET" class="search-form">
      <input class="todo-input" name="search" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit" class="search-button"><i class="fa fa-search"></i></button>
    </form>
    
    <div class="input-container">
      <!-- Form to add a new task -->
      <form action="" method="POST">
        <input class="todo-input" name="todo" placeholder="Add a new task..." required>
        <button type="submit" name="add_todo" class="add-button">
          <i class="fa fa-plus-circle"></i>
        </button>
      </form>
    </div>

    <div class="filters">
      <!-- Filter options for showing different task statuses -->
      <a href="?filter=completed" class="filter">
        <?php echo isset($_SESSION['filter']) && $_SESSION['filter'] === 'completed' ? 'Unfilter' : 'Complete'; ?>
      </a>
      <a href="?filter=pending" class="filter">
        <?php echo isset($_SESSION['filter']) && $_SESSION['filter'] === 'pending' ? 'Unfilter' : 'Incomplete'; ?>
      </a>
      <form action="" method="POST" class="delete-all-form" onsubmit="return confirmDelete();">
        <button type="submit" name="delete_all" class="delete-all">Delete All</button>
      </form>
    </div>

    <div class="todos-container">
      <ul class="todos">
        <!-- Loop through tasks and display them -->
        <?php if (empty($todos)): ?>
          <p>No tasks available. Make some!</p>
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

              <!-- Delete Form with JavaScript confirmation -->
              <form action="" method="POST" class="delete-form" onsubmit="return confirmDelete();">
                <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                <button type="submit" name="delete_todo" class="delete-btn"><i class="fa fa-times"></i></button>
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
