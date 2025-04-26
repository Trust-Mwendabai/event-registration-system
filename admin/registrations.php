<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWith('login.php', 'Please login to access this page.', 'warning');
}

$conn = getDBConnection();

// Handle registration deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $reg_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM registrations WHERE reg_id = ?");
    $stmt->bind_param("i", $reg_id);
    if ($stmt->execute()) {
        redirectWith('registrations.php', 'Registration deleted successfully.', 'success');
    } else {
        redirectWith('registrations.php', 'Failed to delete registration.', 'danger');
    }
}

// Handle registration status changes
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reg_id = (int)$_GET['id'];
    
    switch ($_GET['action']) {
        case 'confirm':
            if (confirmWaitlistedRegistration($conn, $reg_id)) {
                redirectWith('registrations.php', 'Registration confirmed successfully.', 'success');
            } else {
                redirectWith('registrations.php', 'Failed to confirm registration.', 'danger');
            }
            break;
            
        case 'cancel':
            if (cancelRegistration($conn, $reg_id)) {
                redirectWith('registrations.php', 'Registration cancelled successfully.', 'success');
            } else {
                redirectWith('registrations.php', 'Failed to cancel registration.', 'danger');
            }
            break;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM registrations WHERE reg_id = ?");
            $stmt->bind_param("i", $reg_id);
            if ($stmt->execute()) {
                redirectWith('registrations.php', 'Registration deleted successfully.', 'success');
            } else {
                redirectWith('registrations.php', 'Failed to delete registration.', 'danger');
            }
            break;
    }
}

// Get event filter
$event_filter = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;

// Get all events for filter dropdown
$events = [];
$result = $conn->query("SELECT event_id, title FROM events ORDER BY date DESC");
while ($row = $result->fetch_assoc()) {
    $events[$row['event_id']] = $row['title'];
}

// Get registrations with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query based on filter
$where_clause = $event_filter ? "WHERE r.event_id = $event_filter" : "";
$count_query = "SELECT COUNT(*) as total FROM registrations r $where_clause";
$result = $conn->query($count_query);
$total_rows = $result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Get registrations for current page
$query = "
    SELECT r.*, e.title as event_title, e.date as event_date 
    FROM registrations r 
    JOIN events e ON r.event_id = e.event_id 
    $where_clause
    ORDER BY r.registration_date DESC 
    LIMIT $offset, $per_page
";
$result = $conn->query($query);
$registrations = [];
while ($row = $result->fetch_assoc()) {
    $registrations[] = $row;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_query = "
        SELECT 
            e.title as event_title,
            e.date as event_date,
            r.name,
            r.email,
            r.phone,
            r.registration_date
        FROM registrations r 
        JOIN events e ON r.event_id = e.event_id
        " . ($event_filter ? "WHERE r.event_id = $event_filter" : "") . "
        ORDER BY r.registration_date DESC
    ";
    
    $result = $conn->query($export_query);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="registrations.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Event', 'Event Date', 'Name', 'Email', 'Phone', 'Registration Date']);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['event_title'],
            formatDate($row['event_date']),
            $row['name'],
            $row['email'],
            $row['phone'],
            formatDate($row['registration_date'])
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Registrations - Event Registration System</title>
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
                        <a class="nav-link" href="events.php">Manage Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="registrations.php">View Registrations</a>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>View Registrations</h1>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex gap-2">
                    <select name="event_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Events</option>
                        <?php foreach ($events as $id => $title): ?>
                            <option value="<?php echo $id; ?>" <?php echo $event_filter === $id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <a href="?export=csv<?php echo $event_filter ? "&event_id=$event_filter" : ''; ?>" 
                   class="btn btn-success">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
        </div>

        <?php if (empty($registrations)): ?>
            <div class="alert alert-info">No registrations found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Event</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($reg['event_title']); ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo formatDate($reg['event_date']); ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                <td><?php echo formatPhoneNumber($reg['phone']); ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'confirmed' => 'success',
                                        'waitlisted' => 'warning',
                                        'cancelled' => 'danger'
                                    ][$reg['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($reg['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($reg['registration_date']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($reg['status'] === 'waitlisted'): ?>
                                            <a href="?action=confirm&id=<?php echo $reg['reg_id']; ?>" 
                                               class="btn btn-sm btn-success"
                                               onclick="return confirm('Confirm this registration?')">
                                                <i class="bi bi-check-circle"></i> Confirm
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($reg['status'] !== 'cancelled'): ?>
                                            <a href="?action=cancel&id=<?php echo $reg['reg_id']; ?>" 
                                               class="btn btn-sm btn-warning"
                                               onclick="return confirm('Cancel this registration?')">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?action=delete&id=<?php echo $reg['reg_id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this registration?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php 
                                    echo $event_filter ? "&event_id=$event_filter" : ''; 
                                ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
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