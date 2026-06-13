<?php
/**
 * Student Application Model
 */

class Application {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Submit online application
     */
    public function submitApplication($data) {
        try {
            // Validate required fields
            $required = ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'nationality', 'program_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
                }
            }
            
            // Validate email
            if (!Security::validateEmail($data['email'])) {
                return ['success' => false, 'message' => 'Invalid email address'];
            }
            
            // Check if email already exists
            $check_stmt = $this->pdo->prepare("SELECT id FROM applications WHERE email = ?");
            $check_stmt->execute([$data['email']]);
            if ($check_stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already used for an application'];
            }
            
            // Validate file uploads
            $form5_path = null;
            $payment_proof_path = null;
            $app_fee_proof_path = null;
            
            if (isset($_FILES['form_5_results']) && $_FILES['form_5_results']['size'] > 0) {
                $validation = validateFileUpload($_FILES['form_5_results'], ['pdf', 'jpg', 'jpeg', 'png'], MAX_FILE_SIZE);
                if (!$validation['success']) {
                    return ['success' => false, 'message' => 'Form 5 Results: ' . $validation['message']];
                }
                $form5_path = $this->saveUploadedFile($_FILES['form_5_results'], 'applications/form5');
                if (!$form5_path) {
                    return ['success' => false, 'message' => 'Failed to upload Form 5 Results'];
                }
            }
            
            if (isset($_FILES['application_fee_proof']) && $_FILES['application_fee_proof']['size'] > 0) {
                $validation = validateFileUpload($_FILES['application_fee_proof'], ['pdf', 'jpg', 'jpeg', 'png'], MAX_FILE_SIZE);
                if (!$validation['success']) {
                    return ['success' => false, 'message' => 'Application Fee Proof: ' . $validation['message']];
                }
                $app_fee_proof_path = $this->saveUploadedFile($_FILES['application_fee_proof'], 'applications/fees');
                if (!$app_fee_proof_path) {
                    return ['success' => false, 'message' => 'Failed to upload Application Fee Proof'];
                }
            }
            
            if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['size'] > 0) {
                $validation = validateFileUpload($_FILES['payment_proof'], ['pdf', 'jpg', 'jpeg', 'png'], MAX_FILE_SIZE);
                if (!$validation['success']) {
                    return ['success' => false, 'message' => 'Payment Proof: ' . $validation['message']];
                }
                $payment_proof_path = $this->saveUploadedFile($_FILES['payment_proof'], 'applications/payments');
                if (!$payment_proof_path) {
                    return ['success' => false, 'message' => 'Failed to upload Payment Proof'];
                }
            }
            
            // Create application record
            $app_stmt = $this->pdo->prepare("
                INSERT INTO applications (
                    first_name, last_name, email, phone, date_of_birth, gender,
                    nationality, address, city, postal_code, country,
                    program_id, form_5_results, application_fee_proof, payment_proof,
                    status, application_number
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', ?)
            ");
            
            $application_number = $this->generateApplicationNumber();
            
            $app_stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'],
                $data['date_of_birth'],
                $data['gender'] ?? null,
                $data['nationality'],
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['postal_code'] ?? null,
                $data['country'] ?? null,
                $data['program_id'],
                $form5_path,
                $app_fee_proof_path,
                $payment_proof_path,
                $application_number
            ]);
            
            $application_id = $this->pdo->lastInsertId();
            
            // Send confirmation email
            $this->sendConfirmationEmail($data['email'], $data['first_name'], $application_number);
            
            // Log activity
            Security::logActivity(
                null,
                'submit_application',
                'applications',
                $application_id,
                'application',
                null,
                ['email' => $data['email'], 'program_id' => $data['program_id']]
            );
            
            return [
                'success' => true,
                'message' => 'Application submitted successfully',
                'application_id' => $application_id,
                'application_number' => $application_number
            ];
        } catch (Exception $e) {
            error_log('Application submission error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit application'];
        }
    }
    
    /**
     * Save uploaded file
     */
    protected function saveUploadedFile($file, $subfolder) {
        try {
            $upload_dir = UPLOAD_DIR . $subfolder . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = uniqid() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                return $subfolder . '/' . $filename;
            }
            
            return null;
        } catch (Exception $e) {
            error_log('File upload error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate unique application number
     */
    protected function generateApplicationNumber() {
        return 'APP-' . date('Y') . '-' . strtoupper(uniqid());
    }
    
    /**
     * Send confirmation email
     */
    protected function sendConfirmationEmail($email, $first_name, $application_number) {
        $subject = 'IDMA Application Received - ' . $application_number;
        
        $message = "<html><body>";
        $message .= "<h2>Application Received</h2>";
        $message .= "<p>Dear " . htmlspecialchars($first_name) . ",</p>";
        $message .= "<p>Thank you for submitting your application to IDMA.</p>";
        $message .= "<p><strong>Application Number: </strong>" . htmlspecialchars($application_number) . "</p>";
        $message .= "<p>We have received your application and will review it shortly. You will be notified via email once a decision has been made.</p>";
        $message .= "<p>Best regards,<br>IDMA Admissions Office</p>";
        $message .= "</body></html>";
        
        return sendEmail($email, $subject, $message, true);
    }
    
    /**
     * Get application by ID
     */
    public function getApplicationById($application_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$application_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get application by email
     */
    public function getApplicationByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM applications WHERE email = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Update application status
     */
    public function updateApplicationStatus($application_id, $status, $admin_id, $comments = null) {
        try {
            $valid_statuses = ['submitted', 'under_review', 'approved', 'rejected', 'pending_payment'];
            if (!in_array($status, $valid_statuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE applications SET 
                    status = ?,
                    reviewed_by = ?,
                    reviewed_date = NOW(),
                    reviewer_comments = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$status, $admin_id, $comments, $application_id]);
            
            if ($result) {
                // Get application and send notification
                $app = $this->getApplicationById($application_id);
                if ($app) {
                    $this->sendStatusUpdateEmail($app['email'], $app['first_name'], $status);
                }
            }
            
            return ['success' => true, 'message' => 'Application status updated'];
        } catch (Exception $e) {
            error_log('Status update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }
    
    /**
     * Send status update email
     */
    protected function sendStatusUpdateEmail($email, $first_name, $status) {
        $status_messages = [
            'submitted' => 'Your application has been received and is waiting for review.',
            'under_review' => 'Your application is currently being reviewed by our admissions team.',
            'approved' => 'Congratulations! Your application has been approved. Please proceed with payment.',
            'rejected' => 'Unfortunately, your application was not accepted at this time.',
            'pending_payment' => 'Your application has been approved. Please complete the payment to finalize your enrollment.'
        ];
        
        $subject = 'IDMA Application Status Update';
        $message = "<html><body>";
        $message .= "<h2>Application Status</h2>";
        $message .= "<p>Dear " . htmlspecialchars($first_name) . ",</p>";
        $message .= "<p>" . $status_messages[$status] . "</p>";
        $message .= "<p>Login to your portal to view more details: <a href='" . APP_URL . "/login'>IDMA Portal</a></p>";
        $message .= "<p>Best regards,<br>IDMA Admissions Office</p>";
        $message .= "</body></html>";
        
        return sendEmail($email, $subject, $message, true);
    }
    
    /**
     * Get all applications
     */
    public function getAllApplications($status = null, $limit = 50, $offset = 0) {
        try {
            $query = "SELECT * FROM applications WHERE 1 = 1";
            $params = [];
            
            if ($status) {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}

?>