<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWith('login.php', 'Please login to access your dashboard.', 'warning');
}

$conn = getDBConnection();

// Get user's upcoming registrations
$upcoming_registrations = [];
$stmt = $conn->prepare("
    SELECT r.*, e.title, e.date, e.location, e.capacity,
           (SELECT COUNT(*) FROM registrations r2 WHERE r2.event_id = e.event_id AND r2.status = 'confirmed') as registered_count
    FROM registrations r 
    JOIN events e ON r.event_id = e.event_id 
    WHERE r.email = ? 
    AND e.date >= CURRENT_TIMESTAMP 
    AND r.status = 'confirmed'
    ORDER BY e.date ASC
");
$stmt->bind_param("s", $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $upcoming_registrations[] = $row;
}

// Get user's past registrations
$past_registrations = [];
$stmt = $conn->prepare("
    SELECT r.*, e.title, e.date, e.location
    FROM registrations r 
    JOIN events e ON r.event_id = e.event_id 
    WHERE r.email = ? 
    AND e.date < CURRENT_TIMESTAMP
    ORDER BY e.date DESC
");
$stmt->bind_param("s", $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $past_registrations[] = $row;
}

// Get user's waitlisted registrations
$waitlisted_registrations = [];
$stmt = $conn->prepare("
    SELECT r.*, e.title, e.date, e.location, e.capacity,
           (SELECT COUNT(*) FROM registrations r2 WHERE r2.event_id = e.event_id AND r2.status = 'confirmed') as registered_count
    FROM registrations r 
    JOIN events e ON r.event_id = e.event_id 
    WHERE r.email = ? 
    AND e.date >= CURRENT_TIMESTAMP 
    AND r.status = 'waitlisted'
    ORDER BY e.date ASC
");
$stmt->bind_param("s", $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $waitlisted_registrations[] = $row;
}

// Get user's name from the database
$stmt = $conn->prepare("SELECT name FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_name = $user ? $user['name'] : 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Event Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Event Registration System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Browse Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php echo displayMessage(); ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p class="lead">Manage your event registrations and view your event history.</p>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="bi bi-calendar-event text-primary"></i> Upcoming Events</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_registrations)): ?>
                            <p class="text-muted">You have no upcoming events.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcoming_registrations as $reg): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reg['title']); ?></td>
                                                <td><?php echo formatDate($reg['date']); ?></td>
                                                <td><?php echo htmlspecialchars($reg['location']); ?></td>
                                                <td>
                                                    <span class="badge bg-success">Confirmed</span>
                                                </td>
                                                <td>
                                                    <a href="../register.php?event_id=<?php echo $reg['event_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i> View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Waitlisted Events -->
        <?php if (!empty($waitlisted_registrations)): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="bi bi-hourglass-split text-warning"></i> Waitlisted Events</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($waitlisted_registrations as $reg): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reg['title']); ?></td>
                                            <td><?php echo formatDate($reg['date']); ?></td>
                                            <td><?php echo htmlspecialchars($reg['location']); ?></td>
                                            <td>
                                                <span class="badge bg-warning">Waitlisted</span>
                                            </td>
                                            <td>
                                                <a href="../register.php?event_id=<?php echo $reg['event_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Past Events -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="bi bi-clock-history text-secondary"></i> Past Events</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($past_registrations)): ?>
                            <p class="text-muted">You have no past events.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($past_registrations as $reg): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reg['title']); ?></td>
                                                <td><?php echo formatDate($reg['date']); ?></td>
                                                <td><?php echo htmlspecialchars($reg['location']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'confirmed' => 'success',
                                                        'cancelled' => 'danger'
                                                    ][$reg['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($reg['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Event Registration System. All rights reserved.</p>
            <p class="mb-0">Monica Kabwe</p>
            <p class="mb-0">SIN: 2403443685</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php closeDBConnection($conn); ?> 