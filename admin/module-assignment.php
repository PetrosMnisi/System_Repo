<?php
/**
 * Admin Module Assignment Controller
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../src/helpers/Functions.php';
require_once '../src/helpers/Security.php';
require_once '../src/models/ModuleAssignment.php';

requireRole(ROLE_ADMIN);

$module_assignment = new ModuleAssignment($pdo);
$action = $_GET['action'] ?? 'list';
$assignments = [];
$lecturers = [];
$modules = [];
$courses = [];
$message = '';
$error = '';

// Get current academic year and semester
$current_year = date('Y');
$current_semester = ceil(date('n') / 4); // 1, 2, or 3
$academic_year = $_GET['academic_year'] ?? $current_year;
$semester = $_GET['semester'] ?? $current_semester;

// Get all lecturers
try {
    $stmt = $pdo->prepare("SELECT l.id, l.user_id, u.first_name, u.last_name FROM lecturers l JOIN users u ON l.user_id = u.id WHERE l.status = 'active' ORDER BY u.first_name ASC");
    $stmt->execute();
    $lecturers = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching lecturers: ' . $e->getMessage());
}

// Get all courses for assignment
try {
    $stmt = $pdo->prepare("
        SELECT c.id as course_id, c.code, c.name, p.name as program_name
        FROM courses c
        JOIN programs p ON c.program_id = p.id
        WHERE c.status = 'active'
        ORDER BY c.code ASC
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching courses: ' . $e->getMessage());
}

// Handle assign module
if ($_POST['action'] ?? '' === 'assign') {
    $module_id = intval($_POST['module_id'] ?? 0);
    $lecturer_id = intval($_POST['lecturer_id'] ?? 0);
    
    if (!$module_id || !$lecturer_id) {
        $error = 'Module and Lecturer are required';
    } else {
        $result = $module_assignment->assignModuleToLecturer(
            $module_id,
            $lecturer_id,
            $academic_year,
            $semester
        );
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Handle remove assignment
if ($_GET['action'] ?? '' === 'remove' && isset($_GET['id'])) {
    $result = $module_assignment->removeModuleAssignment(intval($_GET['id']));
    if ($result['success']) {
        $message = 'Assignment removed successfully';
    } else {
        $error = $result['message'];
    }
}

// Get current assignments
$assignments = $module_assignment->getAllAssignments($academic_year, $semester);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module Assignment - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/idma-sms-lms/public/css/style.css">
</head>
<body>
    <div class="container my-5">
        <div class="dashboard-header">
            <h1><i class="fas fa-tasks"></i> Module Assignment Management</h1>
            <p>Assign modules to lecturers for each semester</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label">Academic Year</label>
                <select class="form-select" id="academicYear" onchange="filterAssignments()">
                    <?php for ($y = 2024; $y <= 2030; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $academic_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Semester</label>
                <select class="form-select" id="semester" onchange="filterAssignments()">
                    <option value="1" <?php echo $semester == 1 ? 'selected' : ''; ?>>1st Semester</option>
                    <option value="2" <?php echo $semester == 2 ? 'selected' : ''; ?>>2nd Semester</option>
                    <option value="3" <?php echo $semester == 3 ? 'selected' : ''; ?>>3rd Semester</option>
                </select>
            </div>
        </div>

        <!-- Assignment Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Assign New Module</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="assignmentForm">
                    <input type="hidden" name="action" value="assign">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                            <select class="form-select" id="course_id" onchange="loadModules()" required>
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="module_id" class="form-label">Module <span class="text-danger">*</span></label>
                            <select class="form-select" id="module_id" name="module_id" required>
                                <option value="">-- Select Module --</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="lecturer_id" class="form-label">Lecturer <span class="text-danger">*</span></label>
                        <select class="form-select" id="lecturer_id" name="lecturer_id" required>
                            <option value="">-- Select Lecturer --</option>
                            <?php foreach ($lecturers as $lecturer): ?>
                                <option value="<?php echo $lecturer['id']; ?>">
                                    <?php echo htmlspecialchars($lecturer['first_name'] . ' ' . $lecturer['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Assign Module
                    </button>
                </form>
            </div>
        </div>

        <!-- Current Assignments -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Current Assignments (<?php echo htmlspecialchars($academic_year); ?> - Semester <?php echo htmlspecialchars($semester); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No assignments for this period
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Module</th>
                                    <th>Lecturer</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($assignment['code']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($assignment['course_name']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo ucfirst($assignment['status']); ?></span>
                                        </td>
                                        <td>
                                            <a href="?action=remove&id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove assignment?');">
                                                <i class="fas fa-trash"></i> Remove
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
    <script>
        // Load modules based on selected course
        function loadModules() {
            const courseId = document.getElementById('course_id').value;
            if (!courseId) return;
            
            fetch(`/idma-sms-lms/api/get-modules?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('module_id');
                    select.innerHTML = '<option value="">-- Select Module --</option>';
                    data.forEach(module => {
                        const option = document.createElement('option');
                        option.value = module.id;
                        option.textContent = module.name;
                        select.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        function filterAssignments() {
            const year = document.getElementById('academicYear').value;
            const semester = document.getElementById('semester').value;
            window.location.href = `?academic_year=${year}&semester=${semester}`;
        }
    </script>
</body>
</html>