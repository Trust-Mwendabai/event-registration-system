-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Add user_id to registrations table
ALTER TABLE registrations ADD COLUMN user_id INT NULL AFTER reg_id;
ALTER TABLE registrations ADD FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL; 