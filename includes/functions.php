<?php
session_start();

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (basic validation)
function isValidPhone($phone) {
    return preg_match('/^[0-9+\-\s()]{10,20}$/', $phone);
}

// Check if user is logged in
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

// Redirect with message
function redirectWith($url, $message = '', $type = 'info') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}

// Display flash message
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return "<div class='alert alert-$type'>$message</div>";
    }
    return '';
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Format date for display
function formatDate($date) {
    return date('F j, Y g:i A', strtotime($date));
}

// Check if event registration is open
function isEventOpen($eventDate) {
    return strtotime($eventDate) > time();
}

// Get remaining slots for an event
function getRemainingSlots($conn, $event_id) {
    $stmt = $conn->prepare("
        SELECT e.capacity, COUNT(r.reg_id) as registered 
        FROM events e 
        LEFT JOIN registrations r ON e.event_id = r.event_id AND r.status = 'confirmed'
        WHERE e.event_id = ?
        GROUP BY e.event_id
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($data['capacity'] === null) {
        return null; // No capacity limit
    }
    
    return max(0, $data['capacity'] - $data['registered']);
}

// Check if event is full
function isEventFull($conn, $event_id) {
    $remaining = getRemainingSlots($conn, $event_id);
    return $remaining !== null && $remaining <= 0;
}

// Add registration to waitlist
function addToWaitlist($conn, $event_id, $name, $email, $phone) {
    $stmt = $conn->prepare("
        INSERT INTO registrations (event_id, name, email, phone, status) 
        VALUES (?, ?, ?, ?, 'waitlisted')
    ");
    $stmt->bind_param("isss", $event_id, $name, $email, $phone);
    return $stmt->execute();
}

// Send notification
function sendNotification($conn, $event_id, $type, $message) {
    $stmt = $conn->prepare("
        INSERT INTO notifications (event_id, type, message) 
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $event_id, $type, $message);
    return $stmt->execute();
}

// Get event statistics
function getEventStats($conn, $event_id) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_registrations,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'waitlisted' THEN 1 ELSE 0 END) as waitlisted,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM registrations 
        WHERE event_id = ?
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Format phone number
function formatPhoneNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format based on length
    if (strlen($phone) === 10) {
        return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    }
    
    return $phone;
}

// Generate QR code URL for event
function generateEventQRCode($event_id) {
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://$_SERVER[HTTP_HOST]/register.php?event_id=$event_id";
    return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url);
}

// Check if registration is confirmed
function isRegistrationConfirmed($conn, $reg_id) {
    $stmt = $conn->prepare("SELECT status FROM registrations WHERE reg_id = ?");
    $stmt->bind_param("i", $reg_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $registration = $result->fetch_assoc();
    return $registration && $registration['status'] === 'confirmed';
}

// Move waitlisted registration to confirmed
function confirmWaitlistedRegistration($conn, $reg_id) {
    $stmt = $conn->prepare("UPDATE registrations SET status = 'confirmed' WHERE reg_id = ?");
    $stmt->bind_param("i", $reg_id);
    return $stmt->execute();
}

// Cancel registration
function cancelRegistration($conn, $reg_id) {
    $stmt = $conn->prepare("UPDATE registrations SET status = 'cancelled' WHERE reg_id = ?");
    $stmt->bind_param("i", $reg_id);
    return $stmt->execute();
} 