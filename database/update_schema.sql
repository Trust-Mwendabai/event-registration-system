-- Add capacity column to events table if it doesn't exist
ALTER TABLE events ADD COLUMN IF NOT EXISTS capacity INT DEFAULT NULL;

-- Add status column to registrations table if it doesn't exist
ALTER TABLE registrations ADD COLUMN IF NOT EXISTS status ENUM('confirmed', 'waitlisted', 'cancelled') DEFAULT 'confirmed';

-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    type ENUM('reminder', 'update', 'cancellation') NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- Add new indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_registration_status ON registrations(status);
CREATE INDEX IF NOT EXISTS idx_notification_event ON notifications(event_id);

-- Add category column to events table
ALTER TABLE events ADD COLUMN category VARCHAR(50) DEFAULT 'General' AFTER description;

-- Update existing events to have a category
UPDATE events SET category = 'General' WHERE category IS NULL; 