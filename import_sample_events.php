<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get database connection
$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed");
}

// Read and execute the sample events SQL file
$sql = file_get_contents('database/sample_events.sql');

if ($conn->multi_query($sql)) {
    echo "Sample events imported successfully!\n";
    
    // Get count of imported events
    $result = $conn->query("SELECT COUNT(*) as count FROM events");
    $count = $result->fetch_assoc()['count'];
    echo "Total events in database: $count\n";
    
    // Display some statistics
    $result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN capacity IS NOT NULL THEN 1 ELSE 0 END) as with_capacity,
            SUM(CASE WHEN date >= CURRENT_TIMESTAMP THEN 1 ELSE 0 END) as upcoming
        FROM events
    ");
    $stats = $result->fetch_assoc();
    
    echo "\nEvent Statistics:\n";
    echo "Total Events: {$stats['total']}\n";
    echo "Events with Capacity Limit: {$stats['with_capacity']}\n";
    echo "Upcoming Events: {$stats['upcoming']}\n";
    
} else {
    echo "Error importing sample events: " . $conn->error;
}

closeDBConnection($conn);
?> 