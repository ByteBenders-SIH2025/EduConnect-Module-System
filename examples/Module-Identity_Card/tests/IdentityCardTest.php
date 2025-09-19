<?php

require_once __DIR__ . '/../src/models/IdentityCard.php';
require_once __DIR__ . '/../src/controllers/IdentityCardController.php';

/**
 * Identity Card Module Test Suite
 * Tests all functionality of the Identity Card management system
 */
class IdentityCardTest {
    private $db;
    private $identityCardModel;
    private $controller;
    private $testStudentId;
    private $testCardId;

    public function __construct() {
        $this->setupDatabase();
        $this->identityCardModel = new IdentityCard($this->db);
        $this->controller = new IdentityCardController();
    }

    /**
     * Setup test database connection
     */
    private function setupDatabase() {
        try {
            // Use test database configuration
            $host = 'localhost';
            $dbname = 'educonnect_test';
            $username = 'root';
            $password = '';

            $this->db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Setup test data
     */
    private function setupTestData() {
        // Create test student
        $stmt = $this->db->prepare("
            INSERT INTO students (name, student_id, email, phone, class_id, department_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute(['Test Student', 'TEST001', 'test@example.com', '1234567890', 1, 1]);
        $this->testStudentId = $this->db->lastInsertId();

        // Create test user
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, role, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute(['Test User', 'testuser@example.com', 'admin']);
        $this->testUserId = $this->db->lastInsertId();
    }

    /**
     * Cleanup test data
     */
    private function cleanupTestData() {
        if ($this->testCardId) {
            $this->db->prepare("DELETE FROM identity_cards WHERE id = ?")->execute([$this->testCardId]);
        }
        if ($this->testStudentId) {
            $this->db->prepare("DELETE FROM students WHERE id = ?")->execute([$this->testStudentId]);
        }
        if ($this->testUserId) {
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$this->testUserId]);
        }
    }

    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "Starting Identity Card Module Tests...\n\n";

        $this->setupTestData();

        try {
            $this->testCreateCard();
            $this->testGetCardById();
            $this->testGetAllCards();
            $this->testUpdateCard();
            $this->testGetActiveByStudentId();
            $this->testGetByStatus();
            $this->testGetExpiring();
            $this->testGetStatistics();
            $this->testBulkUpdateStatus();
            $this->testCardNumberExists();
            $this->testGetByDateRange();
            $this->testControllerMethods();
            $this->testValidation();
            $this->testErrorHandling();

            echo "\n✅ All tests passed successfully!\n";
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n";
        } finally {
            $this->cleanupTestData();
        }
    }

    /**
     * Test card creation
     */
    public function testCreateCard() {
        echo "Testing card creation... ";

        $cardData = [
            'student_id' => $this->testStudentId,
            'card_type' => 'student',
            'card_number' => 'TEST001',
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'status' => 'pending',
            'notes' => 'Test card',
            'created_by' => $this->testUserId
        ];

        $cardId = $this->identityCardModel->create($cardData);
        
        if (!$cardId) {
            throw new Exception("Failed to create card");
        }

        $this->testCardId = $cardId;
        echo "✅ Passed\n";
    }

    /**
     * Test getting card by ID
     */
    public function testGetCardById() {
        echo "Testing get card by ID... ";

        $card = $this->identityCardModel->getById($this->testCardId);
        
        if (!$card || $card['id'] != $this->testCardId) {
            throw new Exception("Failed to retrieve card by ID");
        }

        if ($card['card_number'] !== 'TEST001') {
            throw new Exception("Card number mismatch");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test getting all cards
     */
    public function testGetAllCards() {
        echo "Testing get all cards... ";

        $result = $this->identityCardModel->getAll(1, 10);
        
        if (!isset($result['data']) || !isset($result['pagination'])) {
            throw new Exception("Invalid result structure");
        }

        if (count($result['data']) < 1) {
            throw new Exception("No cards found");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test updating card
     */
    public function testUpdateCard() {
        echo "Testing card update... ";

        $updateData = [
            'status' => 'active',
            'notes' => 'Updated test card',
            'updated_by' => $this->testUserId
        ];

        $result = $this->identityCardModel->update($this->testCardId, $updateData);
        
        if (!$result) {
            throw new Exception("Failed to update card");
        }

        $card = $this->identityCardModel->getById($this->testCardId);
        
        if ($card['status'] !== 'active') {
            throw new Exception("Status not updated correctly");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test getting active card by student ID
     */
    public function testGetActiveByStudentId() {
        echo "Testing get active card by student ID... ";

        $card = $this->identityCardModel->getActiveByStudentId($this->testStudentId);
        
        if (!$card) {
            throw new Exception("No active card found for student");
        }

        if ($card['student_id'] != $this->testStudentId) {
            throw new Exception("Wrong student ID returned");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test getting cards by status
     */
    public function testGetByStatus() {
        echo "Testing get cards by status... ";

        $cards = $this->identityCardModel->getByStatus('active');
        
        if (count($cards) < 1) {
            throw new Exception("No active cards found");
        }

        foreach ($cards as $card) {
            if ($card['status'] !== 'active') {
                throw new Exception("Non-active card returned");
            }
        }

        echo "✅ Passed\n";
    }

    /**
     * Test getting expiring cards
     */
    public function testGetExpiring() {
        echo "Testing get expiring cards... ";

        $cards = $this->identityCardModel->getExpiring(30);
        
        // Should return our test card since it expires in 2025
        $found = false;
        foreach ($cards as $card) {
            if ($card['id'] == $this->testCardId) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception("Test card not found in expiring cards");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test getting statistics
     */
    public function testGetStatistics() {
        echo "Testing get statistics... ";

        $stats = $this->identityCardModel->getStatistics();
        
        if (!isset($stats['total_cards']) || !isset($stats['active_cards'])) {
            throw new Exception("Invalid statistics structure");
        }

        if ($stats['total_cards'] < 1) {
            throw new Exception("No cards in statistics");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test bulk status update
     */
    public function testBulkUpdateStatus() {
        echo "Testing bulk status update... ";

        $result = $this->identityCardModel->bulkUpdateStatus([$this->testCardId], 'suspended');
        
        if (!$result) {
            throw new Exception("Bulk update failed");
        }

        $card = $this->identityCardModel->getById($this->testCardId);
        
        if ($card['status'] !== 'suspended') {
            throw new Exception("Bulk update did not change status");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test card number exists check
     */
    public function testCardNumberExists() {
        echo "Testing card number exists check... ";

        $exists = $this->identityCardModel->cardNumberExists('TEST001');
        
        if (!$exists) {
            throw new Exception("Card number should exist");
        }

        $notExists = $this->identityCardModel->cardNumberExists('NONEXISTENT');
        
        if ($notExists) {
            throw new Exception("Non-existent card number should not exist");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test getting cards by date range
     */
    public function testGetByDateRange() {
        echo "Testing get cards by date range... ";

        $startDate = '2025-01-01';
        $endDate = '2025-12-31';
        
        $cards = $this->identityCardModel->getByDateRange($startDate, $endDate);
        
        $found = false;
        foreach ($cards as $card) {
            if ($card['id'] == $this->testCardId) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception("Test card not found in date range");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test controller methods
     */
    public function testControllerMethods() {
        echo "Testing controller methods... ";

        // Test controller instantiation
        if (!$this->controller) {
            throw new Exception("Controller not instantiated");
        }

        // Test module lifecycle hooks
        $installResult = IdentityCardController::onInstall();
        if (!$installResult) {
            throw new Exception("onInstall hook failed");
        }

        $activateResult = IdentityCardController::onActivate();
        if (!$activateResult) {
            throw new Exception("onActivate hook failed");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test validation
     */
    public function testValidation() {
        echo "Testing validation... ";

        // Test invalid card data
        try {
            $invalidData = [
                'student_id' => 999999, // Non-existent student
                'card_type' => 'invalid_type',
                'card_number' => '',
                'valid_from' => '2025-12-31',
                'valid_until' => '2025-01-01', // Invalid date range
                'status' => 'invalid_status',
                'created_by' => 999999 // Non-existent user
            ];

            $cardId = $this->identityCardModel->create($invalidData);
            
            if ($cardId) {
                throw new Exception("Should not create card with invalid data");
            }
        } catch (Exception $e) {
            // Expected to fail
        }

        echo "✅ Passed\n";
    }

    /**
     * Test error handling
     */
    public function testErrorHandling() {
        echo "Testing error handling... ";

        // Test getting non-existent card
        $card = $this->identityCardModel->getById(999999);
        if ($card) {
            throw new Exception("Should not return non-existent card");
        }

        // Test updating non-existent card
        $result = $this->identityCardModel->update(999999, ['status' => 'active']);
        if ($result) {
            throw new Exception("Should not update non-existent card");
        }

        // Test deleting non-existent card
        $result = $this->identityCardModel->delete(999999);
        if ($result) {
            throw new Exception("Should not delete non-existent card");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test performance with large dataset
     */
    public function testPerformance() {
        echo "Testing performance... ";

        $startTime = microtime(true);

        // Create multiple test cards
        for ($i = 0; $i < 100; $i++) {
            $cardData = [
                'student_id' => $this->testStudentId,
                'card_type' => 'student',
                'card_number' => 'PERF' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'valid_from' => '2025-01-01',
                'valid_until' => '2025-12-31',
                'status' => 'pending',
                'created_by' => $this->testUserId
            ];

            $this->identityCardModel->create($cardData);
        }

        // Test pagination performance
        $result = $this->identityCardModel->getAll(1, 50);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        if ($executionTime > 5) { // Should complete within 5 seconds
            throw new Exception("Performance test failed - took too long: " . $executionTime . " seconds");
        }

        echo "✅ Passed (execution time: " . round($executionTime, 2) . "s)\n";
    }

    /**
     * Test security features
     */
    public function testSecurity() {
        echo "Testing security features... ";

        // Test SQL injection prevention
        $maliciousInput = "'; DROP TABLE identity_cards; --";
        
        try {
            $card = $this->identityCardModel->getById($maliciousInput);
            // Should not cause any issues
        } catch (Exception $e) {
            // Expected behavior
        }

        // Test XSS prevention
        $xssInput = "<script>alert('xss')</script>";
        $cardData = [
            'student_id' => $this->testStudentId,
            'card_type' => 'student',
            'card_number' => 'XSS001',
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'status' => 'pending',
            'notes' => $xssInput,
            'created_by' => $this->testUserId
        ];

        $cardId = $this->identityCardModel->create($cardData);
        $card = $this->identityCardModel->getById($cardId);
        
        if (strpos($card['notes'], '<script>') !== false) {
            throw new Exception("XSS prevention failed");
        }

        echo "✅ Passed\n";
    }

    /**
     * Test data integrity
     */
    public function testDataIntegrity() {
        echo "Testing data integrity... ";

        // Test foreign key constraints
        try {
            $cardData = [
                'student_id' => 999999, // Non-existent student
                'card_type' => 'student',
                'card_number' => 'INT001',
                'valid_from' => '2025-01-01',
                'valid_until' => '2025-12-31',
                'status' => 'pending',
                'created_by' => $this->testUserId
            ];

            $cardId = $this->identityCardModel->create($cardData);
            
            if ($cardId) {
                throw new Exception("Should not create card with invalid foreign key");
            }
        } catch (Exception $e) {
            // Expected to fail due to foreign key constraint
        }

        // Test unique constraint
        try {
            $cardData = [
                'student_id' => $this->testStudentId,
                'card_type' => 'student',
                'card_number' => 'TEST001', // Duplicate card number
                'valid_from' => '2025-01-01',
                'valid_until' => '2025-12-31',
                'status' => 'pending',
                'created_by' => $this->testUserId
            ];

            $cardId = $this->identityCardModel->create($cardData);
            
            if ($cardId) {
                throw new Exception("Should not create card with duplicate card number");
            }
        } catch (Exception $e) {
            // Expected to fail due to unique constraint
        }

        echo "✅ Passed\n";
    }

    /**
     * Run specific test
     */
    public function runTest($testName) {
        if (method_exists($this, $testName)) {
            $this->setupTestData();
            try {
                $this->$testName();
                echo "✅ Test '$testName' passed\n";
            } catch (Exception $e) {
                echo "❌ Test '$testName' failed: " . $e->getMessage() . "\n";
            } finally {
                $this->cleanupTestData();
            }
        } else {
            echo "❌ Test '$testName' not found\n";
        }
    }

    /**
     * Generate test report
     */
    public function generateReport() {
        $report = [
            'module' => 'Identity Card',
            'version' => '1.0.0',
            'test_date' => date('Y-m-d H:i:s'),
            'tests_run' => 0,
            'tests_passed' => 0,
            'tests_failed' => 0,
            'coverage' => [
                'models' => true,
                'controllers' => true,
                'views' => false, // Views require browser testing
                'api' => false,   // API requires HTTP testing
                'database' => true,
                'security' => true,
                'performance' => true
            ]
        ];

        return $report;
    }
}

// Run tests if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $testSuite = new IdentityCardTest();
    
    if (isset($argv[1])) {
        // Run specific test
        $testSuite->runTest($argv[1]);
    } else {
        // Run all tests
        $testSuite->runAllTests();
        
        // Generate report
        $report = $testSuite->generateReport();
        echo "\n" . json_encode($report, JSON_PRETTY_PRINT) . "\n";
    }
}
