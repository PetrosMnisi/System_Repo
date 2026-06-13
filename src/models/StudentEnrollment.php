<?php
/**
 * Student Enrollment Model
 * Handles student enrollment in modules
 */

class StudentEnrollment {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if student has paid 40% deposit
     */
    public function hasStudentPaidDeposit($student_id, $total_tuition = 25000) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as paid_amount
                FROM payments
                WHERE student_id = ? AND status = 'completed'
            ");
            
            $stmt->execute([$student_id]);
            $result = $stmt->fetch();
            $paid_amount = $result['paid_amount'] ?? 0;
            
            $required_deposit = $total_tuition * (DEPOSIT_PERCENTAGE / 100);
            return $paid_amount >= $required_deposit;
        } catch (Exception $e) {
            error_log('Deposit check error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if student has paid full tuition
     */
    public function hasStudentPaidFullTuition($student_id, $total_tuition = 25000) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as paid_amount
                FROM payments
                WHERE student_id = ? AND status = 'completed'
            ");
            
            $stmt->execute([$student_id]);
            $result = $stmt->fetch();
            $paid_amount = $result['paid_amount'] ?? 0;
            
            return $paid_amount >= $total_tuition;
        } catch (Exception $e) {
            error_log('Full payment check error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available modules for enrollment
     */
    public function getAvailableModules($academic_year, $semester, $program_id = null) {
        try {
            $query = "
                SELECT DISTINCT m.*, c.code, c.name as course_name, c.credits, c.academic_year, c.semester
                FROM modules m
                JOIN courses c ON m.course_id = c.id
                WHERE c.academic_year = ? AND c.semester = ? AND c.status = 'active'
            ";
            
            $params = [$academic_year, $semester];
            
            if ($program_id) {
                $query .= " AND c.program_id = ?";
                $params[] = $program_id;
            }
            
            $query .= " ORDER BY c.code ASC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get available modules error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Enroll student in modules
     */
    public function enrollStudentInModules($student_id, $module_ids, $academic_year, $semester) {
        try {
            // Check if student has paid deposit
            if (!$this->hasStudentPaidDeposit($student_id)) {
                return ['success' => false, 'message' => 'You must pay 40% deposit before enrolling in modules'];
            }
            
            // Get student program
            $student_stmt = $this->pdo->prepare("SELECT program_id FROM students WHERE id = ?");
            $student_stmt->execute([$student_id]);
            $student = $student_stmt->fetch();
            
            if (!$student) {
                return ['success' => false, 'message' => 'Student not found'];
            }
            
            $enrolled_count = 0;
            $errors = [];
            
            foreach ($module_ids as $module_id) {
                try {
                    // Get course information
                    $course_stmt = $this->pdo->prepare("
                        SELECT c.id FROM courses c
                        JOIN modules m ON c.id = m.course_id
                        WHERE m.id = ? AND c.academic_year = ? AND c.semester = ?
                    ");
                    $course_stmt->execute([$module_id, $academic_year, $semester]);
                    $course = $course_stmt->fetch();
                    
                    if (!$course) {
                        $errors[] = 'Module ' . $module_id . ' not found';
                        continue;
                    }
                    
                    // Check if already enrolled
                    $check_stmt = $this->pdo->prepare("
                        SELECT id FROM enrollments
                        WHERE student_id = ? AND course_id = ? AND status IN ('active', 'completed')
                    ");
                    $check_stmt->execute([$student_id, $course['id']]);
                    
                    if ($check_stmt->fetch()) {
                        $errors[] = 'Already enrolled in module ' . $module_id;
                        continue;
                    }
                    
                    // Create enrollment
                    $enroll_stmt = $this->pdo->prepare("
                        INSERT INTO enrollments (student_id, course_id, enrollment_date, status)
                        VALUES (?, ?, NOW(), 'active')
                    ");
                    
                    if ($enroll_stmt->execute([$student_id, $course['id']])) {
                        $enrolled_count++;
                    } else {
                        $errors[] = 'Failed to enroll in module ' . $module_id;
                    }
                } catch (Exception $e) {
                    $errors[] = 'Error with module ' . $module_id . ': ' . $e->getMessage();
                }
            }
            
            // Log activity
            Security::logActivity(
                getCurrentUserID(),
                'student_enrollment',
                'enrollments',
                $student_id,
                'student',
                null,
                ['module_count' => $enrolled_count, 'academic_year' => $academic_year, 'semester' => $semester]
            );
            
            $message = "Successfully enrolled in $enrolled_count module(s)";
            if (!empty($errors)) {
                $message .= ". Issues: " . implode("; ", $errors);
            }
            
            return ['success' => $enrolled_count > 0, 'message' => $message, 'enrolled_count' => $enrolled_count];
        } catch (Exception $e) {
            error_log('Enrollment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Enrollment failed'];
        }
    }
    
    /**
     * Get student enrollments
     */
    public function getStudentEnrollments($student_id, $academic_year = null, $semester = null) {
        try {
            $query = "
                SELECT e.*, c.code, c.name, c.credits, c.academic_year, c.semester
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE e.student_id = ?
            ";
            
            $params = [$student_id];
            
            if ($academic_year) {
                $query .= " AND c.academic_year = ?";
                $params[] = $academic_year;
            }
            
            if ($semester) {
                $query .= " AND c.semester = ?";
                $params[] = $semester;
            }
            
            $query .= " ORDER BY c.code ASC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get enrollments error: ' . $e->getMessage());
            return [];
        }
    }
}

?>