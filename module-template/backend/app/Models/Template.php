<?php
/**
 * Template Model
 * Copy and modify this file for your new module
 */

class Template {
    private $db;
    private $table = 'template_items';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all template items with pagination and filtering
     */
    public function getAll($page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = [];
            $params = [];

            if (!empty($filters['search'])) {
                $whereConditions[] = "(name LIKE ? OR description LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (!empty($filters['status'])) {
                $whereConditions[] = "status = ?";
                $params[] = $filters['status'];
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get items
            $query = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate pagination info
            $totalPages = ceil($total / $limit);
            $pagination = [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ];

            return [
                'items' => $items,
                'pagination' => $pagination
            ];

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Get a template item by ID
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Create a new template item
     */
    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table} (name, description, status, created_at, updated_at) 
                      VALUES (?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                $data['name'],
                $data['description'],
                $data['status']
            ]);

            if ($success) {
                return $this->db->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Update a template item
     */
    public function update($id, $data) {
        try {
            $setParts = [];
            $params = [];

            foreach ($data as $key => $value) {
                $setParts[] = "{$key} = ?";
                $params[] = $value;
            }

            if (empty($setParts)) {
                return false;
            }

            $setParts[] = "updated_at = NOW()";
            $params[] = $id;

            $query = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute($params);

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Delete a template item
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Get statistics for the template module
     */
    public function getStatistics() {
        try {
            $stats = [];

            // Total items
            $query = "SELECT COUNT(*) as total FROM {$this->table}";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['total_items'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Items by status
            $query = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($statusCounts as $status) {
                $stats['status_' . $status['status']] = $status['count'];
            }

            // Recent items (last 7 days)
            $query = "SELECT COUNT(*) as count FROM {$this->table} 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['recent_items'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            return $stats;

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Perform bulk actions on multiple items
     */
    public function bulkAction($action, $ids) {
        try {
            if (empty($ids)) {
                return ['success' => false, 'message' => 'No items selected'];
            }

            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $affectedRows = 0;

            switch ($action) {
                case 'activate':
                    $query = "UPDATE {$this->table} SET status = 'active', updated_at = NOW() 
                              WHERE id IN ({$placeholders})";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute($ids);
                    $affectedRows = $stmt->rowCount();
                    break;

                case 'deactivate':
                    $query = "UPDATE {$this->table} SET status = 'inactive', updated_at = NOW() 
                              WHERE id IN ({$placeholders})";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute($ids);
                    $affectedRows = $stmt->rowCount();
                    break;

                case 'delete':
                    $query = "DELETE FROM {$this->table} WHERE id IN ({$placeholders})";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute($ids);
                    $affectedRows = $stmt->rowCount();
                    break;

                default:
                    return ['success' => false, 'message' => 'Invalid action'];
            }

            return [
                'success' => true,
                'message' => "Bulk {$action} completed successfully",
                'affected_rows' => $affectedRows
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get all items for export (no pagination)
     */
    public function getAllForExport($filters = []) {
        try {
            // Build WHERE clause
            $whereConditions = [];
            $params = [];

            if (!empty($filters['search'])) {
                $whereConditions[] = "(name LIKE ? OR description LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (!empty($filters['status'])) {
                $whereConditions[] = "status = ?";
                $params[] = $filters['status'];
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            $query = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Search items by name or description
     */
    public function search($searchTerm, $limit = 10) {
        try {
            $query = "SELECT * FROM {$this->table} 
                      WHERE (name LIKE ? OR description LIKE ?) 
                      ORDER BY created_at DESC LIMIT ?";
            
            $searchPattern = '%' . $searchTerm . '%';
            $stmt = $this->db->prepare($query);
            $stmt->execute([$searchPattern, $searchPattern, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Get items by status
     */
    public function getByStatus($status, $limit = 10) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC LIMIT ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$status, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Check if item exists
     */
    public function exists($id) {
        try {
            $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Get table name
     */
    public function getTableName() {
        return $this->table;
    }
}
?>
