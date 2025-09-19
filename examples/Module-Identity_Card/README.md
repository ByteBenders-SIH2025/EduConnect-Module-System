# Identity Card Management Module

A comprehensive identity card management system for the EduConnect platform, designed to handle student and staff identity card creation, generation, and management.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Reference](#api-reference)
- [Database Schema](#database-schema)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

The Identity Card Management Module provides a complete solution for managing identity cards within an educational institution. It supports multiple card types, automated generation, PDF creation, and comprehensive tracking of card lifecycle.

### Key Components

- **Controller**: Handles HTTP requests and business logic
- **Model**: Manages database interactions and data validation
- **Views**: User interface for card management and generation
- **Assets**: CSS and JavaScript for enhanced user experience
- **Migrations**: Database schema and setup scripts
- **Tests**: Comprehensive test suite for quality assurance

## ✨ Features

### Core Functionality
- ✅ **Card Creation**: Create identity cards for students, staff, and visitors
- ✅ **Card Generation**: Generate PDF identity cards with customizable templates
- ✅ **Status Management**: Track card status (pending, active, generated, expired, etc.)
- ✅ **Validity Tracking**: Monitor card validity periods and expiration dates
- ✅ **Bulk Operations**: Perform bulk actions on multiple cards
- ✅ **Search & Filter**: Advanced search and filtering capabilities

### Advanced Features
- 🎨 **Customizable Templates**: Multiple card design templates
- 📱 **Responsive Design**: Mobile-friendly interface
- 🔍 **Advanced Search**: Search by card number, student name, or ID
- 📊 **Statistics Dashboard**: Comprehensive analytics and reporting
- 🔔 **Expiration Alerts**: Automatic notifications for expiring cards
- 📄 **PDF Generation**: High-quality PDF card generation
- 🖨️ **Print Support**: Direct printing capabilities
- 🔄 **Card Renewal**: Easy card renewal process
- 📋 **Activity Logging**: Complete audit trail of all card activities

### Security Features
- 🔒 **Access Control**: Role-based permissions
- 🛡️ **Data Validation**: Comprehensive input validation
- 🔐 **SQL Injection Prevention**: Secure database queries
- 🚫 **XSS Protection**: Cross-site scripting prevention
- 📝 **Audit Trail**: Complete activity logging

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- EduConnect platform installed
- Required PHP extensions: PDO, PDO_MySQL, JSON

### Installation Steps

1. **Copy Module Files**
   ```bash
   cp -r Module-Identity_Card/ /path/to/educonnect/frontend/public/modules/
   ```

2. **Run Database Migration**
   ```bash
   mysql -u username -p database_name < migrations/2025_09_19_create_identity_cards_table.sql
   ```

3. **Set Permissions**
   ```bash
   chmod -R 755 Module-Identity_Card/
   chmod -R 777 Module-Identity_Card/src/assets/
   ```

4. **Install Dependencies**
   ```bash
   # Install any required PHP libraries
   composer install
   ```

5. **Configure Module**
   - Update `module.json` with your institution details
   - Configure database connection settings
   - Set up file upload directories

## ⚙️ Configuration

### Module Configuration

Edit `module.json` to customize the module:

```json
{
  "name": "Identity Card Manager",
  "version": "1.0.0",
  "settings": {
    "card_template": "default",
    "auto_generate": false,
    "require_approval": true,
    "validity_period": 365
  }
}
```

### Database Configuration

Ensure your database connection is properly configured in the main EduConnect configuration.

### File Upload Configuration

Create the following directories and set appropriate permissions:

```bash
mkdir -p backend/storage/uploads/identity_cards
chmod 777 backend/storage/uploads/identity_cards
```

## 📖 Usage

### Creating Identity Cards

1. **Access the Module**
   - Navigate to the Identity Cards section in the admin panel
   - Click "Create New Card"

2. **Fill Card Information**
   - Select student from dropdown
   - Choose card type (Student/Staff/Visitor)
   - Set validity period
   - Add any notes

3. **Save Card**
   - Click "Save Card" to create the identity card
   - Card will be created with "pending" status

### Generating PDF Cards

1. **Select Card**
   - Find the card in the list view
   - Click "Generate PDF" button

2. **Customize Template**
   - Choose from available templates
   - Adjust colors and layout
   - Preview the card

3. **Generate & Download**
   - Click "Generate PDF"
   - Download the generated card

### Managing Card Status

- **Pending**: Newly created cards awaiting approval
- **Active**: Approved cards ready for use
- **Generated**: Cards with PDF generated
- **Expired**: Cards past their validity date
- **Suspended**: Temporarily disabled cards
- **Cancelled**: Permanently cancelled cards

## 🔌 API Reference

### Endpoints

#### List Identity Cards
```http
GET /api/v1/identity-cards
```

**Parameters:**
- `page` (int): Page number for pagination
- `limit` (int): Number of items per page
- `search` (string): Search term
- `status` (string): Filter by status
- `card_type` (string): Filter by card type

**Response:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 100,
    "total_pages": 10
  }
}
```

#### Create Identity Card
```http
POST /api/v1/identity-cards
```

**Request Body:**
```json
{
  "student_id": 1,
  "card_type": "student",
  "valid_from": "2025-01-01",
  "valid_until": "2025-12-31",
  "status": "pending",
  "notes": "Optional notes"
}
```

#### Update Identity Card
```http
PUT /api/v1/identity-cards/{id}
```

#### Delete Identity Card
```http
DELETE /api/v1/identity-cards/{id}
```

#### Generate PDF
```http
POST /api/v1/identity-cards/{id}/generate
```

#### Download Generated Card
```http
GET /api/v1/identity-cards/{id}/download
```

### Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "code": "ERROR_CODE"
}
```

