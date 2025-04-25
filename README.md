# Event Registration System

A web-based platform designed to help organizations manage event sign-ups digitally. This system eliminates manual paperwork, email confusion, and spreadsheet chaos by providing a centralized solution for event registration.

## Features

### For Attendees
- Browse upcoming events
- Register for events with a simple form
- Receive instant confirmation
- Mobile-friendly interface

### For Organizers
- Secure admin dashboard
- Create, update, and manage events
- View and export attendee lists
- Real-time registration statistics

## Technology Stack
- HTML/CSS: Frontend structure and styling
- JavaScript: Interactive form validation
- PHP: Backend logic
- MySQL: Database management

## Installation

1. Clone this repository to your web server directory
2. Import the database schema from `database/event_registration.sql`
3. Configure database connection in `config/database.php`
4. Access the application through your web browser

## Directory Structure
```
event-registration-system/
├── admin/              # Admin panel files
├── assets/            # CSS, JS, and images
├── config/            # Configuration files
├── database/          # Database schema
├── includes/          # PHP helper functions
├── public/            # Public-facing pages
└── vendor/            # Dependencies
```

## Setup Instructions
1. Create a MySQL database named 'event_registration'
2. Import the database schema
3. Update database credentials in config/database.php
4. Default admin credentials:
   - Username: admin
   - Password: admin123 (change this immediately after first login)

## Security Features
- Password hashing for admin accounts
- Input validation and sanitization
- Secure session management
- Protection against SQL injection

## License
MIT License 