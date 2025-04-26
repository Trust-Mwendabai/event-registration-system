<?php
require_once 'database.php';

$conn = getDBConnection();

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Drop existing tables to ensure clean setup
$tables = ['registrations', 'events', 'users', 'admins'];
foreach ($tables as $table) {
    $conn->query("DROP TABLE IF EXISTS $table");
    echo "Dropped table $table if it existed<br>";
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Create events table
$sql = "CREATE TABLE IF NOT EXISTS events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    capacity INT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'General',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Events table created successfully<br>";
} else {
    echo "Error creating events table: " . $conn->error . "<br>";
}

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
)";

if ($conn->query($sql)) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create registrations table
$sql = "CREATE TABLE IF NOT EXISTS registrations (
    reg_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    event_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('confirmed', 'waitlisted', 'cancelled') DEFAULT 'confirmed',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Registrations table created successfully<br>";
} else {
    echo "Error creating registrations table: " . $conn->error . "<br>";
}

// Create admins table
$sql = "CREATE TABLE IF NOT EXISTS admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Admins table created successfully<br>";
} else {
    echo "Error creating admins table: " . $conn->error . "<br>";
}

// Insert sample events
$sample_events = [
    [
        'title' => 'Tech Conference 2024',
        'description' => 'Annual technology conference featuring the latest innovations and industry leaders.',
        'date' => '2024-03-15 09:00:00',
        'location' => 'Convention Center, New York',
        'capacity' => 500,
        'category' => 'Conference'
    ],
    [
        'title' => 'Web Development Workshop',
        'description' => 'Hands-on workshop covering modern web development techniques and best practices.',
        'date' => '2024-02-20 10:00:00',
        'location' => 'Tech Hub, San Francisco',
        'capacity' => 50,
        'category' => 'Workshop'
    ],
    [
        'title' => 'Data Science Summit',
        'description' => 'Conference focusing on data science, machine learning, and artificial intelligence.',
        'date' => '2024-01-25 08:00:00',
        'location' => 'Business Center, Boston',
        'capacity' => 300,
        'category' => 'Conference'
    ],
    [
        'title' => 'Startup Networking Event',
        'description' => 'Networking event for entrepreneurs and startup founders to connect and share ideas.',
        'date' => '2024-02-01 18:00:00',
        'location' => 'Innovation Hub, Seattle',
        'capacity' => 200,
        'category' => 'Networking'
    ],
    [
        'title' => 'Mobile App Development Workshop',
        'description' => 'Workshop on mobile app development for iOS and Android platforms.',
        'date' => '2024-01-15 09:00:00',
        'location' => 'Tech Campus, Austin',
        'capacity' => 75,
        'category' => 'Workshop'
    ],
    [
        'title' => 'AI and Machine Learning Conference',
        'description' => 'Explore the latest advancements in AI and machine learning technologies.',
        'date' => '2024-03-01 09:00:00',
        'location' => 'Research Park, Silicon Valley',
        'capacity' => 400,
        'category' => 'Conference'
    ],
    [
        'title' => 'Cybersecurity Workshop',
        'description' => 'Learn about the latest cybersecurity threats and defense strategies.',
        'date' => '2024-02-10 10:00:00',
        'location' => 'Security Center, Washington DC',
        'capacity' => 100,
        'category' => 'Workshop'
    ],
    [
        'title' => 'Cloud Computing Summit',
        'description' => 'Discover the future of cloud computing and infrastructure.',
        'date' => '2024-03-10 08:00:00',
        'location' => 'Tech Center, Chicago',
        'capacity' => 350,
        'category' => 'Conference'
    ]
];

$stmt = $conn->prepare("INSERT INTO events (title, description, date, location, capacity, category) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($sample_events as $event) {
    $stmt->bind_param("ssssis", $event['title'], $event['description'], $event['date'], $event['location'], $event['capacity'], $event['category']);
    if ($stmt->execute()) {
        echo "Added sample event: {$event['title']}<br>";
    } else {
        echo "Error adding sample event: " . $stmt->error . "<br>";
    }
}

// Insert default admin if not exists
$sql = "SELECT admin_id FROM admins WHERE username = 'admin'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO admins (username, password) VALUES ('admin', '$password')";
    if ($conn->query($sql)) {
        echo "Default admin account created successfully<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating default admin account: " . $conn->error . "<br>";
    }
}

closeDBConnection($conn);
echo "Database setup completed!";
?> 