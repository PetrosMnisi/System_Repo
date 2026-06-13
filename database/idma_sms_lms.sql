-- IDMA SMS/LMS Database Schema
-- Version: 1.0.0
-- Created: 2026-06-13

CREATE DATABASE IF NOT EXISTS idma_sms_lms;
USE idma_sms_lms;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE,
    email VARCHAR(255) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'lecturer', 'student', 'finance', 'admissions') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_email (email)
);

-- Students Table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    date_of_birth DATE,
    gender ENUM('M', 'F', 'Other'),
    nationality VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    admission_date DATE NOT NULL,
    program_id INT,
    academic_year INT,
    semester INT,
    status ENUM('active', 'suspended', 'graduated', 'withdrawn') DEFAULT 'active',
    gpa DECIMAL(3,2) DEFAULT 0.00,
    total_credits INT DEFAULT 0,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_program (program_id),
    INDEX idx_status (status)
);

-- Lecturers Table
CREATE TABLE lecturers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(100),
    qualification VARCHAR(255),
    office_location VARCHAR(100),
    office_hours TEXT,
    profile_picture VARCHAR(255),
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_department (department),
    INDEX idx_status (status)
);

-- Programs Table
CREATE TABLE programs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    duration_years INT DEFAULT 3,
    total_credits INT DEFAULT 120,
    department VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code)
);

-- Courses Table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    lecturer_id INT,
    program_id INT,
    credits INT DEFAULT 3,
    academic_year INT,
    semester INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE SET NULL,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    INDEX idx_code (code),
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_program (program_id)
);

-- Modules Table
CREATE TABLE modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    credits INT DEFAULT 3,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course (course_id)
);

-- Grade Weightings Table
CREATE TABLE grade_weightings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    individual_assignment_weight DECIMAL(3,2) DEFAULT 0.20,
    group_assignment_weight DECIMAL(3,2) DEFAULT 0.20,
    test_weight DECIMAL(3,2) DEFAULT 0.20,
    exam_weight DECIMAL(3,2) DEFAULT 0.40,
    academic_year INT,
    semester INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_lecturer (lecturer_id)
);

-- Enrollments Table
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'completed', 'withdrawn', 'failed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status)
);

-- Grades Table
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    individual_assignment_score DECIMAL(5,2),
    group_assignment_score DECIMAL(5,2),
    test_score DECIMAL(5,2),
    exam_score DECIMAL(5,2),
    calculated_grade DECIMAL(5,2),
    letter_grade VARCHAR(2),
    gpa_points DECIMAL(3,2),
    status ENUM('pending', 'submitted', 'approved', 'rejected') DEFAULT 'pending',
    submitted_date TIMESTAMP NULL,
    approved_date TIMESTAMP NULL,
    approved_by INT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    UNIQUE KEY unique_grade (enrollment_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status)
);

-- Payments Table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_type ENUM('tuition', 'accommodation', 'library', 'other') DEFAULT 'tuition',
    payment_method ENUM('bank_transfer', 'cash', 'cheque', 'card') DEFAULT 'bank_transfer',
    transaction_reference VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    due_date DATE,
    payment_date DATE,
    deposit_percentage DECIMAL(3,2) DEFAULT 40.00,
    outstanding_balance DECIMAL(10,2),
    receipt_number VARCHAR(50),
    verified_by INT,
    verification_date TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
);

-- Notifications Table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    category ENUM('grade', 'payment', 'enrollment', 'system', 'other') DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);

-- Audit Logs Table
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    module VARCHAR(100),
    record_id INT,
    record_type VARCHAR(50),
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(50),
    user_agent TEXT,
    status ENUM('success', 'failed') DEFAULT 'success',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_module (module)
);

-- Sessions Table (for session management)
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- Academic Calendar Table
CREATE TABLE academic_calendar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    academic_year INT NOT NULL,
    semester INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    registration_start DATE,
    registration_end DATE,
    exam_start DATE,
    exam_end DATE,
    grade_submission_deadline DATE,
    status ENUM('planning', 'active', 'completed') DEFAULT 'planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_academic (academic_year, semester)
);

-- Create default admin user
INSERT INTO users (username, email, password_hash, role, first_name, last_name, status) 
VALUES ('admin', 'admin@idma.sz', '$2y$10$aHLBr4LRp6xQa.bHJLzjsu0KvXFLvQ7JLvqVKQQvN4w/4j.7gHZhC', 'admin', 'System', 'Administrator', 'active');

-- Create indexes for better performance
CREATE INDEX idx_users_role_status ON users(role, status);
CREATE INDEX idx_students_user ON students(user_id);
CREATE INDEX idx_grades_student_course ON grades(student_id, course_id);
CREATE INDEX idx_payments_student_status ON payments(student_id, status);
CREATE INDEX idx_enrollments_student_course ON enrollments(student_id, course_id);

SET FOREIGN_KEY_CHECKS = 1;
