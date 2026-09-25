# Gym Management System

A professional gym management system built with Core PHP, MySQL, Bootstrap 5, and PDO. This Phase 1 project focuses on setting up a clean foundation for a future complete gym management application.

## Project Purpose

This project is designed for a BCA final-year project and provides a strong base for a real-world gym management platform. It includes a proper database schema, configuration structure, reusable helper functions, and a landing page for the application foundation.

## Technology Stack

- Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
- Backend: Core PHP 8+
- Database: MySQL / MariaDB
- Database Access: PDO
- Server: XAMPP Apache
- Development Environment: VS Code
- Database Tool: phpMyAdmin
- Version Control: Git / GitHub

## Required Software

- XAMPP with Apache and MySQL enabled
- VS Code
- Web browser (Chrome, Edge, or Firefox)
- phpMyAdmin

## XAMPP Setup

1. Install XAMPP.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Ensure the project folder is placed in:
   `C:\xampp\htdocs\gym-management-system`
4. Open the app using:
   `http://localhost/gym-management-system/`

## Database Setup

1. Open phpMyAdmin in the browser at:
   `http://localhost/phpmyadmin`
2. Create a new database named `gym_management`.
3. Click on the Import tab.
4. Choose the file:
   `database/gym_management.sql`
5. Click Go to import.

## Local URL

- Application: http://localhost/gym-management-system/

## Project Structure

```text
gym-management-system/
├── index.php
├── config/
│   ├── database.php
│   └── config.php
├── includes/
│   └── functions.php
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── images/
├── uploads/
│   └── profiles/
├── database/
│   └── gym_management.sql
├── README.md
└── .gitignore
```

## Current Development Phase

Phase 1: Foundation

This phase includes the application base structure, database schema, configuration, helpers, and a professional landing page. Authentication, login, admin dashboard, trainers, members, payments, attendance, and future modules are intentionally not implemented yet.

## Future Phases

- Phase 2: Authentication and roles
- Phase 3: Admin dashboard and user management
- Phase 4: Members, trainers, and membership handling
- Phase 5: Billing, attendance, and workout management
- Phase 6: Reports and final polishing

## Security Notes

- PDO is used for MySQL connection handling.
- Password hashes are stored using bcrypt-compatible values in the SQL seed data.
- No authentication or login features are implemented in Phase 1.

## License

This project is intended for educational and academic use.
