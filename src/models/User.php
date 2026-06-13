<?php
/**
 * User Model
 */

class User {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($username, $password) {
        try {
            // Support both username and email login
            $stmt = $this->pdo->prepare("
                SELECT * FROM users 
                WHERE (username = ? OR email = ?) AND status = 'active'
            ");
            
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check if account is locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return ['success' => false, 'message' => 'Account is temporarily locked'];
            }
            
            // Verify password
            if (!Security::verifyPassword($password, $user['password_hash'])) {
                $this->incrementLoginAttempts($user['id']);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Reset login attempts
            $this->resetLoginAttempts($user['id']);
            
            // Update last login
            $update_stmt = $this->pdo->prepare("
                UPDATE users SET last_login = NOW() WHERE id = ?
            ");
            $update_stmt->execute([$user['id']]);
            
            return ['success' => true, 'user' => $user];
        } catch (Exception $e) {
            error_log('Authentication error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication failed'];
        }
    }
    
    /**
     * Register student
     */
    public function registerStudent($data) {
        try {
            // Validate required fields
            $required = ['student_id', 'email', 'password', 'first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => ucfirst($field) . ' is required'];
                }
            }
            
            // Validate email
            if (!Security::validateEmail($data['email'])) {
                return ['success' => false, 'message' => 'Invalid email address'];
            }
            
            // Validate student ID
            if (!Security::validateStudentID($data['student_id'])) {
                return ['success' => false, 'message' => 'Invalid student ID format'];
            }
            
            // Check if student ID exists
            $check_stmt = $this->pdo->prepare("SELECT id FROM students WHERE student_id = ?");
            $check_stmt->execute([$data['student_id']]);
            if (!$check_stmt->fetch()) {
                return ['success' => false, 'message' => 'Student ID not found. Contact admissions'];
            }
            
            // Check if email exists
            $email_stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $email_stmt->execute([$data['email']]);
            if ($email_stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Validate payment deposit
            $payment_stmt = $this->pdo->prepare("
                SELECT SUM(amount) as paid FROM payments 
                WHERE student_id = (SELECT id FROM students WHERE student_id = ?) 
                AND status = 'completed'
            ");
            $payment_stmt->execute([$data['student_id']]);
            $payment = $payment_stmt->fetch();
            
            $paid_amount = $payment['paid'] ?? 0;
            if ($paid_amount < (25000 * 0.40)) { // Example: 40% of 25000 tuition
                return ['success' => false, 'message' => 'Insufficient deposit. Please complete 40% payment'];
            }
            
            // Create user account
            $password_hash = Security::hashPassword($data['password']);
            
            $user_stmt = $this->pdo->prepare("
                INSERT INTO users (email, password_hash, role, first_name, last_name, status)
                VALUES (?, ?, 'student', ?, ?, 'active')
            ");
            
            $user_stmt->execute([
                $data['email'],
                $password_hash,
                $data['first_name'],
                $data['last_name']
            ]);
            
            $user_id = $this->pdo->lastInsertId();
            
            // Link to student record
            $student_update = $this->pdo->prepare("UPDATE students SET user_id = ? WHERE student_id = ?");
            $student_update->execute([$user_id, $data['student_id']]);
            
            // Log activity
            Security::logActivity($user_id, 'student_registration', 'student', $user_id, 'user', null, 
                ['email' => $data['email'], 'role' => 'student']);
            
            return ['success' => true, 'message' => 'Registration successful. Please login'];
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Increment login attempts
     */
    protected function incrementLoginAttempts($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT login_attempts FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            $attempts = ($user['login_attempts'] ?? 0) + 1;
            
            $update_stmt = $this->pdo->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
            $update_stmt->execute([$attempts, $user_id]);
            
            // Lock account after 5 failed attempts
            if ($attempts >= 5) {
                $lock_until = date('Y-m-d H:i:s', time() + 900); // 15 minutes
                $lock_stmt = $this->pdo->prepare("UPDATE users SET locked_until = ? WHERE id = ?");
                $lock_stmt->execute([$lock_until, $user_id]);
            }
        } catch (Exception $e) {
            error_log('Login attempt error: ' . $e->getMessage());
        }
    }
    
    /**
     * Reset login attempts
     */
    protected function resetLoginAttempts($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?
            ");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log('Reset attempts error: ' . $e->getMessage());
        }
    }
}

?>
