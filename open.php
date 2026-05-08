<?php
include("db_connection.php");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered. Please login instead.";
        } else {
            $pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users(name, email, password, role) VALUES(?, ?, ?, ?)");
            $role = 'user';
            $stmt->bind_param("ssss", $name, $email, $pass, $role);

            if ($stmt->execute()) {
                $success = "Registered! <a href='login.php'>Login here</a>";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home - Student Guild Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-light bg-warning" style="border-bottom: 2px solid red;">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Student Guild Voting System</span>
            <a href="index.php" class="btn btn-outline-dark">Home</a>
            <a href="login.php" class="btn btn-outline-dark">Login</a>
            <a href="register.php" class="btn btn-outline-dark">Register</a>
        </div>
    </nav>

    <div class="card mb-4">
        <div class="card-body">
            <h1 class="card-title">Welcome to Student Guild Voting System</h1>
            <p class="card-text">This is a secure system allowing registered students to vote for guild leadership, with one vote enforced per account.</p>
            <p class="card-text">Students can log in and cast a vote for one candidate. The system prevents a second vote from the same account.</p>
            <p class="card-text">Candidates are stored in the database and displayed dynamically.</p>
            <p class="card-text">Admin can log in and view the current vote tally, updated from the database.</p>
            <p class="card-text">Admin can add or remove candidates before voting opens.</p>
            <p class="card-text">Create a new account by filling the form below or <a href="login.php">login here</a> if you already have an account.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>
        <h2>Register New Account</h2>
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name:</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>
    <?php endif; ?>

    <div class="mt-4">
        <a href="login.php" class="btn btn-secondary me-2">Login</a>
        <a href="index.php" class="btn btn-secondary">Home</a>
    </div>
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
