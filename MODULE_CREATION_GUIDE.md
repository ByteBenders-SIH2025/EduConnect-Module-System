# 🏗️ EduConnect Complex Module Creation Guide

## Overview
This guide shows you exactly how to create a new complex module for EduConnect using the established architecture.

## 📁 Required File Structure

When creating a new module, you need to create this exact structure:

```
Module-YourModuleName/
├── manifest.json                    # Module configuration
├── index.html                       # Main module interface
├── src/
│   ├── controllers/                 # Business logic
│   │   └── YourModuleController.php
│   ├── models/                      # Data layer
│   │   └── YourModule.php
│   ├── views/                       # UI components
│   │   ├── list.php
│   │   ├── create.php
│   │   └── view.php
│   └── assets/
│       ├── css/
│       │   └── your-module.css
│       └── js/
│           └── your-module.js
├── migrations/                      # Database schema
│   └── YYYY_MM_DD_create_your_table.sql
├── tests/                          # Test suite
│   └── YourModuleTest.php
└── README.md                       # Documentation
```

## 🔧 Backend API Structure

You also need to create these backend files:

```
backend/
├── app/Controllers/
│   └── YourModuleController.php    # Main controller
├── app/Models/
│   └── YourModule.php              # Data model
├── public/api/v1/your-module/
│   ├── index.php                   # Main API endpoint
│   ├── create.php                  # Create endpoint
│   └── download.php                # Download endpoint (if needed)
└── database/migrations/
    └── YYYY_MM_DD_create_your_table.sql
```

## 📝 Step-by-Step Creation Process

### Step 1: Create Module Directory
```bash
mkdir -p frontend/public/modules/Module-YourModuleName/src/{controllers,models,views,assets/{css,js}}
mkdir -p frontend/public/modules/Module-YourModuleName/{migrations,tests}
```

### Step 2: Create manifest.json
```json
{
  "key": "your-module-name",
  "name": "Your Module Name",
  "version": "1.0.0",
  "type": "complex",
  "entry": "/modules/Module-YourModuleName/index.html",
  "scripts": [
    "/modules/Module-YourModuleName/src/assets/js/your-module.js"
  ],
  "styles": [
    "/modules/Module-YourModuleName/src/assets/css/your-module.css"
  ],
  "description": "Description of your module",
  "api_endpoints": {
    "base": "/api/v1/your-module",
    "list": "GET /api/v1/your-module",
    "create": "POST /api/v1/your-module",
    "view": "GET /api/v1/your-module/{id}",
    "update": "PUT /api/v1/your-module/{id}",
    "delete": "DELETE /api/v1/your-module/{id}"
  }
}
```

### Step 3: Create Backend Controller
```php
<?php
// backend/app/Controllers/YourModuleController.php
class YourModuleController extends BaseController {
    private $yourModuleModel;
    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = require_once __DIR__ . '/../../database/connection.php';
        $this->yourModuleModel = new YourModule($this->db);
    }

    public function index() {
        // List all items
    }

    public function create() {
        // Create new item
    }

    public function show($id) {
        // Show specific item
    }

    public function update($id) {
        // Update item
    }

    public function delete($id) {
        // Delete item
    }
}
?>
```

### Step 4: Create Backend Model
```php
<?php
// backend/app/Models/YourModule.php
class YourModule {
    private $db;
    private $table = 'your_table_name';

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAll($page = 1, $limit = 10, $search = '') {
        // Get all items with pagination
    }

    public function getById($id) {
        // Get item by ID
    }

    public function create($data) {
        // Create new item
    }

    public function update($id, $data) {
        // Update item
    }

    public function delete($id) {
        // Delete item
    }
}
?>
```

### Step 5: Create API Endpoints
```php
<?php
// backend/public/api/v1/your-module/index.php
require_once __DIR__ . '/../../../../app/Controllers/YourModuleController.php';

header('Content-Type: application/json');
$controller = new YourModuleController();

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

switch ($method) {
    case 'GET':
        $controller->index();
        break;
    case 'POST':
        $controller->create();
        break;
    // Add other methods
}
?>
```

### Step 6: Create Frontend Interface
```html
<!-- frontend/public/modules/Module-YourModuleName/index.html -->
<div class="module-your-module">
    <div class="module-header">
        <h1><i class="fas fa-icon"></i> Your Module Name</h1>
        <p>Description of your module</p>
    </div>
    
    <div class="module-content">
        <!-- Your module content here -->
    </div>
</div>
```

### Step 7: Create CSS Styles
```css
/* frontend/public/modules/Module-YourModuleName/src/assets/css/your-module.css */
.module-your-module {
    padding: 20px;
    background: #f8fafc;
    min-height: 100vh;
}

.module-header {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Add your styles here */
```

### Step 8: Create JavaScript
```javascript
// frontend/public/modules/Module-YourModuleName/src/assets/js/your-module.js
class YourModuleManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadData();
    }

    bindEvents() {
        // Bind your event handlers
    }

    async loadData() {
        // Load data from API
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new YourModuleManager();
});
```

### Step 9: Create Database Migration
```sql
-- backend/database/migrations/YYYY_MM_DD_create_your_table.sql
CREATE TABLE IF NOT EXISTS `your_table_name` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Step 10: Register Module
Add your module to `frontend/public/modules/registry.json`:
```json
{
  "modules": [
    { "key": "clubs", "manifest": "/modules/clubs/manifest.json" },
    { "key": "hr", "manifest": "/modules/sample-hr/manifest.json" },
    { "key": "identity-cards", "manifest": "/modules/Module-Identity_Card/manifest.json" },
    { "key": "your-module-name", "manifest": "/modules/Module-YourModuleName/manifest.json" }
  ]
}
```

### Step 11: Add Sidebar Link
Add to `frontend/public/components/sidebar.html`:
```html
<li class="nav-item" data-module="your-module-name">
    <a href="#" class="nav-link" data-module-link="your-module-name">
        <i class="fas fa-your-icon"></i>
        <span>Your Module Name</span>
    </a>
</li>
```

## 🚀 Quick Start Template

I'll create a complete template module for you to copy and modify.
