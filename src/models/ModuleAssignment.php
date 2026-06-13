<?php
/**
 * Module Assignment Model
 * Handles module assignments to lecturers
 */

class ModuleAssignment {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Assign module to lecturer
     */
    public function assignModuleToLecturer($module_id, $lecturer_id, $academic_year, $semester) {
        try {
            // Check if assignment already exists
            $check_stmt = $this->pdo->prepare("
                SELECT id FROM module_assignments 
                WHERE module_id = ? AND lecturer_id = ? AND academic_year = ? AND semester = ?
            ");
            $check_stmt->execute([$module_id, $lecturer_id, $academic_year, $semester]);
            
            if ($check_stmt->fetch()) {
                return ['success' => false, 'message' => 'Module already assigned to this lecturer for this semester'];
            }
            
            // Create assignment
            $stmt = $this->pdo->prepare("
                INSERT INTO module_assignments (module_id, lecturer_id, academic_year, semester, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
            
            $result = $stmt->execute([$module_id, $lecturer_id, $academic_year, $semester]);
            
            if ($result) {
                Security::logActivity(
                    getCurrentUserID(),
                    'assign_module',
                    'modules',
                    $module_id,
                    'module',
                    null,
                    ['lecturer_id' => $lecturer_id, 'academic_year' => $academic_year, 'semester' => $semester]
                );
            }
            
            return ['success' => $result, 'message' => $result ? 'Module assigned successfully' : 'Failed to assign module'];
        } catch (Exception $e) {
            error_log('Module assignment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error assigning module'];
        }
    }
    
    /**
     * Remove module assignment
     */
    public function removeModuleAssignment($assignment_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM module_assignments WHERE id = ?");
            $result = $stmt->execute([$assignment_id]);
            
            if ($result) {
                Security::logActivity(
                    getCurrentUserID(),
                    'remove_module_assignment',
                    'modules',
                    $assignment_id,
                    'assignment'
                );
            }
            
            return ['success' => $result, 'message' => $result ? 'Assignment removed' : 'Failed to remove'];
        } catch (Exception $e) {
            error_log('Remove assignment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error removing assignment'];
        }
    }
    
    /**
     * Get assigned modules for lecturer
     */
    public function getAssignedModulesForLecturer($lecturer_id, $academic_year = null, $semester = null) {
        try {
            $query = "
                SELECT ma.*, m.*, c.code as course_code, c.name as course_name
                FROM module_assignments ma
                JOIN modules m ON ma.module_id = m.id
                JOIN courses c ON m.course_id = c.id
                WHERE ma.lecturer_id = ?
            ";
            
            $params = [$lecturer_id];
            
            if ($academic_year) {
                $query .= " AND ma.academic_year = ?";
                $params[] = $academic_year;
            }
            
            if ($semester) {
                $query .= " AND ma.semester = ?";
                $params[] = $semester;
            }
            
            $query .= " ORDER BY c.code ASC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get assigned modules error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all module assignments
     */
    public function getAllAssignments($academic_year = null, $semester = null, $lecturer_id = null) {
        try {
            $query = "
                SELECT ma.*, m.*, c.code, c.name as course_name, 
                       l.user_id, u.first_name, u.last_name
                FROM module_assignments ma
                JOIN modules m ON ma.module_id = m.id
                JOIN courses c ON m.course_id = c.id
                JOIN lecturers l ON ma.lecturer_id = l.id
                JOIN users u ON l.user_id = u.id
                WHERE 1 = 1
            ";
            
            $params = [];
            
            if ($academic_year) {
                $query .= " AND ma.academic_year = ?";
                $params[] = $academic_year;
            }
            
            if ($semester) {
                $query .= " AND ma.semester = ?";
                $params[] = $semester;
            }
            
            if ($lecturer_id) {
                $query .= " AND ma.lecturer_id = ?";
                $params[] = $lecturer_id;
            }
            
            $query .= " ORDER BY c.code ASC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get all assignments error: ' . $e->getMessage());
            return [];
        }
    }
}

?>