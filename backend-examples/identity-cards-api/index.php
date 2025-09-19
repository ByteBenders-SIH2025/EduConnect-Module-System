<?php
/**
 * Identity Cards API Endpoint
 * Handles CRUD operations for identity cards
 */

require_once __DIR__ . '/../../../../app/Controllers/IdentityCardController.php';
require_once __DIR__ . '/../../../../config/config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize controller
$controller = new IdentityCardController();

// Route the request
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($path, '/'));

try {
    switch ($method) {
        case 'GET':
            if (isset($pathParts[4]) && is_numeric($pathParts[4])) {
                // GET /api/v1/identity-cards/{id}
                $controller->show($pathParts[4]);
            } else {
                // GET /api/v1/identity-cards
                $controller->index();
            }
            break;
            
        case 'POST':
            if (isset($pathParts[5]) && $pathParts[5] === 'generate') {
                // POST /api/v1/identity-cards/{id}/generate
                $controller->generate($pathParts[4]);
            } else {
                // POST /api/v1/identity-cards
                $controller->create();
            }
            break;
            
        case 'PUT':
            if (isset($pathParts[4]) && is_numeric($pathParts[4])) {
                // PUT /api/v1/identity-cards/{id}
                $controller->update($pathParts[4]);
            } else {
                throw new Exception('Invalid request');
            }
            break;
            
        case 'DELETE':
            if (isset($pathParts[4]) && is_numeric($pathParts[4])) {
                // DELETE /api/v1/identity-cards/{id}
                $controller->delete($pathParts[4]);
            } else {
                throw new Exception('Invalid request');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
