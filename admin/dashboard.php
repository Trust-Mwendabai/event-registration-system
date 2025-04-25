<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWith('login.php', 'Please login to access the dashboard.', 'warning');
}

$conn = getDBConnection();

// Get total events and registrations
$stats = [
    'total_events' => 0,
    'total_registrations' => 0,
    'upcoming_events' => 0
];

$result = $conn->query("SELECT 
    (SELECT COUNT(*) FROM events) as total_events,
    (SELECT COUNT(*) FROM registrations) as total_registrations,
    (SELECT COUNT(*) FROM events WHERE date >= CURRENT_TIMESTAMP) as upcoming_events
");

if ($result) {
    $stats = $result->fetch_assoc();
}

// Get upcoming events
$upcoming_events = [];
$result = $conn->query("SELECT * FROM events WHERE date >= CURRENT_TIMESTAMP ORDER BY date ASC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $upcoming_events[] = $row;
    }
}

// Get recent registrations
$recent_registrations = [];
$result = $conn->query("
    SELECT r.*, e.title as event_title 
    FROM registrations r 
    JOIN events e ON r.event_id = e.event_id 
    ORDER BY r.registration_date DESC 
    LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_registrations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Event Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Manage Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registrations.php">View Registrations</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php" target="_blank">View Site</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php echo displayMessage(); ?>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Events</h5>
                        <h2 class="display-4"><?php echo $stats['total_events']; ?></h2>
                        <p class="mb-0">
                            <i class="bi bi-calendar-event"></i>
                            <?php echo $stats['upcoming_events']; ?> upcoming
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Registrations</h5>
                        <h2 class="display-4"><?php echo $stats['total_registrations']; ?></h2>
                        <p class="mb-0">
                            <i class="bi bi-people"></i>
                            Across all events
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>
                        <div class="d-grid gap-2">
                            <a href="events.php?action=add" class="btn btn-light">
                                <i class="bi bi-plus-circle"></i> Add New Event
                            </a>
                            <a href="registrations.php" class="btn btn-light">
                                <i class="bi bi-table"></i> View All Registrations
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Upcoming Events</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcoming_events)): ?>
                            <div class="list-group">
                                <?php foreach ($upcoming_events as $event): ?>
                                    <a href="events.php?action=edit&id=<?php echo $event['event_id']; ?>" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h6>
                                            <small><?php echo formatDate($event['date']); ?></small>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No upcoming events.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Registrations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_registrations)): ?>
                            <div class="list-group">
                                <?php foreach ($recent_registrations as $registration): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($registration['name']); ?></h6>
                                            <small><?php echo formatDate($registration['registration_date']); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <small class="text-muted">
                                                Event: <?php echo htmlspecialchars($registration['event_title']); ?>
                                            </small>
                                        </p>
                                        <small>
                                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($registration['email']); ?>
                                            &nbsp;|&nbsp;
                                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($registration['phone']); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No recent registrations.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php closeDBConnection($conn); ?> 