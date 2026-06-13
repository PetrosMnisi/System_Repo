<?php
/**
 * Online Application Form - Public Page
 * Bootstrap-based form for student applications
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../src/helpers/Functions.php';
require_once '../src/helpers/Security.php';
require_once '../src/models/Application.php';

$application = new Application($pdo);
$programs = [];
$form_submitted = false;
$submission_result = null;

// Get available programs
try {
    $stmt = $pdo->prepare("SELECT id, name FROM programs WHERE status = 'active' ORDER BY name ASC");
    $stmt->execute();
    $programs = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching programs: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_submitted = true;
    
    $submission_result = $application->submitApplication([
        'first_name' => Security::sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => Security::sanitizeInput($_POST['last_name'] ?? ''),
        'email' => Security::sanitizeInput($_POST['email'] ?? ''),
        'phone' => Security::sanitizeInput($_POST['phone'] ?? ''),
        'date_of_birth' => Security::sanitizeInput($_POST['date_of_birth'] ?? ''),
        'gender' => Security::sanitizeInput($_POST['gender'] ?? ''),
        'nationality' => Security::sanitizeInput($_POST['nationality'] ?? ''),
        'address' => Security::sanitizeInput($_POST['address'] ?? ''),
        'city' => Security::sanitizeInput($_POST['city'] ?? ''),
        'postal_code' => Security::sanitizeInput($_POST['postal_code'] ?? ''),
        'country' => Security::sanitizeInput($_POST['country'] ?? ''),
        'program_id' => intval($_POST['program_id'] ?? 0)
    ]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Application - IDMA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/idma-sms-lms/public/css/style.css">
    <style>
        .form-section {
            margin-bottom: 30px;
            padding: 25px;
            background-color: #f9f9f9;
            border-left: 5px solid #1b8449;
            border-radius: 5px;
        }
        
        .form-section-title {
            color: #1b8449;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .file-upload-area {
            border: 2px dashed #1b8449;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background-color: rgba(27, 132, 73, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            background-color: rgba(27, 132, 73, 0.1);
            border-color: #0d5a2f;
        }
        
        .file-upload-area i {
            font-size: 48px;
            color: #1b8449;
            margin-bottom: 10px;
        }
        
        .file-name {
            color: #28a745;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .required-note {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .progress-bar {
            background-color: #1b8449;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .form-header h1 {
            color: #1b8449;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #666;
            font-size: 16px;
        }
        
        .submit-section {
            background: linear-gradient(135deg, #f9f9f9 0%, #f0f8f5 100%);
            padding: 30px;
            border-radius: 8px;
            border-top: 3px solid #1b8449;
            margin-top: 30px;
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
                        <a class="nav-link active" href="#">Apply Online</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/idma-sms-lms/track-application">Track Application</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/idma-sms-lms/login">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if ($form_submitted): ?>
            <?php if ($submission_result['success']): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Success!</h4>
                    <p><?php echo htmlspecialchars($submission_result['message']); ?></p>
                    <hr>
                    <p><strong>Application Number: </strong><span class="badge bg-success"><?php echo htmlspecialchars($submission_result['application_number']); ?></span></p>
                    <p>Please save this application number. You can use it to track your application status.</p>
                    <p><a href="/idma-sms-lms/track-application" class="btn btn-success btn-sm">Track Your Application</a></p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php else: ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-exclamation-circle"></i> Error</h4>
                    <p><?php echo htmlspecialchars($submission_result['message']); ?></p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="form-header">
            <h1><i class="fas fa-graduation-cap"></i> Online Application Form</h1>
            <p>Institute of Development Management - Eswatini</p>
            <p class="text-muted">Please fill in all required fields marked with <span class="text-danger">*</span></p>
        </div>

        <form method="POST" enctype="multipart/form-data" id="applicationForm" novalidate>
            <!-- Personal Information Section -->
            <div class="form-section">
                <h5 class="form-section-title"><i class="fas fa-user"></i> Personal Information</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                        <div class="invalid-feedback">First name is required.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                        <div class="invalid-feedback">Last name is required.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">Valid email is required.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9\s\-\+\(\)]+" required>
                        <div class="invalid-feedback">Valid phone number is required.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                        <div class="invalid-feedback">Date of birth is required.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">-- Select Gender --</option>
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="form-section">
                <h5 class="form-section-title"><i class="fas fa-map-marker-alt"></i> Contact Information</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nationality" class="form-label">Nationality <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nationality" name="nationality" required>
                        <div class="invalid-feedback">Nationality is required.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="country" name="country" required>
                        <div class="invalid-feedback">Country is required.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Street Address</label>
                    <input type="text" class="form-control" id="address" name="address">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="postal_code" class="form-label">Postal Code</label>
                        <input type="text" class="form-control" id="postal_code" name="postal_code">
                    </div>
                </div>
            </div>

            <!-- Academic Information Section -->
            <div class="form-section">
                <h5 class="form-section-title"><i class="fas fa-book"></i> Academic Information</h5>
                
                <div class="mb-3">
                    <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
                    <select class="form-select" id="program_id" name="program_id" required>
                        <option value="">-- Select Program --</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?php echo $program['id']; ?>"><?php echo htmlspecialchars($program['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Program selection is required.</div>
                </div>
            </div>

            <!-- Document Upload Section -->
            <div class="form-section">
                <h5 class="form-section-title"><i class="fas fa-file-upload"></i> Required Documents</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Form 5 Results <span class="text-danger">*</span></label>
                        <div class="file-upload-area" onclick="document.getElementById('form_5_results').click();">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="mb-0"><strong>Click to upload</strong> or drag and drop</p>
                            <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                            <div id="form5-name" class="file-name"></div>
                        </div>
                        <input type="file" id="form_5_results" name="form_5_results" class="d-none" accept=".pdf,.jpg,.jpeg,.png" required onchange="updateFileName('form_5_results', 'form5-name')">
                        <div class="invalid-feedback">Form 5 Results are required.</div>
                        <div class="required-note">Please upload your Form 5 examination results</div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">Application Fee Payment Proof <span class="text-danger">*</span></label>
                        <div class="file-upload-area" onclick="document.getElementById('application_fee_proof').click();">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="mb-0"><strong>Click to upload</strong> or drag and drop</p>
                            <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                            <div id="appfee-name" class="file-name"></div>
                        </div>
                        <input type="file" id="application_fee_proof" name="application_fee_proof" class="d-none" accept=".pdf,.jpg,.jpeg,.png" required onchange="updateFileName('application_fee_proof', 'appfee-name')">
                        <div class="invalid-feedback">Application fee proof is required.</div>
                        <div class="required-note">Application Fee: SZL 100</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Deposit Payment Proof (40% of Tuition)</label>
                        <div class="file-upload-area" onclick="document.getElementById('payment_proof').click();">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="mb-0"><strong>Click to upload</strong> or drag and drop</p>
                            <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                            <div id="payment-name" class="file-name"></div>
                        </div>
                        <input type="file" id="payment_proof" name="payment_proof" class="d-none" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName('payment_proof', 'payment-name')">
                        <div class="required-note">Optional: Upload 40% tuition deposit proof to fast-track approval</div>
                    </div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="form-section">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label" for="agree_terms">
                        I confirm that the information provided above is accurate and true. I understand that providing false information may result in rejection of my application.
                        <span class="text-danger">*</span>
                    </label>
                    <div class="invalid-feedback">You must agree to the terms.</div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="submit-section">
                <div class="row">
                    <div class="col-md-6">
                        <button type="reset" class="btn btn-secondary btn-lg w-100">
                            <i class="fas fa-redo"></i> Clear Form
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </div>
                </div>
                <p class="text-center mt-3 text-muted">
                    <small>After submission, you will receive a confirmation email with your application number. Use this number to track your application status.</small>
                </p>
            </div>
        </form>
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
    <script>
        // Form validation
        (function() {
            'use strict';
            const form = document.getElementById('applicationForm');
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })();

        // Update file names
        function updateFileName(inputId, displayId) {
            const input = document.getElementById(inputId);
            const display = document.getElementById(displayId);
            if (input.files && input.files[0]) {
                display.textContent = '✓ ' + input.files[0].name;
            }
        }

        // Drag and drop functionality
        document.querySelectorAll('.file-upload-area').forEach(area => {
            area.addEventListener('dragover', (e) => {
                e.preventDefault();
                area.style.backgroundColor = 'rgba(27, 132, 73, 0.15)';
            });
            
            area.addEventListener('dragleave', () => {
                area.style.backgroundColor = 'rgba(27, 132, 73, 0.05)';
            });
            
            area.addEventListener('drop', (e) => {
                e.preventDefault();
                area.style.backgroundColor = 'rgba(27, 132, 73, 0.05)';
                
                const input = area.nextElementSibling;
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    input.files = e.dataTransfer.files;
                    updateFileName(input.id, area.nextElementSibling.nextElementSibling?.nextElementSibling?.id);
                }
            });
        });
    </script>
</body>
</html>