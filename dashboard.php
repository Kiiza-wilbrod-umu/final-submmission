<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] === 'admin') {
    header("Location: Admin/index.php");
    exit();
}

include("db_connection.php");

$message = '';
$error = '';
$search = trim($_GET['search'] ?? '');

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] === 'admin') {
        // Admin actions
        if (isset($_POST['add_candidate'])) {
            $name = trim($_POST['candidate_name']);
            $description = trim($_POST['candidate_description']);
            $image_path = '';
            if (empty($name)) {
                $error = "Candidate name is required.";
            } else {
                // Handle image upload
                if (isset($_FILES['candidate_image']) && $_FILES['candidate_image']['error'] == 0) {
                    $target_dir = "uploads/";
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0755, true);
                    }
                    $target_file = $target_dir . basename($_FILES["candidate_image"]["name"]);
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    if (in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                        if (move_uploaded_file($_FILES["candidate_image"]["tmp_name"], $target_file)) {
                            $image_path = $target_file;
                        } else {
                            $error = "Error uploading image.";
                        }
                    } else {
                        $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
                    }
                }
                if (!$error) {
                    $stmt = $conn->prepare("INSERT INTO candidates (name, description, image, created_by) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sssi", $name, $description, $image_path, $_SESSION['id']);
                    if ($stmt->execute()) {
                        $message = "Candidate added successfully.";
                    } else {
                        $error = "Error adding candidate: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        } elseif (isset($_POST['edit_candidate'])) {
            $id = (int)$_POST['candidate_id'];
            $name = trim($_POST['candidate_name']);
            $description = trim($_POST['candidate_description']);
            $image_path = $_POST['current_image'] ?? '';
            if (empty($name)) {
                $error = "Candidate name is required.";
            } else {
                // Handle image upload
                if (isset($_FILES['edit_candidate_image']) && $_FILES['edit_candidate_image']['error'] == 0) {
                    $target_dir = "uploads/";
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0755, true);
                    }
                    $target_file = $target_dir . basename($_FILES["edit_candidate_image"]["name"]);
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    if (in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                        if (move_uploaded_file($_FILES["edit_candidate_image"]["tmp_name"], $target_file)) {
                            $image_path = $target_file;
                        } else {
                            $error = "Error uploading image.";
                        }
                    } else {
                        $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
                    }
                }
                if (!$error) {
                    $stmt = $conn->prepare("UPDATE candidates SET name = ?, description = ?, image = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $description, $image_path, $id);
                    if ($stmt->execute()) {
                        $message = "Candidate updated successfully.";
                    } else {
                        $error = "Error updating candidate: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        } elseif (isset($_POST['delete_candidate'])) {
            $id = (int)$_POST['candidate_id'];
            $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Candidate deleted successfully.";
            } else {
                $error = "Error deleting candidate: " . $stmt->error;
            }
            $stmt->close();
        } elseif (isset($_POST['edit_user'])) {
            $id = (int)$_POST['user_id'];
            $name = trim($_POST['user_name']);
            $email = trim($_POST['user_email']);
            $role = $_POST['user_role'];
            if (empty($name) || empty($email)) {
                $error = "Name and email are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $role, $id);
                if ($stmt->execute()) {
                    $message = "User updated successfully.";
                } else {
                    $error = "Error updating user: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif (isset($_POST['delete_user'])) {
            $id = (int)$_POST['user_id'];
            if ($id == $_SESSION['id']) {
                $error = "You cannot delete your own account.";
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "User deleted successfully.";
                } else {
                    $error = "Error deleting user: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif (isset($_POST['toggle_voting'])) {
            $open = isset($_POST['voting_open']) ? 1 : 0;
            $stmt = $conn->prepare("UPDATE voting_config SET voting_open = ?, updated_by = ? WHERE id = 1");
            $stmt->bind_param("ii", $open, $_SESSION['id']);
            if ($stmt->execute()) {
                $message = "Voting " . ($open ? "opened" : "closed") . " successfully.";
            } else {
                $error = "Error updating voting status: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        // User voting
        if (isset($_POST['vote'])) {
            $candidate_id = (int)$_POST['candidate_id'];
            // Check if voting is open
            $config = $conn->query("SELECT voting_open FROM voting_config WHERE id = 1")->fetch_assoc();
            if (!$config['voting_open']) {
                $error = "Voting is currently closed.";
            } else {
                // Check if user already voted
                $stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ?");
                $stmt->bind_param("i", $_SESSION['id']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = "You have already voted.";
                } else {
                    // Insert vote
                    $stmt = $conn->prepare("INSERT INTO votes (user_id, candidate_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $_SESSION['id'], $candidate_id);
                    if ($stmt->execute()) {
                        // Update has_voted
                        $conn->query("UPDATE users SET has_voted = TRUE WHERE id = " . $_SESSION['id']);
                        $message = "Your vote has been recorded successfully.";
                    } else {
                        $error = "Error recording vote: " . $stmt->error;
                    }
                }
                $stmt->close();
            }
        }
    }
}

// Get voting config
$config = $conn->query("SELECT voting_open FROM voting_config WHERE id = 1")->fetch_assoc();
$voting_open = $config['voting_open'];

// Get candidates
$imageColumnExists = false;
$checkImageColumn = $conn->query("SHOW COLUMNS FROM candidates LIKE 'image'");
if ($checkImageColumn && $checkImageColumn->num_rows > 0) {
    $imageColumnExists = true;
}

if ($search !== '') {
    $like = "%{$search}%";
    if ($imageColumnExists) {
        $stmt = $conn->prepare("SELECT id, name, description, image FROM candidates WHERE name LIKE ? ORDER BY name");
    } else {
        $stmt = $conn->prepare("SELECT id, name, description FROM candidates WHERE name LIKE ? ORDER BY name");
    }
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $candidates = $stmt->get_result();
    $stmt->close();
} else {
    if ($imageColumnExists) {
        $candidates = $conn->query("SELECT id, name, description, image FROM candidates ORDER BY name");
    } else {
        $candidates = $conn->query("SELECT id, name, description FROM candidates ORDER BY name");
    }
}

// Get vote tally if admin
$tally = [];
$users = [];
if ($_SESSION['role'] === 'admin') {
    $result = $conn->query("SELECT c.name, COUNT(v.id) as votes FROM candidates c LEFT JOIN votes v ON c.id = v.candidate_id GROUP BY c.id ORDER BY votes DESC");
    while ($row = $result->fetch_assoc()) {
        $tally[] = $row;
    }
    $users = $conn->query("SELECT id, name, email, role, has_voted FROM users ORDER BY name");
}

// Check if user has voted
$has_voted = false;
$candidate_votes = [];
if ($_SESSION['role'] !== 'admin') {
    $stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $has_voted = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    // Get vote counts for each candidate
    $vote_result = $conn->query("SELECT candidate_id, COUNT(*) as vote_count FROM votes GROUP BY candidate_id");
    while ($row = $vote_result->fetch_assoc()) {
        $candidate_votes[$row['candidate_id']] = $row['vote_count'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Student Guild Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom styles for additional responsiveness if needed */
        @media (max-width: 768px) {
            .card { margin-bottom: 15px; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-warning" style="border-bottom: 2px solid red;">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
            <a href="dashboard.php" class="btn btn-outline-dark">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h1>Student Guild Voting System</h1>
        <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="mb-4">
            <form method="GET" action="dashboard.php" class="d-flex">
                <input class="form-control me-2" type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search candidates...">
                <button class="btn btn-outline-success" type="submit">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($_SESSION['role'] === 'admin'): ?>
            <!-- Admin Panel -->
            <h2>Vote Tally</h2>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Candidate</th>
                        <th>Votes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tally as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['votes']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h2>Manage Candidates</h2>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($candidate = $candidates->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $candidate['id']; ?></td>
                            <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['description']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-2" onclick="editCandidate(<?php echo $candidate['id']; ?>, '<?php echo addslashes($candidate['name']); ?>', '<?php echo addslashes($candidate['description']); ?>', '<?php echo addslashes($candidate['image'] ?? ''); ?>')">Edit</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                    <button type="submit" name="delete_candidate" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <h3>Add New Candidate</h3>
            <form method="POST" enctype="multipart/form-data" class="mb-4">
                <div class="mb-3">
                    <label for="candidate_name" class="form-label">Name:</label>
                    <input type="text" class="form-control" id="candidate_name" name="candidate_name" required>
                </div>
                <div class="mb-3">
                    <label for="candidate_description" class="form-label">Description:</label>
                    <textarea class="form-control" id="candidate_description" name="candidate_description"></textarea>
                </div>
                <div class="mb-3">
                    <label for="candidate_image" class="form-label">Image:</label>
                    <input type="file" class="form-control" id="candidate_image" name="candidate_image" accept="image/*">
                </div>
                <button type="submit" name="add_candidate" class="btn btn-primary">Add Candidate</button>
            </form>
            
            <h3>Edit Candidate</h3>
            <form method="POST" enctype="multipart/form-data" id="editForm" style="display:none;" class="mb-4">
                <input type="hidden" name="candidate_id" id="edit_candidate_id">
                <input type="hidden" name="current_image" id="edit_current_image">
                <div class="mb-3">
                    <label for="edit_candidate_name" class="form-label">Name:</label>
                    <input type="text" class="form-control" id="edit_candidate_name" name="candidate_name" required>
                </div>
                <div class="mb-3">
                    <label for="edit_candidate_description" class="form-label">Description:</label>
                    <textarea class="form-control" id="edit_candidate_description" name="candidate_description"></textarea>
                </div>
                <div class="mb-3">
                    <label for="edit_candidate_image" class="form-label">Image (leave blank to keep current):</label>
                    <input type="file" class="form-control" id="edit_candidate_image" name="edit_candidate_image" accept="image/*">
                </div>
                <button type="submit" name="edit_candidate" class="btn btn-warning">Update Candidate</button>
                <button type="button" class="btn btn-secondary ms-2" onclick="cancelEdit()">Cancel</button>
            </form>
            
            <h3>Voting Control</h3>
            <form method="POST" class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="voting_open" value="1" id="voting_open" <?php echo $voting_open ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="voting_open">
                        Voting Open
                    </label>
                </div>
                <button type="submit" name="toggle_voting" class="btn btn-success mt-2">Update Voting Status</button>
            </form>
            
            <h2>Manage Users</h2>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Voted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo $user['has_voted'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-2" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo $user['role']; ?>')">Edit</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <h3>Edit User</h3>
            <form method="POST" id="editUserForm" style="display:none;" class="mb-4">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="mb-3">
                    <label for="edit_user_name" class="form-label">Name:</label>
                    <input type="text" class="form-control" id="edit_user_name" name="user_name" required>
                </div>
                <div class="mb-3">
                    <label for="edit_user_email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="edit_user_email" name="user_email" required>
                </div>
                <div class="mb-3">
                    <label for="edit_user_role" class="form-label">Role:</label>
                    <select class="form-control" id="edit_user_role" name="user_role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="edit_user" class="btn btn-warning">Update User</button>
                <button type="button" class="btn btn-secondary ms-2" onclick="cancelEditUser()">Cancel</button>
            </form>
            
        <?php else: ?>
            <!-- Student Voting -->
            <?php if ($has_voted): ?>
                <div class="message">You have already voted. Thank you for participating!</div>
            <?php elseif (!$voting_open): ?>
                <div class="error">Voting is currently closed.</div>
            <?php else: ?>
                <h2>Cast Your Vote</h2>
                <p>Select one candidate to vote for:</p>
                <form method="POST">
                    <div class="row">
                        <?php 
                        $candidates->data_seek(0); // Reset pointer
                        while ($candidate = $candidates->fetch_assoc()): 
                        ?>
                            <div class="col-md-4 col-sm-6 mb-3">
                                <div class="card h-100">
                                    <?php if (!empty($candidate['image'] ?? '')): ?>
                                        <img src="<?php echo htmlspecialchars($candidate['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($candidate['name']); ?>" style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">No image available</div>
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($candidate['name']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($candidate['description']); ?></p>
                                        <p class="card-text mt-auto"><small class="text-muted">Current Votes: <?php echo $candidate_votes[$candidate['id']] ?? 0; ?></small></p>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="radio" name="candidate_id" value="<?php echo $candidate['id']; ?>" id="candidate_<?php echo $candidate['id']; ?>" required>
                                            <label class="form-check-label" for="candidate_<?php echo $candidate['id']; ?>">
                                                Vote for this candidate
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <button type="submit" name="vote" class="btn btn-primary mt-3" onclick="return confirmVote()">Submit Vote</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCandidate(id, name, description, image) {
            document.getElementById('edit_candidate_id').value = id;
            document.getElementById('edit_candidate_name').value = name;
            document.getElementById('edit_candidate_description').value = description;
            document.getElementById('edit_current_image').value = image;
            document.getElementById('editForm').style.display = 'block';
        }
        function cancelEdit() {
            document.getElementById('editForm').style.display = 'none';
        }
        function editUser(id, name, email, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_user_name').value = name;
            document.getElementById('edit_user_email').value = email;
            document.getElementById('edit_user_role').value = role;
            document.getElementById('editUserForm').style.display = 'block';
        }
        function cancelEditUser() {
            document.getElementById('editUserForm').style.display = 'none';
        }
        function confirmVote() {
            return confirm('Are you sure you want to cast your vote? This action cannot be undone.');
        }
    </script>
</body>
</html>