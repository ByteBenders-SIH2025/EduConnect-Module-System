<?php

require_once __DIR__ . '/../Models/IdentityCard.php';
require_once __DIR__ . '/BaseController.php';

class IdentityCardController extends BaseController {
    private $identityCardModel;
    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = require_once __DIR__ . '/../../database/connection.php';
        $this->identityCardModel = new IdentityCard($this->db);
    }

    /**
     * List all identity cards with pagination and filtering
     */
    public function index() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $cardType = $_GET['card_type'] ?? '';

            $result = $this->identityCardModel->getAll($page, $limit, $search, $status, $cardType);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination']
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Failed to fetch identity cards: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create a new identity card
     */
    public function create() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['student_id', 'card_type', 'valid_from', 'valid_until'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    $this->sendResponse(400, [
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ]);
                    return;
                }
            }

            // Check if student already has an active card
            $existingCard = $this->identityCardModel->getActiveByStudentId($input['student_id']);
            if ($existingCard) {
                $this->sendResponse(409, [
                    'success' => false,
                    'message' => 'Student already has an active identity card'
                ]);
                return;
            }

            $cardData = [
                'student_id' => $input['student_id'],
                'card_type' => $input['card_type'],
                'card_number' => $this->generateCardNumber(),
                'valid_from' => $input['valid_from'],
                'valid_until' => $input['valid_until'],
                'status' => $input['status'] ?? 'pending',
                'notes' => $input['notes'] ?? '',
                'created_by' => $this->getCurrentUserId()
            ];

            $cardId = $this->identityCardModel->create($cardData);
            
            if ($cardId) {
                $card = $this->identityCardModel->getById($cardId);
                $this->sendResponse(201, [
                    'success' => true,
                    'message' => 'Identity card created successfully',
                    'data' => $card
                ]);
            } else {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Failed to create identity card'
                ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Failed to create identity card: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get identity card by ID
     */
    public function show($id) {
        try {
            $card = $this->identityCardModel->getById($id);
            
            if ($card) {
                $this->sendResponse(200, [
                    'success' => true,
                    'data' => $card
                ]);
            } else {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Identity card not found'
                ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Failed to fetch identity card: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update identity card
     */
    public function update($id) {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $card = $this->identityCardModel->getById($id);
            if (!$card) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Identity card not found'
                ]);
                return;
            }

            $updateData = array_filter($input, function($key) {
                return in_array($key, ['card_type', 'valid_from', 'valid_until', 'status', 'notes']);
            }, ARRAY_FILTER_USE_KEY);

            if (empty($updateData)) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'No valid fields to update'
                ]);
                return;
            }

            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $updateData['updated_by'] = $this->getCurrentUserId();

            $result = $this->identityCardModel->update($id, $updateData);
            
            if ($result) {
                $updatedCard = $this->identityCardModel->getById($id);
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => 'Identity card updated successfully',
                    'data' => $updatedCard
                ]);
            } else {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Failed to update identity card'
                ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Failed to update identity card: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete identity card
     */
    public function delete($id) {
        try {
            $card = $this->identityCardModel->getById($id);
            if (!$card) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Identity card not found'
                ]);
                return;
            }

            $result = $this->identityCardModel->delete($id);
            
            if ($result) {
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => 'Identity card deleted successfully'
                ]);
            } else {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Failed to delete identity card'
                ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Failed to delete identity card: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate identity card PDF
     */
    public function generate($id) {
        try {
            $card = $this->identityCardModel->getById($id);
            if (!$card) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Identity card not found'
                ]);
                return;
            }

            // Get student details
            $student = $this->getStudentDetails($card['student_id']);
            if (!$student) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Student not found'
                ]);
                return;
            }

            // Generate PDF
            $pdfPath = $this->generatePDF($card, $student);
            
            if ($pdfPath) {
                // Update card status to generated
                $this->identityCardModel->update($id, [
                    'status' => 'generated',
                    'generated_at' => date('Y-m-d H:i:s'),
                    'generated_by' => $this->getCurrentUserId()
                ]);

                $this->sendResponse(200, [
                    'success' => true,
                    'message' => 'Identity card generated successfully',
                    'data' => [
                        'pdf_path' => $pdfPath,
                        'download_url' => '/api/v1/identity-cards/' . $id . '/download'
                    ]
                ]);
            } else {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Failed to generate identity card PDF'
                ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Failed to generate identity card: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download generated identity card
     */
    public function download($id) {
        try {
            $card = $this->identityCardModel->getById($id);
            if (!$card || $card['status'] !== 'generated') {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Generated identity card not found'
                ]);
                return;
            }

            $pdfPath = $this->getPDFPath($id);
            if (file_exists($pdfPath)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="identity_card_' . $card['card_number'] . '.pdf"');
                readfile($pdfPath);
            } else {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'PDF file not found'
                ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Failed to download identity card: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get statistics
     */
    public function statistics() {
        try {
            $stats = $this->identityCardModel->getStatistics();
            $this->sendResponse(200, [
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Bulk operations
     */
    public function bulk() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            $cardIds = $input['card_ids'] ?? [];

            if (empty($cardIds)) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'No cards selected'
                ]);
                return;
            }

            $result = $this->identityCardModel->bulkUpdateStatus($cardIds, $action);
            
            if ($result) {
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => 'Bulk operation completed successfully'
                ]);
            } else {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Failed to perform bulk operation'
                ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Failed to perform bulk operation: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate unique card number
     */
    private function generateCardNumber() {
        $prefix = 'ID';
        $year = date('Y');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $year . $random;
    }

    /**
     * Get student details
     */
    private function getStudentDetails($studentId) {
        $stmt = $this->db->prepare("
            SELECT s.*, c.name as class_name, d.name as department_name
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN departments d ON s.department_id = d.id
            WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate PDF for identity card
     */
    private function generatePDF($card, $student) {
        // This is a simplified PDF generation
        // In a real implementation, you would use a library like TCPDF or FPDF
        
        $pdfContent = $this->createPDFContent($card, $student);
        $filename = 'identity_card_' . $card['card_number'] . '_' . time() . '.pdf';
        $filepath = __DIR__ . '/../../storage/uploads/identity_cards/' . $filename;
        
        // Ensure directory exists
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // For now, create a simple text file as placeholder
        // In production, use proper PDF generation
        file_put_contents($filepath, $pdfContent);
        
        return $filepath;
    }

    /**
     * Create PDF content (placeholder)
     */
    private function createPDFContent($card, $student) {
        return "IDENTITY CARD\n\n" .
               "Card Number: " . $card['card_number'] . "\n" .
               "Student Name: " . $student['name'] . "\n" .
               "Student ID: " . $student['student_id'] . "\n" .
               "Class: " . $student['class_name'] . "\n" .
               "Department: " . $student['department_name'] . "\n" .
               "Valid From: " . $card['valid_from'] . "\n" .
               "Valid Until: " . $card['valid_until'] . "\n" .
               "Status: " . $card['status'] . "\n";
    }

    /**
     * Get PDF file path
     */
    private function getPDFPath($cardId) {
        $card = $this->identityCardModel->getById($cardId);
        $filename = 'identity_card_' . $card['card_number'] . '_' . time() . '.pdf';
        return __DIR__ . '/../../storage/uploads/identity_cards/' . $filename;
    }

    /**
     * Get current user ID (implement based on your auth system)
     */
    private function getCurrentUserId() {
        // Implement based on your authentication system
        return $_SESSION['user_id'] ?? 1;
    }
}
?>
