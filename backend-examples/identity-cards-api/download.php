<?php
/**
 * Identity Card PDF Download Endpoint
 */

require_once __DIR__ . '/../../../../app/Controllers/IdentityCardController.php';
require_once __DIR__ . '/../../../../config/config.php';

// Get card ID from URL
$path = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($path, '/'));
$cardId = isset($pathParts[4]) ? $pathParts[4] : null;

if (!$cardId || !is_numeric($cardId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid card ID']);
    exit();
}

try {
    $controller = new IdentityCardController();
    $controller->download($cardId);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
