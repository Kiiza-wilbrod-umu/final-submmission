 Installation and Setup
Follow these steps,to run this project locally:

Ensure you have a local server environment installed:

XAMPP (Recommended for Windows/Linux/macOS)

PHP (version 7.4 or higher)

MySQL/MariaDB

Database Setup
Open phpMyAdmin (usually http://localhost/phpmyadmin).

Create a new database named course_db.

Click on the SQL tab.

Copy the SQL code provided in the database.sql file (or the schema provided above) and paste it into the SQL box.

Click Go to execute the queries and create the tables.
SQL TABLES 
CREATE DATABASE course_db;
USE course_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    has_voted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    candidate_id INT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    UNIQUE KEY unique_vote (user_id)
);

CREATE TABLE voting_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voting_open BOOLEAN DEFAULT FALSE,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

INSERT INTO voting_config (id, voting_open) VALUES (1, FALSE)
    ON DUPLICATE KEY UPDATE voting_open = voting_open;

CREATE INDEX idx_email ON users(email);

INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/iu', 'admin'),
('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/iu', 'user')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO candidates (name, description, created_by) VALUES
('Candidate A', 'Experienced leader with vision for the guild.', 1),
('Candidate B', 'Innovative thinker focused on student welfare.', 1)
ON DUPLICATE KEY UPDATE name = name;

Connect the Application to the Database
In your project folder, ensure your PHP connection file (e.g., db_connect.php or config.php) matches your local credentials:

PHP
<?php
$host = "localhost";
$user = "root";
$pass = ""; // Default XAMPP password is empty
$dbname = "course_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
Running the Site
Move the project folder to your server's root directory:

XAMPP: C:/xampp/htdocs/(web final project)

WAMP: C:/wamp64/www/(web final project)

Start Apache and MySQL from your Control Panel.

Open your browser and type: http://localhost/web final project/

 Default Credentials
Admin: kiizawilbrodAdmin@gmail.com
password: 123456789

User: james@gmail.com 
password:123456
note; u cand create a user email

Password: (The hashed password used in your SQL dump)