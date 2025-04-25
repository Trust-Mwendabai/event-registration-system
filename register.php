<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$conn = getDBConnection();
$errors = [];
$success = false;

// Get event details
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    redirectWith('index.php', 'Invalid event selected.', 'danger');
}

$event_id = (int)$_GET['event_id'];
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ? AND date >= CURRENT_TIMESTAMP");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    redirectWith('index.php', 'Event not found or registration closed.', 'danger');
}

// Check if event is full
$is_full = isEventFull($conn, $event_id);
$remaining_slots = getRemainingSlots($conn, $event_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    }

    // Validate inputs
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    if (empty($name)) {
        $errors[] = 'Name is required.';
    }

    if (empty($email) || !isValidEmail($email)) {
        $errors[] = 'Valid email is required.';
    }

    if (empty($phone) || !isValidPhone($phone)) {
        $errors[] = 'Valid phone number is required.';
    }

    // Check for duplicate registration
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT reg_id FROM registrations WHERE event_id = ? AND (email = ? OR phone = ?)");
        $stmt->bind_param("iss", $event_id, $email, $phone);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'You have already registered for this event.';
        }
    }

    // Process registration
    if (empty($errors)) {
        if ($is_full) {
            // Add to waitlist
            if (addToWaitlist($conn, $event_id, $name, $email, $phone)) {
                $success = true;
                $status = 'waitlisted';
                sendNotification($conn, $event_id, 'update', 
                    "New waitlist registration for {$event['title']} by $name");
            } else {
                $errors[] = 'Failed to add to waitlist. Please try again.';
            }
        } else {
            // Regular registration
            $stmt = $conn->prepare("INSERT INTO registrations (event_id, name, email, phone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $event_id, $name, $email, $phone);
            
            if ($stmt->execute()) {
                $success = true;
                $status = 'confirmed';
                sendNotification($conn, $event_id, 'update', 
                    "New registration for {$event['title']} by $name");
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for <?php echo htmlspecialchars($event['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Event Registration System</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4>Registration <?php echo $status === 'waitlisted' ? 'Added to Waitlist' : 'Successful'; ?>!</h4>
                        <p>You have <?php echo $status === 'waitlisted' ? 'been added to the waitlist for' : 'successfully registered for'; ?> 
                           "<?php echo htmlspecialchars($event['title']); ?>".</p>
                        <p>Event details:</p>
                        <ul>
                            <li>Date: <?php echo formatDate($event['date']); ?></li>
                            <li>Location: <?php echo htmlspecialchars($event['location']); ?></li>
                        </ul>
                        <?php if ($status === 'waitlisted'): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> You are on the waitlist. We will notify you if a spot becomes available.
                            </div>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-primary">Back to Events</a>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="mb-0">Register for Event</h2>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p>
                                <strong>Date:</strong> <?php echo formatDate($event['date']); ?><br>
                                <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                            </p>
                            <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>

                            <?php if ($event['capacity'] !== null): ?>
                                <div class="alert <?php echo $is_full ? 'alert-warning' : 'alert-info'; ?>">
                                    <?php if ($is_full): ?>
                                        <i class="bi bi-exclamation-triangle"></i> This event is currently full.
                                        You can still register to be added to the waitlist.
                                    <?php else: ?>
                                        <i class="bi bi-info-circle"></i> 
                                        <?php echo $remaining_slots; ?> spots remaining
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                           required>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $is_full ? 'Join Waitlist' : 'Register'; ?>
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
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