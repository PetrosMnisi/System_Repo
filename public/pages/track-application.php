<?php
/**
 * Application Status Tracking - Student Module
 * Allows students to track their application status
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../src/helpers/Functions.php';
require_once '../src/helpers/Security.php';
require_once '../src/models/Application.php';

$application = new Application($pdo);
$application_data = null;
$search_method = 'application_number'; // or 'email'
$search_value = '';
$search_attempted = false;
$error_message = '';
$success_message = '';

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_method = Security::sanitizeInput($_POST['search_method'] ?? 'application_number');
    $search_value = Security::sanitizeInput($_POST['search_value'] ?? '');
    $search_attempted = true;
    
    if (empty($search_value)) {
        $error_message = 'Please enter your application number or email address';
    } else {
        if ($search_method === 'application_number') {
            // Search by application number
            try {
                $stmt = $pdo->prepare("SELECT * FROM applications WHERE application_number = ?");
                $stmt->execute([$search_value]);
                $application_data = $stmt->fetch();
                
                if (!$application_data) {
                    $error_message = 'Application not found. Please check your application number.';
                }
            } catch (Exception $e) {
                $error_message = 'Error retrieving application. Please try again.';
                error_log('Search error: ' . $e->getMessage());
            }
        } else {
            // Search by email
            $application_data = $application->getApplicationByEmail($search_value);
            
            if (!$application_data) {
                $error_message = 'Application not found. Please check your email address.';
            }
        }
    }
}

// Get status badge color
function getStatusBadgeClass($status) {
    $classes = [
        'submitted' => 'bg-info',
        'under_review' => 'bg-warning',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'pending_payment' => 'bg-warning'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

// Get status description
function getStatusDescription($status) {
    $descriptions = [
        'submitted' => 'Your application has been received and is waiting for review.',
        'under_review' => 'Your application is currently being reviewed by our admissions team.',
        'approved' => 'Congratulations! Your application has been approved.',
        'rejected' => 'Unfortunately, your application was not accepted at this time.',
        'pending_payment' => 'Your application has been approved. Please complete the payment to finalize enrollment.'
    ];
    return $descriptions[$status] ?? 'Status unknown';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Application - IDMA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/idma-sms-lms/public/css/style.css">
    <style>
        .track-container {
            max-width: 900px;
            margin: 40px auto;
        }
        
        .search-section {
            background: linear-gradient(135deg, #1b8449 0%, #2d9e5f 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(27, 132, 73, 0.2);
        }
        
        .search-section h2 {
            margin-bottom: 30px;
            font-weight: bold;
        }
        
        .search-section .form-label {
            color: white;
            font-weight: 500;
        }
        
        .search-section .form-control,
        .search-section .form-select {
            background-color: rgba(255, 255, 255, 0.95);
            border: none;
            padding: 12px 15px;
        }
        
        .search-section .form-control:focus,
        .search-section .form-select:focus {
            background-color: white;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }
        
        .search-btn {
            background-color: white;
            color: #1b8449;
            border: none;
            padding: 12px 40px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background-color: #f0f8f5;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .status-timeline {
            position: relative;
            padding: 40px 0;
        }
        
        .status-step {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .status-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50px;
            left: 30px;
            width: 3px;
            height: 30px;
            background-color: #ddd;
        }
        
        .status-step.completed:not(:last-child)::after {
            background-color: #28a745;
        }
        
        .status-step.current:not(:last-child)::after {
            background-color: #ffc107;
        }
        
        .status-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .status-step.completed .status-icon {
            background-color: #d4edda;
            color: #28a745;
            border: 2px solid #28a745;
        }
        
        .status-step.current .status-icon {
            background-color: #fff3cd;
            color: #ffc107;
            border: 2px solid #ffc107;
            animation: pulse 2s infinite;
        }
        
        .status-step.pending .status-icon {
            background-color: #e2e3e5;
            color: #6c757d;
            border: 2px solid #6c757d;
        }
        
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }
        }
        
        .status-content {
            flex: 1;
        }
        
        .status-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .status-date {
            color: #666;
            font-size: 14px;
        }
        
        .application-details {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: bold;
            color: #1b8449;
            width: 30%;
            min-width: 150px;
        }
        
        .detail-value {
            color: #333;
            width: 70%;
        }
        
        .status-alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid;
        }
        
        .status-alert.approved {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .status-alert.under-review {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .status-alert.rejected {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .status-alert.submitted {
            background-color: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .document-card {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .document-icon {
            width: 40px;
            height: 40px;
            background-color: #1b8449;
            color: white;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/idma-sms-lms">
                <i class="fas fa-graduation-cap"></i> IDMA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/idma-sms-lms">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/idma-sms-lms/apply-online">Apply Online</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Track Application</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/idma-sms-lms/login">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container track-container">
        <!-- Search Section -->
        <div class="search-section">
            <h2><i class="fas fa-search"></i> Track Your Application</h2>
            <p class="mb-4">Enter your application number or email address to check your application status</p>
            
            <form method="POST" novalidate>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="search_method" class="form-label">Search By</label>
                        <select class="form-select" id="search_method" name="search_method">
                            <option value="application_number">Application Number</option>
                            <option value="email">Email Address</option>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="search_value" class="form-label">Enter Value</label>
                        <input type="text" class="form-control" id="search_value" name="search_value" 
                               placeholder="<?php echo $_POST['search_method'] === 'email' ? 'your@email.com' : 'APP-2026-XXXXX'; ?>" 
                               value="<?php echo htmlspecialchars($search_value); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn search-btn w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <?php if ($search_attempted): ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($application_data): ?>
                <!-- Application Found -->
                
                <!-- Status Alert -->
                <div class="status-alert <?php echo $application_data['status']; ?>">
                    <h4><i class="fas fa-info-circle"></i> Application Status</h4>
                    <p class="mb-0"><?php echo getStatusDescription($application_data['status']); ?></p>
                </div>

                <!-- Application Details -->
                <div class="application-details">
                    <h5 style="color: #1b8449; margin-bottom: 20px;">
                        <i class="fas fa-user"></i> Application Information
                    </h5>
                    
                    <div class="detail-row">
                        <div class="detail-label">Application Number:</div>
                        <div class="detail-value">
                            <strong><?php echo htmlspecialchars($application_data['application_number']); ?></strong>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Full Name:</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($application_data['first_name'] . ' ' . $application_data['last_name']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($application_data['email']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($application_data['phone']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Date Submitted:</div>
                        <div class="detail-value">
                            <?php echo formatDateTime($application_data['created_at']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value">
                            <span class="badge <?php echo getStatusBadgeClass($application_data['status']); ?>">
                                <?php echo ucwords(str_replace('_', ' ', $application_data['status'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($application_data['reviewed_date']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Reviewed Date:</div>
                            <div class="detail-value">
                                <?php echo formatDateTime($application_data['reviewed_date']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($application_data['reviewer_comments']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Reviewer Comments:</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($application_data['reviewer_comments']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Status Timeline -->
                <div class="application-details">
                    <h5 style="color: #1b8449; margin-bottom: 30px;">
                        <i class="fas fa-timeline"></i> Application Timeline
                    </h5>
                    
                    <div class="status-timeline">
                        <!-- Submitted -->
                        <div class="status-step completed">
                            <div class="status-icon"><i class="fas fa-check"></i></div>
                            <div class="status-content">
                                <div class="status-title">Application Submitted</div>
                                <div class="status-date"><?php echo formatDateTime($application_data['created_at']); ?></div>
                            </div>
                        </div>

                        <!-- Under Review -->
                        <div class="status-step <?php echo in_array($application_data['status'], ['under_review', 'approved', 'rejected', 'pending_payment']) ? 'completed' : (strtotime(date('Y-m-d')) > strtotime('+3 days', strtotime($application_data['created_at'])) ? 'current' : 'pending'); ?>">
                            <div class="status-icon"><i class="fas fa-eye"></i></div>
                            <div class="status-content">
                                <div class="status-title">Under Review</div>
                                <div class="status-date">Typically 3-5 business days</div>
                            </div>
                        </div>

                        <!-- Decision -->
                        <div class="status-step <?php echo in_array($application_data['status'], ['approved', 'rejected', 'pending_payment']) ? 'completed' : 'pending'; ?>">
                            <div class="status-icon">
                                <?php if ($application_data['status'] === 'approved' || $application_data['status'] === 'pending_payment'): ?>
                                    <i class="fas fa-check"></i>
                                <?php elseif ($application_data['status'] === 'rejected'): ?>
                                    <i class="fas fa-times"></i>
                                <?php else: ?>
                                    <i class="fas fa-hourglass-half"></i>
                                <?php endif; ?>
                            </div>
                            <div class="status-content">
                                <div class="status-title">
                                    <?php 
                                    if ($application_data['status'] === 'approved' || $application_data['status'] === 'pending_payment') {
                                        echo 'Application Approved';
                                    } elseif ($application_data['status'] === 'rejected') {
                                        echo 'Application Rejected';
                                    } else {
                                        echo 'Pending Decision';
                                    }
                                    ?>
                                </div>
                                <?php if ($application_data['reviewed_date']): ?>
                                    <div class="status-date"><?php echo formatDateTime($application_data['reviewed_date']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment (if approved) -->
                        <?php if ($application_data['status'] === 'pending_payment' || $application_data['status'] === 'approved'): ?>
                            <div class="status-step pending">
                                <div class="status-icon"><i class="fas fa-credit-card"></i></div>
                                <div class="status-content">
                                    <div class="status-title">Complete Payment</div>
                                    <div class="status-date">Pay 40% deposit to finalize enrollment</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Documents Section -->
                <?php if ($application_data['form_5_results'] || $application_data['application_fee_proof'] || $application_data['payment_proof']): ?>
                    <div class="application-details">
                        <h5 style="color: #1b8449; margin-bottom: 20px;">
                            <i class="fas fa-file-upload"></i> Submitted Documents
                        </h5>
                        
                        <?php if ($application_data['form_5_results']): ?>
                            <div class="document-card">
                                <div style="display: flex; align-items: center; flex: 1;">
                                    <div class="document-icon"><i class="fas fa-file-pdf"></i></div>
                                    <div>
                                        <div style="font-weight: bold;">Form 5 Results</div>
                                        <small class="text-muted">Uploaded: <?php echo formatDate($application_data['created_at']); ?></small>
                                    </div>
                                </div>
                                <a href="/idma-sms-lms/public/uploads/<?php echo htmlspecialchars($application_data['form_5_results']); ?>" class="btn btn-sm btn-primary" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($application_data['application_fee_proof']): ?>
                            <div class="document-card">
                                <div style="display: flex; align-items: center; flex: 1;">
                                    <div class="document-icon"><i class="fas fa-file-pdf"></i></div>
                                    <div>
                                        <div style="font-weight: bold;">Application Fee Proof</div>
                                        <small class="text-muted">Uploaded: <?php echo formatDate($application_data['created_at']); ?></small>
                                    </div>
                                </div>
                                <a href="/idma-sms-lms/public/uploads/<?php echo htmlspecialchars($application_data['application_fee_proof']); ?>" class="btn btn-sm btn-primary" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($application_data['payment_proof']): ?>
                            <div class="document-card">
                                <div style="display: flex; align-items: center; flex: 1;">
                                    <div class="document-icon"><i class="fas fa-file-pdf"></i></div>
                                    <div>
                                        <div style="font-weight: bold;">Payment Proof</div>
                                        <small class="text-muted">Uploaded: <?php echo formatDate($application_data['created_at']); ?></small>
                                    </div>
                                </div>
                                <a href="/idma-sms-lms/public/uploads/<?php echo htmlspecialchars($application_data['payment_proof']); ?>" class="btn btn-sm btn-primary" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($application_data['status'] === 'approved' || $application_data['status'] === 'pending_payment'): ?>
                        <a href="/idma-sms-lms/student/make-payment?app_id=<?php echo $application_data['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-credit-card"></i> Complete Payment
                        </a>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-secondary" onclick="window.print();">
                        <i class="fas fa-print"></i> Print Status
                    </button>
                    
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#contactModal">
                        <i class="fas fa-envelope"></i> Contact Admissions
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact Admissions Office</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Email:</strong> admissions@idma.sz</p>
                    <p><strong>Phone:</strong> +268 2404 2000</p>
                    <p><strong>Office Hours:</strong> Monday - Friday, 8:00 AM - 4:30 PM</p>
                    <p><strong>Address:</strong> IDMA Campus, Eswatini</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5">
        <div class="container">
            <p><strong>Institute of Development Management - Eswatini</strong></p>
            <p>Admissions Office</p>
            <p>&copy; 2026 All Rights Reserved</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>