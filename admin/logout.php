<?php
require_once '../includes/functions.php';

// Clear all session data
session_start();
session_destroy();

// Redirect to login page
redirectWith('login.php', 'You have been logged out successfully.', 'info'); 