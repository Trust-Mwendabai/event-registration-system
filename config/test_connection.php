<?php
require_once 'database.php';

// Test database connection
$conn = getDBConnection();
if ($conn) {
    echo "Database connection successful!<br>";
} else {
    echo "Database connection failed!<br>";
    exit;
}

// Test users table
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "Users table exists!<br>";
    
    // Check if there are any users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "Number of users in database: " . $row['count'] . "<br>";
    
    // List all users
    $result = $conn->query("SELECT user_id, name, email FROM users");
    if ($result->num_rows > 0) {
        echo "<br>List of users:<br>";
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['user_id'] . ", Name: " . $row['name'] . ", Email: " . $row['email'] . "<br>";
        }
    } else {
        echo "No users found in the database.<br>";
    }
} else {
    echo "Users table does not exist!<br>";
}

closeDBConnection($conn);
?> 