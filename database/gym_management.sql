CREATE DATABASE IF NOT EXISTS gym_management;
USE gym_management;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'trainer', 'member') NOT NULL,
    status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    gender ENUM('male', 'female', 'other') DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    address TEXT DEFAULT NULL,
    emergency_contact_name VARCHAR(150) DEFAULT NULL,
    emergency_contact_phone VARCHAR(20) DEFAULT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    join_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'paused') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_members_user_id (user_id),
    KEY idx_members_status (status),
    KEY idx_members_join_date (join_date),
    CONSTRAINT fk_members_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trainers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    specialization VARCHAR(255) DEFAULT NULL,
    experience_years INT UNSIGNED DEFAULT 0,
    certifications TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_trainers_user_id (user_id),
    KEY idx_trainers_status (status),
    CONSTRAINT fk_trainers_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE membership_plans (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    duration_days INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_membership_plans_name (name),
    KEY idx_membership_plans_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE memberships (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled', 'pending') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_memberships_member_id (member_id),
    KEY idx_memberships_plan_id (plan_id),
    KEY idx_memberships_status (status),
    KEY idx_memberships_dates (start_date, end_date),
    CONSTRAINT fk_memberships_member
        FOREIGN KEY (member_id) REFERENCES members(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_memberships_plan
        FOREIGN KEY (plan_id) REFERENCES membership_plans(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    membership_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'upi', 'wallet') NOT NULL,
    transaction_reference VARCHAR(150) DEFAULT NULL,
    payment_date DATETIME NOT NULL,
    status ENUM('paid', 'pending', 'failed', 'refunded') NOT NULL DEFAULT 'paid',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payments_reference (transaction_reference),
    KEY idx_payments_membership_id (membership_id),
    KEY idx_payments_status (status),
    KEY idx_payments_date (payment_date),
    CONSTRAINT fk_payments_membership
        FOREIGN KEY (membership_id) REFERENCES memberships(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id INT UNSIGNED NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attendance_member_id (member_id),
    KEY idx_attendance_check_in (check_in),
    CONSTRAINT fk_attendance_member
        FOREIGN KEY (member_id) REFERENCES members(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workout_plans (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    trainer_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    goal VARCHAR(255) DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_workout_plans_trainer_id (trainer_id),
    KEY idx_workout_plans_member_id (member_id),
    KEY idx_workout_plans_status (status),
    CONSTRAINT fk_workout_plans_trainer
        FOREIGN KEY (trainer_id) REFERENCES trainers(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_workout_plans_member
        FOREIGN KEY (member_id) REFERENCES members(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE progress (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id INT UNSIGNED NOT NULL,
    workout_plan_id INT UNSIGNED DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT NULL,
    body_fat_percentage DECIMAL(5,2) DEFAULT NULL,
    measurements JSON DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    recorded_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_progress_member_id (member_id),
    KEY idx_progress_workout_plan_id (workout_plan_id),
    KEY idx_progress_recorded_at (recorded_at),
    CONSTRAINT fk_progress_member
        FOREIGN KEY (member_id) REFERENCES members(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_progress_workout_plan
        FOREIGN KEY (workout_plan_id) REFERENCES workout_plans(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES
('System Administrator', 'admin@gymmanagement.local', '+91 90000 00001', '$2y$10$KA552PNCtNmjOZfU34B5ue4TMotVOsMlBxyZrW54tHHyP8RAYDzii', 'admin', 'active'),
('Head Trainer', 'trainer@gymmanagement.local', '+91 90000 00002', '$2y$10$Nk6lpuOz/sSOi5nAEUdJ5e/dcHglGGlOSREkgl3w.h9keUo0fTkwm', 'trainer', 'active'),
('Sample Member', 'member@gymmanagement.local', '+91 90000 00003', '$2y$10$1lkCB3a8N/vkFL7TmsIFUutiw5w.KT3kIf9A6yLQnCdcks1Nq1Tb2', 'member', 'active');

INSERT INTO members (user_id, gender, date_of_birth, address, emergency_contact_name, emergency_contact_phone, profile_photo, join_date, status) VALUES
(3, 'male', '1998-05-14', 'Bangalore, Karnataka', 'Jane Doe', '+91 90000 00009', NULL, '2025-01-15', 'active');

INSERT INTO trainers (user_id, specialization, experience_years, certifications, bio, status) VALUES
(2, 'Strength Training and Conditioning', 6, 'NASM-CPT, Kettlebell Instructor', 'Experienced coach with six years in strength and performance-based training.', 'active');

INSERT INTO membership_plans (name, description, duration_days, price, is_active) VALUES
('Basic Gym', 'Access to gym floor and standard equipment.', 30, 1499.00, 1),
('Premium Gym', 'Access to gym floor, classes, and trainer support.', 90, 3999.00, 1),
('Elite Fitness', 'Full access with personal coaching sessions.', 180, 6999.00, 1);

INSERT INTO memberships (member_id, plan_id, start_date, end_date, status) VALUES
(1, 2, '2025-09-01', '2025-11-29', 'active');

INSERT INTO payments (membership_id, amount, payment_method, transaction_reference, payment_date, status, notes) VALUES
(1, 3999.00, 'upi', 'UPI-20250901-1001', '2025-09-01 10:15:00', 'paid', 'Premium Gym plan initial payment');

INSERT INTO attendance (member_id, check_in, check_out, notes) VALUES
(1, '2025-09-24 06:15:00', '2025-09-24 07:30:00', 'Morning cardio session');

INSERT INTO workout_plans (trainer_id, member_id, title, description, goal, start_date, end_date, status) VALUES
(1, 1, '12-Week Strength Builder', 'Progressive strength routine with compound lifts and mobility work.', 'Build strength and muscle mass', '2025-09-01', '2025-11-20', 'active');

INSERT INTO progress (member_id, workout_plan_id, weight, body_fat_percentage, measurements, notes, recorded_at) VALUES
(1, 1, 72.50, 18.80, '{"chest": 96, "waist": 82, "arm": 32}', 'Strong consistency in weekly training and recovery.', '2025-09-24 08:00:00');
