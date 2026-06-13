-- Add Applications Table
CREATE TABLE IF NOT EXISTS applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('M', 'F', 'Other'),
    nationality VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    program_id INT,
    form_5_results VARCHAR(255),
    application_fee_proof VARCHAR(255),
    payment_proof VARCHAR(255),
    status ENUM('submitted', 'under_review', 'approved', 'rejected', 'pending_payment') DEFAULT 'submitted',
    reviewed_by INT,
    reviewed_date TIMESTAMP NULL,
    reviewer_comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_application_number (application_number),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Add Module Assignments Table
CREATE TABLE IF NOT EXISTS module_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    academic_year INT NOT NULL,
    semester INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (module_id, lecturer_id, academic_year, semester),
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_academic (academic_year, semester)
);

-- Add Result Blocks Table (Finance Control)
CREATE TABLE IF NOT EXISTS result_blocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL UNIQUE,
    status ENUM('blocked', 'unblocked') DEFAULT 'blocked',
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Add outstanding_balance column to students if not exists
ALTER TABLE students ADD COLUMN IF NOT EXISTS outstanding_balance DECIMAL(10,2) DEFAULT 0.00;

-- Create trigger to auto-block results for unpaid fees
CREATE TRIGGER IF NOT EXISTS auto_block_results_on_payment_failure
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    IF NEW.status = 'failed' THEN
        INSERT INTO result_blocks (student_id, status, reason) 
        VALUES (NEW.student_id, 'blocked', 'Payment failed or incomplete')
        ON DUPLICATE KEY UPDATE status = 'blocked';
    END IF;
END;
