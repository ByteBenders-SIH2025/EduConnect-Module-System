<?php
/**
 * Template Controller
 * Copy and modify this file for your new module
 */

require_once __DIR__ . '/../Models/Template.php';
require_once __DIR__ . '/../../database/connection.php';

class TemplateController extends BaseController {
    private $templateModel;
    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = require_once __DIR__ . '/../../database/connection.php';
        $this->templateModel = new Template($this->db);
    }

    /**
     * Get all template items with pagination and filtering
     */
    public function index() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';

            $filters = [];
            if (!empty($search)) {
                $filters['search'] = $search;
            }
            if (!empty($status)) {
                $filters['status'] = $status;
            }

            $result = $this->templateModel->getAll($page, $limit, $filters);

            $this->sendResponse([
                'data' => $result['items'],
                'pagination' => $result['pagination']
            ], 'Items retrieved successfully');

        } catch (Exception $e) {
            $this->sendError('Failed to retrieve items: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a specific template item by ID
     */
    public function show($id) {
        try {
            if (!is_numeric($id)) {
                $this->sendError('Invalid item ID', 400);
                return;
            }

            $item = $this->templateModel->getById($id);

            if (!$item) {
                $this->sendError('Item not found', 404);
                return;
            }

            $this->sendResponse($item, 'Item retrieved successfully');

        } catch (Exception $e) {
            $this->sendError('Failed to retrieve item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new template item
     */
    public function store() {
        try {
            $input = $this->getJsonInput();
            
            // Validate required fields
            if (empty($input['name'])) {
                $this->sendError('Name is required', 400);
                return;
            }

            // Prepare data
            $data = [
                'name' => trim($input['name']),
                'description' => isset($input['description']) ? trim($input['description']) : '',
                'status' => isset($input['status']) ? $input['status'] : 'active'
            ];

            // Validate status
            if (!in_array($data['status'], ['active', 'inactive', 'pending'])) {
                $this->sendError('Invalid status value', 400);
                return;
            }

            $itemId = $this->templateModel->create($data);

            if ($itemId) {
                $item = $this->templateModel->getById($itemId);
                $this->sendResponse($item, 'Item created successfully', 201);
            } else {
                $this->sendError('Failed to create item', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to create item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an existing template item
     */
    public function update($id) {
        try {
            if (!is_numeric($id)) {
                $this->sendError('Invalid item ID', 400);
                return;
            }

            $input = $this->getJsonInput();
            
            // Check if item exists
            $existingItem = $this->templateModel->getById($id);
            if (!$existingItem) {
                $this->sendError('Item not found', 404);
                return;
            }

            // Prepare data
            $data = [];
            if (isset($input['name'])) {
                $data['name'] = trim($input['name']);
            }
            if (isset($input['description'])) {
                $data['description'] = trim($input['description']);
            }
            if (isset($input['status'])) {
                if (!in_array($input['status'], ['active', 'inactive', 'pending'])) {
                    $this->sendError('Invalid status value', 400);
                    return;
                }
                $data['status'] = $input['status'];
            }

            if (empty($data)) {
                $this->sendError('No data provided for update', 400);
                return;
            }

            $success = $this->templateModel->update($id, $data);

            if ($success) {
                $item = $this->templateModel->getById($id);
                $this->sendResponse($item, 'Item updated successfully');
            } else {
                $this->sendError('Failed to update item', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to update item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a template item
     */
    public function destroy($id) {
        try {
            if (!is_numeric($id)) {
                $this->sendError('Invalid item ID', 400);
                return;
            }

            // Check if item exists
            $existingItem = $this->templateModel->getById($id);
            if (!$existingItem) {
                $this->sendError('Item not found', 404);
                return;
            }

            $success = $this->templateModel->delete($id);

            if ($success) {
                $this->sendResponse(null, 'Item deleted successfully');
            } else {
                $this->sendError('Failed to delete item', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to delete item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get statistics for the template module
     */
    public function statistics() {
        try {
            $stats = $this->templateModel->getStatistics();
            $this->sendResponse($stats, 'Statistics retrieved successfully');

        } catch (Exception $e) {
            $this->sendError('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk operations (activate, deactivate, delete)
     */
    public function bulkAction() {
        try {
            $input = $this->getJsonInput();
            
            if (!isset($input['action']) || !isset($input['ids'])) {
                $this->sendError('Action and IDs are required', 400);
                return;
            }

            $action = $input['action'];
            $ids = $input['ids'];

            if (!is_array($ids) || empty($ids)) {
                $this->sendError('Invalid IDs provided', 400);
                return;
            }

            $validActions = ['activate', 'deactivate', 'delete'];
            if (!in_array($action, $validActions)) {
                $this->sendError('Invalid action. Allowed: ' . implode(', ', $validActions), 400);
                return;
            }

            $result = $this->templateModel->bulkAction($action, $ids);

            if ($result['success']) {
                $this->sendResponse($result, 'Bulk action completed successfully');
            } else {
                $this->sendError('Bulk action failed: ' . $result['message'], 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to perform bulk action: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export template items
     */
    public function export() {
        try {
            $format = isset($_GET['format']) ? $_GET['format'] : 'json';
            $filters = [];

            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }

            $items = $this->templateModel->getAllForExport($filters);

            if ($format === 'csv') {
                $this->exportToCSV($items);
            } else {
                $this->sendResponse($items, 'Export completed successfully');
            }

        } catch (Exception $e) {
            $this->sendError('Failed to export data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export data to CSV format
     */
    private function exportToCSV($items) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_items_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['ID', 'Name', 'Description', 'Status', 'Created At', 'Updated At']);

        // CSV data
        foreach ($items as $item) {
            fputcsv($output, [
                $item['id'],
                $item['name'],
                $item['description'],
                $item['status'],
                $item['created_at'],
                $item['updated_at']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Module lifecycle hooks
     */
    public static function onInstall() {
        // Called when module is installed
        // Create necessary database tables, default settings, etc.
        return ['success' => true, 'message' => 'Template module installed successfully'];
    }

    public static function onUninstall() {
        // Called when module is uninstalled
        // Clean up database tables, files, etc.
        return ['success' => true, 'message' => 'Template module uninstalled successfully'];
    }

    public static function onActivate() {
        // Called when module is activated
        return ['success' => true, 'message' => 'Template module activated successfully'];
    }

    public static function onDeactivate() {
        // Called when module is deactivated
        return ['success' => true, 'message' => 'Template module deactivated successfully'];
    }
}
?>
