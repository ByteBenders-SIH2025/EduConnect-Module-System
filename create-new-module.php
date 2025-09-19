<?php
/**
 * Module Creation Script
 * This script helps you create a new complex module for EduConnect
 */

echo "🏗️  EduConnect Module Creation Tool\n";
echo "=====================================\n\n";

// Get module information from user
function getInput($prompt) {
    echo $prompt;
    return trim(fgets(STDIN));
}

function createDirectory($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✅ Created directory: $path\n";
    } else {
        echo "⚠️  Directory already exists: $path\n";
    }
}

function copyTemplateFile($source, $destination, $replacements = []) {
    if (file_exists($source)) {
        $content = file_get_contents($source);
        
        // Apply replacements
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        // Create directory if it doesn't exist
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($destination, $content);
        echo "✅ Created file: $destination\n";
    } else {
        echo "❌ Template file not found: $source\n";
    }
}

// Get module information
$moduleName = getInput("Enter module name (e.g., 'Student Records'): ");
$moduleKey = getInput("Enter module key (e.g., 'student-records'): ");
$moduleDescription = getInput("Enter module description: ");
$authorName = getInput("Enter your name: ");

// Convert module name to various formats
$moduleNameLower = strtolower(str_replace(' ', '-', $moduleKey));
$moduleNameCamel = str_replace(' ', '', ucwords(str_replace('-', ' ', $moduleKey)));
$moduleNamePascal = ucfirst($moduleNameCamel);
$moduleNameSnake = str_replace('-', '_', $moduleKey);

echo "\n📁 Creating module structure...\n";

// Create frontend module directory
$frontendModulePath = "frontend/public/modules/Module-{$moduleNamePascal}";
createDirectory($frontendModulePath);
createDirectory("$frontendModulePath/src/controllers");
createDirectory("$frontendModulePath/src/models");
createDirectory("$frontendModulePath/src/views");
createDirectory("$frontendModulePath/src/assets/css");
createDirectory("$frontendModulePath/src/assets/js");
createDirectory("$frontendModulePath/migrations");
createDirectory("$frontendModulePath/tests");

// Create backend directories
$backendPath = "backend";
createDirectory("$backendPath/app/Controllers");
createDirectory("$backendPath/app/Models");
createDirectory("$backendPath/public/api/v1/$moduleNameLower");
createDirectory("$backendPath/database/migrations");

echo "\n📄 Creating module files...\n";

// Replacements for template files
$replacements = [
    'Template' => $moduleNamePascal,
    'template' => $moduleNameLower,
    'Template Module' => $moduleName,
    'template-module' => $moduleKey,
    'template_items' => $moduleNameSnake . '_items',
    'TemplateController' => $moduleNamePascal . 'Controller',
    'Template.php' => $moduleNamePascal . '.php',
    'template.js' => $moduleNameLower . '.js',
    'template.css' => $moduleNameLower . '.css',
    'Your Name' => $authorName,
    'A template module for creating new EduConnect modules' => $moduleDescription,
    'fas fa-cube' => 'fas fa-cog', // Default icon, user can change
    '2025_09_19' => date('Y_m_d')
];

// Copy frontend files
copyTemplateFile(
    'module-template/Module-Template/manifest.json',
    "$frontendModulePath/manifest.json",
    $replacements
);

copyTemplateFile(
    'module-template/Module-Template/index.html',
    "$frontendModulePath/index.html",
    $replacements
);

copyTemplateFile(
    'module-template/Module-Template/src/assets/css/template.css',
    "$frontendModulePath/src/assets/css/$moduleNameLower.css",
    $replacements
);

copyTemplateFile(
    'module-template/Module-Template/src/assets/js/template.js',
    "$frontendModulePath/src/assets/js/$moduleNameLower.js",
    $replacements
);

// Copy backend files
copyTemplateFile(
    'module-template/backend/app/Controllers/TemplateController.php',
    "$backendPath/app/Controllers/{$moduleNamePascal}Controller.php",
    $replacements
);

copyTemplateFile(
    'module-template/backend/app/Models/Template.php',
    "$backendPath/app/Models/{$moduleNamePascal}.php",
    $replacements
);

copyTemplateFile(
    'module-template/backend/database/migrations/2025_09_19_create_template_items_table.sql',
    "$backendPath/database/migrations/" . date('Y_m_d') . "_create_{$moduleNameSnake}_items_table.sql",
    $replacements
);

