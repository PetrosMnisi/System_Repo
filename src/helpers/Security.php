<?php
/**
 * Security Helper Functions
 */

class Security {
    
    /**
     * Hash password using bcrypt
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Sanitize user input
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([__CLASS__, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        // Minimum 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
    }
    
    /**
     * Validate student ID format (9 characters)
     */
    public static function validateStudentID($student_id) {
        return preg_match('/^[A-Z0-9]{9}$/', $student_id);
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP);
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($identifier, $limit = 5, $window = 300) {
        $cache_key = 'rate_limit_' . $identifier;
        $attempts = apcu_fetch($cache_key);
        
        if ($attempts === false) {
            apcu_store($cache_key, 1, $window);
            return true;
        }
        
        if ($attempts < $limit) {
            apcu_inc($cache_key);
            return true;
        }
        
        return false;
    }
    
    /**
     * Log activity
     */
    public static function logActivity($user_id, $action, $module, $record_id = null, $record_type = null, $old_values = null, $new_values = null, $description = null) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, module, record_id, record_type, old_values, new_values, ip_address, user_agent, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $action,
                $module,
                $record_id,
                $record_type,
                $old_values ? json_encode($old_values) : null,
                $new_values ? json_encode($new_values) : null,
                self::getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $description
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Audit log error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify user session
     */
    public static function verifySession($user_id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM sessions 
                WHERE user_id = ? AND expires_at > NOW()
                LIMIT 1
            ");
            
            $stmt->execute([$user_id]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if user has permission
     */
    public static function hasPermission($user_id, $permission) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) return false;
            
            // Define role-based permissions
            $permissions = [
                'admin' => ['*'],
                'lecturer' => ['view_grades', 'submit_grades', 'view_students'],
                'student' => ['view_results', 'view_profile'],
                'finance' => ['view_payments', 'verify_payments'],
                'admissions' => ['manage_registrations']
            ];
            
            $role = $user['role'];
            return in_array('*', $permissions[$role] ?? []) || in_array($permission, $permissions[$role] ?? []);
        } catch (Exception $e) {
            return false;
        }
    }
}

?>
