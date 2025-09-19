<?php
/**
 * Identity Card Module Installation Script
 * Run this script to install the Identity Card module
 */

echo "🚀 Installing Identity Card Module for EduConnect...\n\n";

// Database configuration
$host = 'localhost';
$dbname = 'educonnect';
$username = 'root';
$password = '';

// Try to get database config from environment or config file
if (file_exists('backend/config/config.php')) {
    $config = include 'backend/config/config.php';
    if (isset($config['database'])) {
        $host = $config['database']['host'] ?? $host;
        $dbname = $config['database']['name'] ?? $dbname;
        $username = $config['database']['user'] ?? $username;
        $password = $config['database']['pass'] ?? $password;
    }
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";

    // Read and execute migration
    $migrationFile = 'backend/database/migrations/2025_09_19_create_identity_cards_table.sql';
    if (file_exists($migrationFile)) {
        $sql = file_get_contents($migrationFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Skip errors for existing objects
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "⚠️  Warning: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "✅ Database migration completed\n";
    } else {
        echo "❌ Migration file not found: $migrationFile\n";
    }

    // Create upload directories
    $uploadDir = 'backend/storage/uploads/identity_cards';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✅ Created upload directory: $uploadDir\n";
    } else {
        echo "✅ Upload directory already exists: $uploadDir\n";
    }

    // Set permissions
    chmod($uploadDir, 0755);
    echo "✅ Set directory permissions\n";

    // Test API endpoint
    $apiUrl = 'http://localhost/api/v1/identity-cards';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5
        ]
    ]);
    
    $result = @file_get_contents($apiUrl, false, $context);
    if ($result !== false) {
        echo "✅ API endpoint is accessible\n";
    } else {
        echo "⚠️  API endpoint test failed (this is normal if web server is not running)\n";
    }

    // Check if module is registered
    $registryFile = 'frontend/public/modules/registry.json';
    if (file_exists($registryFile)) {
        $registry = json_decode(file_get_contents($registryFile), true);
        $identityCardModule = null;
        
        foreach ($registry['modules'] as $module) {
            if ($module['key'] === 'identity-cards') {
                $identityCardModule = $module;
                break;
            }
        }
        
        if ($identityCardModule) {
            echo "✅ Module is registered in registry.json\n";
        } else {
            echo "❌ Module not found in registry.json\n";
        }
    }

    // Check module files
    $moduleFiles = [
        'frontend/public/modules/Module-Identity_Card/manifest.json',
        'frontend/public/modules/Module-Identity_Card/index.html',
        'frontend/public/modules/Module-Identity_Card/src/assets/css/identity.css',
        'frontend/public/modules/Module-Identity_Card/src/assets/js/identity.js',
        'backend/app/Controllers/IdentityCardController.php',
        'backend/app/Models/IdentityCard.php'
    ];

    $allFilesExist = true;
    foreach ($moduleFiles as $file) {
        if (file_exists($file)) {
            echo "✅ $file\n";
        } else {
            echo "❌ Missing: $file\n";
            $allFilesExist = false;
        }
    }

    if ($allFilesExist) {
        echo "\n🎉 Identity Card Module installation completed successfully!\n\n";
        echo "📋 Next Steps:\n";
        echo "1. Start your web server (Apache/Nginx)\n";
        echo "2. Access your EduConnect admin panel\n";
        echo "3. Click on 'Identity Cards' in the sidebar\n";
        echo "4. Start creating identity cards!\n\n";
        echo "🔗 Module URL: http://localhost/modules/identity-cards\n";
        echo "🔗 API URL: http://localhost/api/v1/identity-cards\n\n";
    } else {
        echo "\n❌ Installation incomplete. Please ensure all module files are present.\n";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and ensure the database exists.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n📚 Documentation: frontend/public/modules/Module-Identity_Card/README.md\n";
echo "🔧 Configuration: frontend/public/modules/Module-Identity_Card/config.php\n";
?>
