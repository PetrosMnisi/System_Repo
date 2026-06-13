<?php
/**
 * Result Visibility Model
 * Handles finance control over result visibility
 */

class ResultVisibility {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Block student results
     */
    public function blockStudentResults($student_id, $reason = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO result_blocks (student_id, status, reason, created_at, created_by)
                VALUES (?, 'blocked', ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE 
                    status = 'blocked',
                    reason = ?,
                    updated_at = NOW()
            ");
            
            $result = $stmt->execute([
                $student_id,
                $reason,
                getCurrentUserID(),
                $reason
            ]);
            
            if ($result) {
                createNotification(
                    $this->getStudentUserId($student_id),
                    'Results Blocked',
                    'Your results have been blocked due to outstanding fees. Please contact the finance office.',
                    'warning',
                    'payment'
                );
                
                Security::logActivity(
                    getCurrentUserID(),
                    'block_results',
                    'results',
                    $student_id,
                    'student',
                    null,
                    ['reason' => $reason]
                );
            }
            
            return ['success' => $result, 'message' => 'Results blocked'];
        } catch (Exception $e) {
            error_log('Block results error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error blocking results'];
        }
    }
    
    /**
     * Unblock student results
     */
    public function unblockStudentResults($student_id, $reason = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE result_blocks 
                SET status = 'unblocked', reason = ?, updated_at = NOW()
                WHERE student_id = ?
            ");
            
            $result = $stmt->execute([$reason, $student_id]);
            
            if ($result) {
                createNotification(
                    $this->getStudentUserId($student_id),
                    'Results Released',
                    'Your results are now available in your portal.',
                    'success',
                    'grade'
                );
                
                Security::logActivity(
                    getCurrentUserID(),
                    'unblock_results',
                    'results',
                    $student_id,
                    'student',
                    null,
                    ['reason' => $reason]
                );
            }
            
            return ['success' => $result, 'message' => 'Results unblocked'];
        } catch (Exception $e) {
            error_log('Unblock results error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error unblocking results'];
        }
    }
    
    /**
     * Check if student results are blocked
     */
    public function isResultsBlocked($student_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT status FROM result_blocks 
                WHERE student_id = ? AND status = 'blocked'
                LIMIT 1
            ");
            
            $stmt->execute([$student_id]);
            $result = $stmt->fetch();
            
            return $result ? true : false;
        } catch (Exception $e) {
            error_log('Check blocked status error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get block reason
     */
    public function getBlockReason($student_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT reason FROM result_blocks 
                WHERE student_id = ? AND status = 'blocked'
                LIMIT 1
            ");
            
            $stmt->execute([$student_id]);
            $result = $stmt->fetch();
            
            return $result ? $result['reason'] : null;
        } catch (Exception $e) {
            error_log('Get block reason error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all blocked students
     */
    public function getBlockedStudents($limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rb.*, s.student_id, u.first_name, u.last_name, u.email
                FROM result_blocks rb
                JOIN students s ON rb.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE rb.status = 'blocked'
                ORDER BY rb.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get blocked students error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get student user ID
     */
    protected function getStudentUserId($student_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $result = $stmt->fetch();
            return $result ? $result['user_id'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Auto-block students with outstanding fees
     */
    public function autoBlockOutstandingFees($total_tuition = 25000) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO result_blocks (student_id, status, reason, created_at, created_by)
                SELECT s.id, 'blocked', 'Outstanding fees', NOW(), NULL
                FROM students s
                WHERE s.status = 'active'
                AND s.id NOT IN (
                    SELECT DISTINCT student_id FROM result_blocks WHERE status = 'blocked'
                )
                AND s.id NOT IN (
                    SELECT student_id FROM payments 
                    WHERE status = 'completed'
                    GROUP BY student_id
                    HAVING SUM(amount) >= ?
                )
                ON DUPLICATE KEY UPDATE status = 'blocked'
            ");
            
            $result = $stmt->execute([$total_tuition]);
            return ['success' => $result, 'message' => 'Auto-block completed'];
        } catch (Exception $e) {
            error_log('Auto-block error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error in auto-blocking'];
        }
    }
}

?>