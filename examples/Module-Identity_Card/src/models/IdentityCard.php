<?php

class IdentityCard {
    private $db;
    private $table = 'identity_cards';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all identity cards with pagination and filtering
     */
    public function getAll($page = 1, $limit = 10, $search = '', $status = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = [];
            $params = [];
            
            if (!empty($search)) {
                $whereConditions[] = "(ic.card_number LIKE ? OR s.name LIKE ? OR s.student_id LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if (!empty($status)) {
                $whereConditions[] = "ic.status = ?";
                $params[] = $status;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countQuery = "
                SELECT COUNT(*) as total
                FROM {$this->table} ic
                LEFT JOIN students s ON ic.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN departments d ON s.department_id = d.id
                {$whereClause}
            ";
            
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get paginated results
            $query = "
                SELECT 
                    ic.*,
                    s.name as student_name,
                    s.student_id as student_number,
                    s.email as student_email,
                    s.phone as student_phone,
                    s.photo as student_photo,
                    c.name as class_name,
                    d.name as department_name,
                    uc.name as created_by_name,
                    uu.name as updated_by_name
                FROM {$this->table} ic
                LEFT JOIN students s ON ic.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN departments d ON s.department_id = d.id
                LEFT JOIN users uc ON ic.created_by = uc.id
                LEFT JOIN users uu ON ic.updated_by = uu.id
                {$whereClause}
                ORDER BY ic.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate pagination info
            $totalPages = ceil($total / $limit);
            $pagination = [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ];
            
            return [
                'data' => $data,
                'pagination' => $pagination
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to fetch identity cards: " . $e->getMessage());
        }
    }

    /**
     * Get identity card by ID
     */
    public function getById($id) {
        try {
            $query = "
                SELECT 
                    ic.*,
                    s.name as student_name,
                    s.student_id as student_number,
                    s.email as student_email,
                    s.phone as student_phone,
                    s.photo as student_photo,
                    s.date_of_birth,
                    s.gender,
                    s.address,
                    c.name as class_name,
                    d.name as department_name,
                    uc.name as created_by_name,
                    uu.name as updated_by_name,
                    ug.name as generated_by_name
                FROM {$this->table} ic
                LEFT JOIN students s ON ic.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN departments d ON s.department_id = d.id
                LEFT JOIN users uc ON ic.created_by = uc.id
                LEFT JOIN users uu ON ic.updated_by = uu.id
                LEFT JOIN users ug ON ic.generated_by = ug.id
                WHERE ic.id = ?
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch identity card: " . $e->getMessage());
        }
    }

    /**
     * Get active identity card by student ID
     */
    public function getActiveByStudentId($studentId) {
        try {
            $query = "
                SELECT ic.*
                FROM {$this->table} ic
                WHERE ic.student_id = ? 
                AND ic.status IN ('active', 'generated')
                AND ic.valid_until >= CURDATE()
                ORDER BY ic.created_at DESC
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$studentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch active identity card: " . $e->getMessage());
        }
    }

    /**
     * Create new identity card
     */
    public function create($data) {
        try {
            $query = "
                INSERT INTO {$this->table} 
                (student_id, card_type, card_number, valid_from, valid_until, status, notes, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['student_id'],
                $data['card_type'],
                $data['card_number'],
                $data['valid_from'],
                $data['valid_until'],
                $data['status'],
                $data['notes'],
                $data['created_by']
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (Exception $e) {
            throw new Exception("Failed to create identity card: " . $e->getMessage());
        }
    }

    /**
     * Update identity card
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if ($key !== 'id') {
                    $fields[] = "{$key} = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $id;
            $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute($values);
        } catch (Exception $e) {
            throw new Exception("Failed to update identity card: " . $e->getMessage());
        }
    }

    /**
     * Delete identity card
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            throw new Exception("Failed to delete identity card: " . $e->getMessage());
        }
    }

    /**
     * Get identity cards by status
     */
    public function getByStatus($status) {
        try {
            $query = "
                SELECT 
                    ic.*,
                    s.name as student_name,
                    s.student_id as student_number,
                    c.name as class_name,
                    d.name as department_name
                FROM {$this->table} ic
                LEFT JOIN students s ON ic.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE ic.status = ?
                ORDER BY ic.created_at DESC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch identity cards by status: " . $e->getMessage());
        }
    }

    /**
     * Get expiring identity cards
     */
    public function getExpiring($days = 30) {
        try {
            $query = "
                SELECT 
                    ic.*,
                    s.name as student_name,
                    s.student_id as student_number,
                    s.email as student_email,
                    c.name as class_name,
                    d.name as department_name,
                    DATEDIFF(ic.valid_until, CURDATE()) as days_until_expiry
                FROM {$this->table} ic
                LEFT JOIN students s ON ic.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE ic.status = 'active' 
                AND ic.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY ic.valid_until ASC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch expiring identity cards: " . $e->getMessage());
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics() {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_cards,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_cards,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cards,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_cards,
                    SUM(CASE WHEN status = 'generated' THEN 1 ELSE 0 END) as generated_cards,
                    SUM(CASE WHEN valid_until < CURDATE() THEN 1 ELSE 0 END) as expired_by_date
                FROM {$this->table}
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch statistics: " . $e->getMessage());
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus($ids, $status) {
        try {
            if (empty($ids)) {
                return false;
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $query = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})";
            
            $params = array_merge([$status], $ids);
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (Exception $e) {
            throw new Exception("Failed to bulk update status: " . $e->getMessage());
        }
    }

    /**
     * Check if card number exists
     */
    public function cardNumberExists($cardNumber, $excludeId = null) {
        try {
            $query = "SELECT id FROM {$this->table} WHERE card_number = ?";
            $params = [$cardNumber];
            
            if ($excludeId) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (Exception $e) {
            throw new Exception("Failed to check card number: " . $e->getMessage());
        }
    }

    /**
     * Get cards by date range
     */
    public function getByDateRange($startDate, $endDate) {
        try {
            $query = "
                SELECT 
                    ic.*,
                    s.name as student_name,
                    s.student_id as student_number,
                    c.name as class_name,
                    d.name as department_name
                FROM {$this->table} ic
                LEFT JOIN students s ON ic.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE ic.created_at BETWEEN ? AND ?
                ORDER BY ic.created_at DESC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch cards by date range: " . $e->getMessage());
        }
    }
}
