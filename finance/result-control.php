<?php
/**
 * Finance Result Control Dashboard
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../src/helpers/Functions.php';
require_once '../src/helpers/Security.php';
require_once '../src/models/ResultVisibility.php';
require_once '../src/models/Payment.php';

requireRole(ROLE_FINANCE);

$result_visibility = new ResultVisibility($pdo);
$payment = new Payment($pdo);
$blocked_students = [];
$message = '';
$error = '';

// Handle block/unblock actions
if ($_POST['action'] ?? '' === 'block') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $reason = Security::sanitizeInput($_POST['reason'] ?? '');
    
    if ($student_id) {
        $result = $result_visibility->blockStudentResults($student_id, $reason);
        $message = $result['success'] ? 'Results blocked successfully' : $result['message'];
    }
}

if ($_GET['action'] ?? '' === 'unblock' && isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    $reason = 'Payment verified';
    
    $result = $result_visibility->unblockStudentResults($student_id, $reason);
    $message = $result['success'] ? 'Results unblocked successfully' : $result['message'];
}

// Get blocked students
try {
    $stmt = $pdo->prepare("
        SELECT rb.*, s.student_id, u.first_name, u.last_name, u.email,
               (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE student_id = s.id AND status = 'completed') as paid_amount
        FROM result_blocks rb
        JOIN students s ON rb.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE rb.status = 'blocked'
        ORDER BY rb.created_at DESC
    ");
    $stmt->execute();
    $blocked_students = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching blocked students: ' . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Access Control - Finance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/idma-sms-lms/public/css/style.css">
</head>
<body>
    <div class="container my-5">
        <div class="dashboard-header">
            <h1><i class="fas fa-lock"></i> Result Access Control</h1>
            <p>Manage student result visibility based on payment status</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stat-card">
                    <h5>Blocked Students</h5>
                    <div class="stat-value" style="color: #dc3545;"><?php echo count($blocked_students); ?></div>
                </div>
            </div>
        </div>

        <!-- Block Results Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-ban"></i> Block Student Results</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="blockForm">
                    <input type="hidden" name="action" value="block">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="student_id" class="form-label">Select Student <span class="text-danger">*</span></label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">-- Select Student --</option>
                                <?php 
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT s.id, s.student_id, u.first_name, u.last_name, u.email
                                        FROM students s
                                        JOIN users u ON s.user_id = u.id
                                        WHERE s.status = 'active'
                                        AND s.id NOT IN (SELECT student_id FROM result_blocks WHERE status = 'blocked')
                                        ORDER BY u.first_name ASC
                                    ");
                                    $stmt->execute();
                                    $active_students = $stmt->fetchAll();
                                    
                                    foreach ($active_students as $student):
                                ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                        </option>
                                <?php
                                    endforeach;
                                } catch (Exception $e) {
                                    error_log('Error fetching students: ' . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Blocking <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="E.g., Outstanding fees of SZL 15,000" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban"></i> Block Results
                    </button>
                </form>
            </div>
        </div>

        <!-- Blocked Students List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users"></i> Currently Blocked Students</h5>
            </div>
            <div class="card-body">
                <?php if (empty($blocked_students)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No students are currently blocked from viewing results
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Reason</th>
                                    <th>Blocked Since</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blocked_students as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($student['reason'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td><?php echo formatDateTime($student['created_at']); ?></td>
                                        <td>
                                            <a href="?action=unblock&student_id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Unblock this student?');">
                                                <i class="fas fa-unlock"></i> Unblock
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>