<?php
/**
 * Global Helper Functions
 */

/**
 * Format number as currency
 */
function formatCurrency($amount, $currency = 'SZL') {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Convert percentage to letter grade
 */
function getLetterGrade($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 85) return 'A';
    if ($percentage >= 80) return 'B+';
    if ($percentage >= 75) return 'B';
    if ($percentage >= 70) return 'C+';
    if ($percentage >= 65) return 'C';
    if ($percentage >= 60) return 'D';
    if ($percentage >= 50) return 'D';
    return 'F';
}

/**
 * Get GPA points from grade
 */
function getGPAPoints($letter_grade) {
    $grades = [
        'A+' => 4.0,
        'A' => 3.7,
        'B+' => 3.3,
        'B' => 3.0,
        'C+' => 2.7,
        'C' => 2.3,
        'D' => 1.0,
        'F' => 0.0
    ];
    
    return $grades[$letter_grade] ?? 0.0;
}

/**
 * Calculate GPA
 */
function calculateGPA($grades) {
    if (empty($grades)) return 0.0;
    
    $total_points = 0;
    $total_credits = 0;
    
    foreach ($grades as $grade) {
        $points = getGPAPoints($grade['letter_grade']);
        $credits = $grade['credits'] ?? 3;
        $total_points += $points * $credits;
        $total_credits += $credits;
    }
    
    return $total_credits > 0 ? round($total_points / $total_credits, 2) : 0.0;
}

/**
 * Get academic status
 */
function getAcademicStatus($gpa) {
    if ($gpa >= 3.5) return 'Excellent';
    if ($gpa >= 3.0) return 'Very Good';
    if ($gpa >= 2.5) return 'Good';
    if ($gpa >= 2.0) return 'Satisfactory';
    return 'Below Average';
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message, $is_html = true) {
    $headers = "From: " . MAIL_FROM . "\r\n";
    if ($is_html) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    $headers .= "MIME-Version: 1.0\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Create notification
 */
function createNotification($user_id, $title, $message, $type = 'info', $category = 'system', $action_url = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, category, action_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $user_id,
            $title,
            $message,
            $type,
            $category,
            $action_url
        ]);
    } catch (Exception $e) {
        error_log('Notification error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get user by ID
 */
function getUserById($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get student by ID
 */
function getStudentById($student_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Get current user ID
 */
function getCurrentUserID() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/login');
    }
}

/**
 * Require role
 */
function requireRole($role) {
    if (getCurrentUserRole() !== $role) {
        http_response_code(403);
        die('Access Denied');
    }
}

/**
 * Get error message
 */
function getErrorMessage($code) {
    $errors = [
        'INVALID_CREDENTIALS' => 'Invalid username or password',
        'ACCOUNT_LOCKED' => 'Account is locked. Please try again later',
        'SESSION_EXPIRED' => 'Your session has expired. Please login again',
        'UNAUTHORIZED' => 'You do not have permission to access this',
        'INVALID_INPUT' => 'Invalid input provided',
        'DATABASE_ERROR' => 'Database error occurred'
    ];
    
    return $errors[$code] ?? 'An error occurred';
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowed_extensions, $max_size = null) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }
    
    if ($max_size && $file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    return ['success' => true, 'extension' => $ext];
}

?>