## 🗄️ Database Schema

### Main Tables

#### `identity_cards`
Primary table storing identity card information.

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) | Primary key |
| student_id | int(11) | Reference to students table |
| card_type | enum | Type of card (student/staff/visitor) |
| card_number | varchar(50) | Unique card number |
| valid_from | date | Validity start date |
| valid_until | date | Validity end date |
| status | enum | Current card status |
| notes | text | Additional notes |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

#### `identity_card_status_log`
Logs all status changes for audit purposes.

#### `identity_card_activity_log`
Comprehensive activity logging for all card operations.

### Views

- `active_identity_cards`: View of all active cards with student information
- `expiring_identity_cards`: Cards expiring within 30 days
- `identity_card_statistics`: Statistical data for reporting

### Stored Procedures

- `sp_generate_card_number`: Generates unique card numbers
- `sp_bulk_update_card_status`: Updates multiple cards at once

### Functions

- `fn_student_has_active_card`: Checks if student has active card
- `fn_days_until_expiry`: Calculates days until card expiry

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php tests/IdentityCardTest.php

# Run specific test
php tests/IdentityCardTest.php testCreateCard
```

### Test Coverage

The test suite covers:

- ✅ Model functionality
- ✅ Controller methods
- ✅ Database operations
- ✅ Validation logic
- ✅ Error handling
- ✅ Security features
- ✅ Performance testing
- ✅ Data integrity

### Test Database Setup

1. Create test database:
   ```sql
   CREATE DATABASE educonnect_test;
   ```

2. Import test schema:
   ```bash
   mysql -u username -p educonnect_test < migrations/2025_09_19_create_identity_cards_table.sql
   ```

3. Update test configuration in `IdentityCardTest.php`

## 🎨 Customization

### Adding New Card Types

1. Update the `card_type` enum in the database migration
2. Add new type to the controller validation
3. Update the frontend dropdown options
4. Add specific styling for the new type

### Custom Templates

1. Create new template files in `src/templates/`
2. Update the template selection in the generation view
3. Add template-specific CSS styles
4. Update the PDF generation logic

### Custom Fields

The module supports custom fields through the `custom_fields` JSON column:

```json
{
  "emergency_contact": "John Doe",
  "blood_type": "O+",
  "allergies": "None"
}
```

## 🔧 Troubleshooting

### Common Issues

#### Database Connection Errors
- Verify database credentials
- Check if required tables exist
- Ensure proper permissions

#### PDF Generation Fails
- Check file upload directory permissions
- Verify PDF library installation
- Check available disk space

#### Card Number Conflicts
- Ensure unique constraint is working
- Check for duplicate entries
- Verify card number generation logic

### Debug Mode

Enable debug mode by setting:

```php
define('DEBUG_MODE', true);
```

This will provide detailed error messages and logging.

## 📈 Performance Optimization

### Database Optimization

- Use appropriate indexes for frequently queried columns
- Implement query caching for statistics
- Use pagination for large datasets

### File System Optimization

- Implement file cleanup for old generated cards
- Use CDN for static assets
- Optimize image sizes

### Caching

- Cache frequently accessed data
- Implement Redis for session storage
- Use browser caching for static assets

## 🔒 Security Considerations

### Data Protection

- Encrypt sensitive data at rest
- Use HTTPS for all communications
- Implement proper access controls

### Input Validation

- Validate all user inputs
- Sanitize data before database operations
- Use prepared statements for queries

### File Security

- Validate file uploads
- Scan uploaded files for malware
- Restrict file access permissions

## 🤝 Contributing

### Development Setup

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

### Coding Standards

- Follow PSR-12 coding standards
- Add proper documentation
- Write comprehensive tests
- Use meaningful variable names

### Testing Requirements

- All new features must have tests
- Maintain test coverage above 80%
- Test both success and error cases

## 📄 License

This module is part of the EduConnect platform and is licensed under the MIT License.

## 📞 Support

For support and questions:

- Create an issue in the repository
- Contact the development team
- Check the documentation wiki
- Join the community forum

## 🔄 Changelog

### Version 1.0.0 (2025-09-19)

**Initial Release**
- ✅ Basic card creation and management
- ✅ PDF generation with templates
- ✅ Status tracking and management
- ✅ Search and filtering
- ✅ Bulk operations
- ✅ Comprehensive test suite
- ✅ Security features
- ✅ Performance optimization

### Future Roadmap

- 🔄 **Version 1.1.0**
  - QR code integration
  - Barcode support
  - Advanced reporting
  - Email notifications

- 🔄 **Version 1.2.0**
  - Mobile app integration
  - Biometric features
  - Advanced security features
  - Multi-language support

---

**Made with ❤️ for the EduConnect community**
