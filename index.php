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
    <nav class="navbar navbar-light bg-warning">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Student Guild Voting System</span>
            <div>
                <a href="login.php" class="btn btn-outline-dark me-2">Login</a>
                <a href="register.php" class="btn btn-outline-dark">Register</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4">Welcome to Student Guild Voting System</h1>
                        <p class="card-text"><strong>a Secure Voting Platform</strong></p>
                        <p class="card-text">This is a secure system allowing registered students to vote for guild leadership, with one vote enforced per account.</p>
                        <ul class="card-text">
                            <li>Students can log in and cast a vote for one candidate</li>
                            <li>The system prevents a second vote from the same account</li>
                            <li>Candidates are stored in the database and displayed dynamically</li>
                            <li>Admin can log in and view the current vote tally, updated from the database</li>
                            <li>Admin can add or remove candidates before voting opens</li>
                        </ul>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Create New Account</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
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
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Register</button>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
