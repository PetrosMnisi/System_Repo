<?php
/**
 * Payment Model
 */

class Payment {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Record payment
     */
    public function recordPayment($student_id, $amount, $payment_method, $transaction_ref, $payment_type = 'tuition') {
        try {
            // Validate amount
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Invalid amount'];
            }
            
            // Create payment record
            $stmt = $this->pdo->prepare("
                INSERT INTO payments 
                (student_id, amount, payment_method, transaction_reference, payment_type, status, deposit_percentage)
                VALUES (?, ?, ?, ?, ?, 'pending', ?)
            ");
            
            $stmt->execute([
                $student_id,
                $amount,
                $payment_method,
                $transaction_ref,
                $payment_type,
                DEPOSIT_PERCENTAGE
            ]);
            
            $payment_id = $this->pdo->lastInsertId();
            
            Security::logActivity(
                getCurrentUserID(),
                'record_payment',
                'payments',
                $payment_id,
                'payment',
                null,
                ['student_id' => $student_id, 'amount' => $amount]
            );
            
            return ['success' => true, 'message' => 'Payment recorded', 'payment_id' => $payment_id];
        } catch (Exception $e) {
            error_log('Payment recording error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to record payment'];
        }
    }
    
    /**
     * Verify payment
     */
    public function verifyPayment($payment_id, $verified_by, $status = 'completed') {
        try {
            // Get payment details
            $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE id = ?");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch();
            
            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }
            
            // Validate status
            if (!in_array($status, ['completed', 'failed', 'cancelled'])) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            // Update payment status
            $update_stmt = $this->pdo->prepare("
                UPDATE payments SET 
                    status = ?,
                    verification_date = NOW(),
                    verified_by = ?
                WHERE id = ?
            ");
            
            $update_stmt->execute([$status, $verified_by, $payment_id]);
            
            if ($status === 'completed') {
                // Get student and create notification
                $student_stmt = $this->pdo->prepare("
                    SELECT s.user_id FROM students s WHERE id = ?
                ");
                $student_stmt->execute([$payment['student_id']]);
                $student = $student_stmt->fetch();
                
                if ($student) {
                    createNotification(
                        $student['user_id'],
                        'Payment Confirmed',
                        'Your payment of ' . formatCurrency($payment['amount']) . ' has been verified.',
                        'success',
                        'payment'
                    );
                }
            }
            
            Security::logActivity(
                $verified_by,
                'verify_payment',
                'payments',
                $payment_id,
                'payment',
                ['status' => $payment['status']],
                ['status' => $status]
            );
            
            return ['success' => true, 'message' => 'Payment verified'];
        } catch (Exception $e) {
            error_log('Payment verification error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify payment'];
        }
    }
    
    /**
     * Get student payments
     */
    public function getStudentPayments($student_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM payments 
                WHERE student_id = ? 
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$student_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get payments error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate outstanding balance
     */
    public function calculateOutstandingBalance($student_id, $total_tuition = 25000) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as paid 
                FROM payments 
                WHERE student_id = ? AND status = 'completed'
            ");
            
            $stmt->execute([$student_id]);
            $result = $stmt->fetch();
            $paid = $result['paid'] ?? 0;
            
            $outstanding = max(0, $total_tuition - $paid);
            
            return [
                'total_tuition' => $total_tuition,
                'paid' => $paid,
                'outstanding' => $outstanding,
                'deposit_required' => $total_tuition * (DEPOSIT_PERCENTAGE / 100),
                'deposit_paid' => $paid,
                'deposit_remaining' => max(0, ($total_tuition * (DEPOSIT_PERCENTAGE / 100)) - $paid)
            ];
        } catch (Exception $e) {
            error_log('Balance calculation error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get pending payments
     */
    public function getPendingPayments($limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, 
                       s.student_id, 
                       u.first_name, 
                       u.last_name, 
                       u.email
                FROM payments p
                JOIN students s ON p.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE p.status = 'pending'
                ORDER BY p.created_at ASC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get pending payments error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate payment report
     */
    public function generatePaymentReport($start_date = null, $end_date = null) {
        try {
            $query = "
                SELECT p.*, s.student_id, u.first_name, u.last_name
                FROM payments p
                JOIN students s ON p.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE 1 = 1
            ";
            
            $params = [];
            
            if ($start_date) {
                $query .= " AND DATE(p.payment_date) >= ?";
                $params[] = $start_date;
            }
            
            if ($end_date) {
                $query .= " AND DATE(p.payment_date) <= ?";
                $params[] = $end_date;
            }
            
            $query .= " ORDER BY p.payment_date DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Report generation error: ' . $e->getMessage());
            return [];
        }
    }
}

?>