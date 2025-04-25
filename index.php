<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get database connection
$conn = getDBConnection();

// Fetch upcoming events
$sql = "SELECT * FROM events WHERE date >= CURRENT_TIMESTAMP ORDER BY date ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Event Registration System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php echo displayMessage(); ?>
        
        <h1 class="mb-4">Upcoming Events</h1>
        
        <div class="row">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($event = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <p class="card-text">
                                    <strong>Date:</strong> <?php echo formatDate($event['date']); ?><br>
                                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                                </p>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                <?php if (isEventOpen($event['date'])): ?>
                                    <a href="register.php?event_id=<?php echo $event['event_id']; ?>" 
                                       class="btn btn-primary">Register Now</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Registration Closed</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No upcoming events at this time. Please check back later!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Event Registration System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php closeDBConnection($conn); ?> 