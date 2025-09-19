-- Migration: Create template_items table
-- Description: Creates the main table for the Template module
-- Date: 2025-09-19

CREATE TABLE IF NOT EXISTS `template_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `status` enum('active', 'inactive', 'pending') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data (optional)
INSERT INTO `template_items` (`name`, `description`, `status`) VALUES
('Sample Item 1', 'This is a sample template item for demonstration purposes.', 'active'),
('Sample Item 2', 'Another sample item to show how the template works.', 'pending'),
('Sample Item 3', 'A third sample item with inactive status.', 'inactive');

-- Create indexes for better performance
CREATE INDEX `idx_name_status` ON `template_items` (`name`, `status`);
CREATE INDEX `idx_updated_at` ON `template_items` (`updated_at`);

-- Add comments to table and columns
ALTER TABLE `template_items` COMMENT = 'Template items table for the Template module';

-- Add column comments
ALTER TABLE `template_items` 
    MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
    MODIFY COLUMN `name` varchar(255) NOT NULL COMMENT 'Item name',
    MODIFY COLUMN `description` text COMMENT 'Item description',
    MODIFY COLUMN `status` enum('active', 'inactive', 'pending') DEFAULT 'active' COMMENT 'Item status',
    MODIFY COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
    MODIFY COLUMN `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp';
