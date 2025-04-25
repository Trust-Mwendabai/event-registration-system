-- Create the database
CREATE DATABASE IF NOT EXISTS event_registration;
USE event_registration;

-- Create events table
CREATE TABLE IF NOT EXISTS events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    date DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT,
    capacity INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create registrations table
CREATE TABLE IF NOT EXISTS registrations (
    reg_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('confirmed', 'waitlisted', 'cancelled') DEFAULT 'confirmed',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    type ENUM('reminder', 'update', 'cancellation') NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO admins (username, password, email) VALUES 
('admin', '$2y$10$8i5Oo5bv5r/qZ5KNxN5vZuBgJ5OJoFxK.B3E7.l4MJQBj3UXpxmAy', 'admin@example.com');

-- Add indexes for better performance
CREATE INDEX idx_event_date ON events(date);
CREATE INDEX idx_registration_event ON registrations(event_id);
CREATE INDEX idx_admin_username ON admins(username);
CREATE INDEX idx_registration_status ON registrations(status);
CREATE INDEX idx_notification_event ON notifications(event_id); 