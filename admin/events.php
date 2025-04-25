<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWith('login.php', 'Please login to access this page.', 'warning');
}

$conn = getDBConnection();
$action = $_GET['action'] ?? 'list';
$errors = [];
$success = false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $date = sanitize($_POST['date'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $capacity = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null;

        if (empty($title) || empty($date) || empty($location)) {
            $errors[] = 'All fields except description and capacity are required.';
        }

        if ($capacity !== null && $capacity < 1) {
            $errors[] = 'Capacity must be at least 1 if specified.';
        }

        if (empty($errors)) {
            if (isset($_POST['event_id'])) {
                // Update existing event
                $stmt = $conn->prepare("UPDATE events SET title = ?, date = ?, location = ?, description = ?, capacity = ? WHERE event_id = ?");
                $stmt->bind_param("ssssii", $title, $date, $location, $description, $capacity, $_POST['event_id']);
                if ($stmt->execute()) {
                    redirectWith('events.php', 'Event updated successfully.', 'success');
                } else {
                    $errors[] = 'Failed to update event.';
                }
            } else {
                // Create new event
                $stmt = $conn->prepare("INSERT INTO events (title, date, location, description, capacity) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $title, $date, $location, $description, $capacity);
                if ($stmt->execute()) {
                    redirectWith('events.php', 'Event created successfully.', 'success');
                } else {
                    $errors[] = 'Failed to create event.';
                }
            }
        }
    }
}

// Handle event deletion
if ($action === 'delete' && isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    if ($stmt->execute()) {
        redirectWith('events.php', 'Event deleted successfully.', 'success');
    } else {
        $errors[] = 'Failed to delete event.';
    }
}

// Get event for editing
$event = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    
    if (!$event) {
        redirectWith('events.php', 'Event not found.', 'danger');
    }
}

// Get all events for listing
$events = [];
if ($action === 'list') {
    $result = $conn->query("SELECT * FROM events ORDER BY date DESC");
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Event Registration System</title>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="events.php">Manage Events</a>
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

    <div class="container py-4">
        <?php echo displayMessage(); ?>

        <?php if ($action === 'list'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Manage Events</h1>
                <a href="?action=add" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Event
                </a>
            </div>

            <?php if (empty($events)): ?>
                <div class="alert alert-info">No events found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $evt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($evt['title']); ?></td>
                                    <td><?php echo formatDate($evt['date']); ?></td>
                                    <td><?php echo htmlspecialchars($evt['location']); ?></td>
                                    <td>
                                        <?php if ($evt['capacity'] === null): ?>
                                            <span class="text-muted">Unlimited</span>
                                        <?php else: ?>
                                            <?php 
                                            $remaining = getRemainingSlots($conn, $evt['event_id']);
                                            echo $remaining . ' / ' . $evt['capacity'];
                                            ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (strtotime($evt['date']) > time()): ?>
                                            <span class="badge bg-success">Upcoming</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Past</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?action=edit&id=<?php echo $evt['event_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="?action=delete&id=<?php echo $evt['event_id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this event?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                            <a href="<?php echo generateEventQRCode($evt['event_id']); ?>" 
                                               class="btn btn-sm btn-info" target="_blank"
                                               title="Download QR Code">
                                                <i class="bi bi-qr-code"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <h1><?php echo $action === 'edit' ? 'Edit Event' : 'Add New Event'; ?></h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <?php if ($event): ?>
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="title" class="form-label">Event Title</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                   value="<?php echo $event ? htmlspecialchars($event['title']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="date" class="form-label">Event Date and Time</label>
                            <input type="datetime-local" class="form-control" id="date" name="date" required
                                   value="<?php echo $event ? date('Y-m-d\TH:i', strtotime($event['date'])) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" required
                                   value="<?php echo $event ? htmlspecialchars($event['location']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php 
                                echo $event ? htmlspecialchars($event['description']) : ''; 
                            ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity (Optional)</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" 
                                   value="<?php echo $event ? $event['capacity'] : ''; ?>" 
                                   min="1" placeholder="Leave empty for unlimited capacity">
                            <div class="form-text">Set a maximum number of registrations allowed for this event.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $action === 'edit' ? 'Update Event' : 'Create Event'; ?>
                            </button>
                            <a href="events.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php closeDBConnection($conn); ?> 