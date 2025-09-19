<?php
/**
 * Identity Card PDF Generation Endpoint
 */

require_once __DIR__ . '/../../../../app/Controllers/IdentityCardController.php';
require_once __DIR__ . '/../../../../config/config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

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
    $controller->generate($cardId);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