// Create API endpoint
$apiEndpointContent = "<?php
require_once __DIR__ . '/../../../../app/Controllers/{$moduleNamePascal}Controller.php';
require_once __DIR__ . '/../../../../app/Models/{$moduleNamePascal}.php';
require_once __DIR__ . '/../../../../database/connection.php';

header('Content-Type: application/json');

\$controller = new {$moduleNamePascal}Controller();
\$method = \$_SERVER['REQUEST_METHOD'];
\$request_uri = explode('/', trim(\$_SERVER['REQUEST_URI'], '/'));
\$api_path_index = array_search('api', \$request_uri);
\$resource_id = null;

if (\$api_path_index !== false && isset(\$request_uri[\$api_path_index + 3])) {
    \$resource_id = \$request_uri[\$api_path_index + 3];
}

switch (\$method) {
    case 'GET':
        if (\$resource_id) {
            echo \$controller->show(\$resource_id);
        } else {
            echo \$controller->index();
        }
        break;
    case 'POST':
        echo \$controller->store();
        break;
    case 'PUT':
        if (\$resource_id) {
            echo \$controller->update(\$resource_id);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'ID required for update']);
        }
        break;
    case 'DELETE':
        if (\$resource_id) {
            echo \$controller->destroy(\$resource_id);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'ID required for delete']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}
?>";

file_put_contents("$backendPath/public/api/v1/$moduleNameLower/index.php", $apiEndpointContent);
echo "✅ Created API endpoint: $backendPath/public/api/v1/$moduleNameLower/index.php\n";

// Create README
$readmeContent = "# {$moduleName} Module

## Description
{$moduleDescription}

## Installation

1. Run the database migration:
   ```sql
   -- Run the migration file
   source backend/database/migrations/" . date('Y_m_d') . "_create_{$moduleNameSnake}_items_table.sql
   ```

2. Register the module in the registry:
   Add this entry to `frontend/public/modules/registry.json`:
   ```json
   {
     \"key\": \"{$moduleKey}\",
     \"manifest\": \"/modules/Module-{$moduleNamePascal}/manifest.json\"
   }
   ```

3. Add sidebar link in `frontend/public/components/sidebar.html`:
   ```html
   <li class=\"nav-item\" data-module=\"{$moduleKey}\">
       <a href=\"#\" class=\"nav-link\" data-module-link=\"{$moduleKey}\">
           <i class=\"fas fa-cog\"></i>
           <span>{$moduleName}</span>
       </a>
   </li>
   ```

## API Endpoints

- GET `/api/v1/{$moduleNameLower}` - List all items
- POST `/api/v1/{$moduleNameLower}` - Create new item
- GET `/api/v1/{$moduleNameLower}/{id}` - Get specific item
- PUT `/api/v1/{$moduleNameLower}/{id}` - Update item
- DELETE `/api/v1/{$moduleNameLower}/{id}` - Delete item

## Database

The module uses the `{$moduleNameSnake}_items` table with the following structure:
- id (Primary Key)
- name (VARCHAR)
- description (TEXT)
- status (ENUM: active, inactive, pending)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

## Customization

1. **Update the manifest.json** with your specific configuration
2. **Modify the CSS** in `src/assets/css/{$moduleNameLower}.css`
3. **Customize the JavaScript** in `src/assets/js/{$moduleNameLower}.js`
4. **Update the database schema** in the migration file
5. **Modify the controller and model** for your specific business logic

## Author
{$authorName}

## License
MIT
";

file_put_contents("$frontendModulePath/README.md", $readmeContent);
echo "✅ Created README: $frontendModulePath/README.md\n";

echo "\n🎉 Module '{$moduleName}' created successfully!\n\n";

echo "📋 Next Steps:\n";
echo "1. Run the database migration\n";
echo "2. Add the module to registry.json\n";
echo "3. Add sidebar link\n";
echo "4. Customize the module for your needs\n";
echo "5. Test the module\n\n";

echo "📁 Module Location: $frontendModulePath\n";
echo "🔗 API Endpoint: /api/v1/$moduleNameLower\n";
echo "📚 Documentation: $frontendModulePath/README.md\n\n";

echo "Happy coding! 🚀\n";
?>
