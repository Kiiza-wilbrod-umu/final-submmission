<?php
// Database Setup Script for Student Guild Voting System
// Run this once to initialize the database

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "course_db";

// Create connection without database
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($dbname);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    has_voted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create candidates table
$sql = "CREATE TABLE IF NOT EXISTS candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Candidates table created successfully<br>";
} else {
    echo "Error creating candidates table: " . $conn->error . "<br>";
}

// Ensure image column exists for existing installs
$checkImageColumn = $conn->query("SHOW COLUMNS FROM candidates LIKE 'image'");
if (!($checkImageColumn && $checkImageColumn->num_rows > 0)) {
    $sql = "ALTER TABLE candidates ADD COLUMN image VARCHAR(255)";
    if ($conn->query($sql) === TRUE) {
        echo "Candidate image column added successfully<br>";
    } else {
        echo "Error adding candidate image column: " . $conn->error . "<br>";
    }
} else {
    echo "Candidate image column verified<br>";
}

// Create votes table
$sql = "CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    candidate_id INT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    UNIQUE KEY unique_vote (user_id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Votes table created successfully<br>";
} else {
    echo "Error creating votes table: " . $conn->error . "<br>";
}

// Create voting_config table
$sql = "CREATE TABLE IF NOT EXISTS voting_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voting_open BOOLEAN DEFAULT FALSE,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Voting config table created successfully<br>";
} else {
    echo "Error creating voting config table: " . $conn->error . "<br>";
}

// Insert initial voting config
$sql = "INSERT IGNORE INTO voting_config (id, voting_open) VALUES (1, FALSE)";
if ($conn->query($sql) === TRUE) {
    echo "Voting config initialized<br>";
} else {
    echo "Error initializing voting config: " . $conn->error . "<br>";
}

// Create index on email if it does not already exist
$result = $conn->query("SELECT COUNT(*) AS idx_exists FROM information_schema.statistics WHERE table_schema = '$dbname' AND table_name = 'users' AND index_name = 'idx_email'");
$idx_exists = $result ? $result->fetch_assoc()['idx_exists'] : 0;
if ($idx_exists == 0) {
    $sql = "CREATE INDEX idx_email ON users(email)";
    if ($conn->query($sql) === TRUE) {
        echo "Index on email created successfully<br>";
    } else {
        echo "Error creating index: " . $conn->error . "<br>";
    }
} else {
    echo "Index idx_email already exists<br>";
}

// Insert sample data
$hashed_password = password_hash('password', PASSWORD_DEFAULT);

$sql = "INSERT IGNORE INTO users (id, name, email, password, role) VALUES 
(1, 'Admin User', 'admin@example.com', '$hashed_password', 'admin'),
(2, 'Test User', 'test@example.com', '$hashed_password', 'user')";
if ($conn->query($sql) === TRUE) {
    echo "Sample users inserted successfully<br>";
} else {
    echo "Error inserting sample users: " . $conn->error . "<br>";
}

$sql = "INSERT IGNORE INTO candidates (id, name, description, created_by) VALUES 
(1, 'Candidate A', 'Experienced leader with vision for the guild.', 1),
(2, 'Candidate B', 'Innovative thinker focused on student welfare.', 1)";
if ($conn->query($sql) === TRUE) {
    echo "Sample candidates inserted successfully<br>";
} else {
    echo "Error inserting sample candidates: " . $conn->error . "<br>";
}

$conn->close();

echo "<br><strong>Database setup completed!</strong><br>";
echo "You can now access the application at <a href='index.php'>index.php</a><br>";
echo "Admin login: admin@example.com / password<br>";
echo "Test user login: test@example.com / password<br>";
?>