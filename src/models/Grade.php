<?php
/**
 * Grade Model
 */

class Grade {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calculate final grade
     */
    public function calculateGrade($enrollment_id, $lecturer_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT g.*, w.*, e.student_id, c.credits
                FROM grades g
                JOIN grade_weightings w ON g.course_id = w.course_id
                JOIN enrollments e ON g.enrollment_id = e.id
                JOIN courses c ON g.course_id = c.id
                WHERE g.enrollment_id = ? AND w.lecturer_id = ?
            ");
            
            $stmt->execute([$enrollment_id, $lecturer_id]);
            $grade = $stmt->fetch();
            
            if (!$grade) {
                return null;
            }
            
            // Calculate weighted average
            $assignment_avg = ($grade['individual_assignment_score'] + $grade['group_assignment_score']) / 2;
            $calculated = (
                ($assignment_avg * $grade['individual_assignment_weight']) +
                ($grade['test_score'] * $grade['test_weight']) +
                ($grade['exam_score'] * $grade['exam_weight'])
            ) / 100;
            
            $letter_grade = getLetterGrade($calculated);
            $gpa_points = getGPAPoints($letter_grade);
            
            return [
                'calculated_grade' => $calculated,
                'letter_grade' => $letter_grade,
                'gpa_points' => $gpa_points
            ];
        } catch (Exception $e) {
            error_log('Grade calculation error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Submit grades
     */
    public function submitGrades($enrollment_id, $lecturer_id, $grades_data) {
        try {
            // Validate scores
            foreach (['individual_assignment_score', 'group_assignment_score', 'test_score', 'exam_score'] as $field) {
                if (isset($grades_data[$field])) {
                    $score = floatval($grades_data[$field]);
                    if ($score < 0 || $score > 100) {
                        return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' must be between 0-100'];
                    }
                }
            }
            
            // Calculate final grade
            $stmt = $this->pdo->prepare("
                SELECT g.* FROM grades g
                WHERE g.enrollment_id = ?
            ");
            $stmt->execute([$enrollment_id]);
            $existing_grade = $stmt->fetch();
            
            if (!$existing_grade) {
                return ['success' => false, 'message' => 'Grade record not found'];
            }
            
            // Update grades
            $update_stmt = $this->pdo->prepare("
                UPDATE grades SET 
                    individual_assignment_score = ?,
                    group_assignment_score = ?,
                    test_score = ?,
                    exam_score = ?,
                    status = 'submitted',
                    submitted_date = NOW()
                WHERE enrollment_id = ? AND lecturer_id = ?
            ");
            
            $update_stmt->execute([
                $grades_data['individual_assignment_score'] ?? null,
                $grades_data['group_assignment_score'] ?? null,
                $grades_data['test_score'] ?? null,
                $grades_data['exam_score'] ?? null,
                $enrollment_id,
                $lecturer_id
            ]);
            
            // Log activity
            Security::logActivity(
                getCurrentUserID(),
                'submit_grades',
                'grades',
                $enrollment_id,
                'enrollment',
                null,
                $grades_data
            );
            
            return ['success' => true, 'message' => 'Grades submitted successfully'];
        } catch (Exception $e) {
            error_log('Grade submission error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit grades'];
        }
    }
    
    /**
     * Get student results
     */
    public function getStudentResults($student_id, $academic_year = null, $semester = null) {
        try {
            $query = "
                SELECT 
                    g.*, 
                    e.enrollment_date,
                    c.code as course_code,
                    c.name as course_name,
                    c.credits,
                    l.user_id as lecturer_user_id,
                    u.first_name as lecturer_first_name,
                    u.last_name as lecturer_last_name
                FROM grades g
                JOIN enrollments e ON g.enrollment_id = e.id
                JOIN courses c ON g.course_id = c.id
                JOIN lecturers l ON g.lecturer_id = l.id
                JOIN users u ON l.user_id = u.id
                WHERE g.student_id = ? AND g.status IN ('submitted', 'approved')
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
            
            $query .= " ORDER BY c.academic_year DESC, c.semester DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get results error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate student GPA
     */
    public function calculateStudentGPA($student_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT g.gpa_points, c.credits
                FROM grades g
                JOIN courses c ON g.course_id = c.id
                WHERE g.student_id = ? AND g.status = 'approved'
            ");
            
            $stmt->execute([$student_id]);
            $grades = $stmt->fetchAll();
            
            if (empty($grades)) {
                return 0.0;
            }
            
            $total_points = 0;
            $total_credits = 0;
            
            foreach ($grades as $grade) {
                $total_points += $grade['gpa_points'] * $grade['credits'];
                $total_credits += $grade['credits'];
            }
            
            $gpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0.0;
            
            // Update student record
            $update_stmt = $this->pdo->prepare("UPDATE students SET gpa = ? WHERE id = ?");
            $update_stmt->execute([$gpa, $student_id]);
            
            return $gpa;
        } catch (Exception $e) {
            error_log('GPA calculation error: ' . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Approve grades
     */
    public function approveGrades($grade_id, $admin_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE grades SET 
                    status = 'approved',
                    approved_date = NOW(),
                    approved_by = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$admin_id, $grade_id]);
            
            if ($result) {
                // Get grade details and create notification
                $grade_stmt = $this->pdo->prepare("
                    SELECT g.*, s.user_id FROM grades g
                    JOIN students s ON g.student_id = s.id
                    WHERE g.id = ?
                ");
                $grade_stmt->execute([$grade_id]);
                $grade = $grade_stmt->fetch();
                
                if ($grade) {
                    createNotification(
                        $grade['user_id'],
                        'Grades Released',
                        'Your grades for a course have been released. Please check your portal.',
                        'success',
                        'grade'
                    );
                }
                
                Security::logActivity($admin_id, 'approve_grades', 'grades', $grade_id, 'grade');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Grade approval error: ' . $e->getMessage());
            return false;
        }
    }
}

?>