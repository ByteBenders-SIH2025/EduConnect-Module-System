-- Migration: Create Identity Cards Table
-- Date: 2025-09-19
-- Description: Creates the identity_cards table for managing student and staff identity cards

CREATE TABLE IF NOT EXISTS `identity_cards` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL COMMENT 'Reference to students table',
    `card_type` enum('student','staff','visitor') NOT NULL DEFAULT 'student' COMMENT 'Type of identity card',
    `card_number` varchar(50) NOT NULL COMMENT 'Unique card number',
    `valid_from` date NOT NULL COMMENT 'Card validity start date',
    `valid_until` date NOT NULL COMMENT 'Card validity end date',
    `status` enum('pending','active','generated','expired','suspended','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Current status of the card',
    `notes` text DEFAULT NULL COMMENT 'Additional notes or comments',
    `photo_path` varchar(255) DEFAULT NULL COMMENT 'Path to card photo if different from student photo',
    `qr_code_data` text DEFAULT NULL COMMENT 'QR code data for the card',
    `barcode_data` varchar(100) DEFAULT NULL COMMENT 'Barcode data for the card',
    `template_used` varchar(50) DEFAULT 'default' COMMENT 'Template used for card generation',
    `generated_at` timestamp NULL DEFAULT NULL COMMENT 'When the card was generated',
    `generated_by` int(11) DEFAULT NULL COMMENT 'User who generated the card',
    `printed_at` timestamp NULL DEFAULT NULL COMMENT 'When the card was printed',
    `printed_by` int(11) DEFAULT NULL COMMENT 'User who printed the card',
    `issued_at` timestamp NULL DEFAULT NULL COMMENT 'When the card was issued to student',
    `issued_by` int(11) DEFAULT NULL COMMENT 'User who issued the card',
    `returned_at` timestamp NULL DEFAULT NULL COMMENT 'When the card was returned (if applicable)',
    `returned_by` int(11) DEFAULT NULL COMMENT 'User who received the returned card',
    `replacement_for` int(11) DEFAULT NULL COMMENT 'ID of card this replaces (if replacement)',
    `replacement_reason` varchar(255) DEFAULT NULL COMMENT 'Reason for replacement',
    `security_features` json DEFAULT NULL COMMENT 'JSON data for security features (watermark, hologram, etc.)',
    `custom_fields` json DEFAULT NULL COMMENT 'Custom fields specific to institution',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record update timestamp',
    `created_by` int(11) NOT NULL COMMENT 'User who created the record',
    `updated_by` int(11) DEFAULT NULL COMMENT 'User who last updated the record',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_card_number` (`card_number`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_card_type` (`card_type`),
    KEY `idx_status` (`status`),
    KEY `idx_valid_from` (`valid_from`),
    KEY `idx_valid_until` (`valid_until`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_generated_at` (`generated_at`),
    KEY `idx_replacement_for` (`replacement_for`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_updated_by` (`updated_by`),
    KEY `idx_generated_by` (`generated_by`),
    KEY `idx_printed_by` (`printed_by`),
    KEY `idx_issued_by` (`issued_by`),
    KEY `idx_returned_by` (`returned_by`),
    CONSTRAINT `fk_identity_cards_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_identity_cards_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_identity_cards_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_identity_cards_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_identity_cards_printed_by` FOREIGN KEY (`printed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_identity_cards_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_identity_cards_returned_by` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_identity_cards_replacement_for` FOREIGN KEY (`replacement_for`) REFERENCES `identity_cards` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Identity cards for students and staff';

-- Create indexes for better performance
CREATE INDEX `idx_identity_cards_composite_1` ON `identity_cards` (`student_id`, `status`, `valid_until`);
CREATE INDEX `idx_identity_cards_composite_2` ON `identity_cards` (`card_type`, `status`, `created_at`);
CREATE INDEX `idx_identity_cards_composite_3` ON `identity_cards` (`valid_from`, `valid_until`, `status`);

-- Create a view for active cards
CREATE OR REPLACE VIEW `active_identity_cards` AS
SELECT 
    ic.*,
    s.name as student_name,
    s.student_id as student_number,
    s.email as student_email,
    s.phone as student_phone,
    s.photo as student_photo,
    s.date_of_birth,
    s.gender,
    s.address,
    c.name as class_name,
    c.id as class_id,
    d.name as department_name,
    d.id as department_id,
    uc.name as created_by_name,
    uu.name as updated_by_name,
    ug.name as generated_by_name,
    up.name as printed_by_name,
    ui.name as issued_by_name,
    ur.name as returned_by_name,
    DATEDIFF(ic.valid_until, CURDATE()) as days_until_expiry,
    CASE 
        WHEN ic.valid_until < CURDATE() THEN 'expired'
        WHEN ic.valid_until <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
        ELSE 'valid'
    END as validity_status
FROM `identity_cards` ic
LEFT JOIN `students` s ON ic.student_id = s.id
LEFT JOIN `classes` c ON s.class_id = c.id
LEFT JOIN `departments` d ON s.department_id = d.id
LEFT JOIN `users` uc ON ic.created_by = uc.id
LEFT JOIN `users` uu ON ic.updated_by = uu.id
LEFT JOIN `users` ug ON ic.generated_by = ug.id
LEFT JOIN `users` up ON ic.printed_by = up.id
LEFT JOIN `users` ui ON ic.issued_by = ui.id
LEFT JOIN `users` ur ON ic.returned_by = ur.id
WHERE ic.status IN ('active', 'generated')
AND ic.valid_until >= CURDATE();

-- Create a view for expiring cards
CREATE OR REPLACE VIEW `expiring_identity_cards` AS
SELECT 
    ic.*,
    s.name as student_name,
    s.student_id as student_number,
    s.email as student_email,
    s.phone as student_phone,
    c.name as class_name,
    d.name as department_name,
    DATEDIFF(ic.valid_until, CURDATE()) as days_until_expiry
FROM `identity_cards` ic
LEFT JOIN `students` s ON ic.student_id = s.id
LEFT JOIN `classes` c ON s.class_id = c.id
LEFT JOIN `departments` d ON s.department_id = d.id
WHERE ic.status = 'active'
AND ic.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
ORDER BY ic.valid_until ASC;

-- Create a view for card statistics
CREATE OR REPLACE VIEW `identity_card_statistics` AS
SELECT 
    COUNT(*) as total_cards,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cards,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_cards,
    SUM(CASE WHEN status = 'generated' THEN 1 ELSE 0 END) as generated_cards,
    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_cards,
    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_cards,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_cards,
    SUM(CASE WHEN card_type = 'student' THEN 1 ELSE 0 END) as student_cards,
    SUM(CASE WHEN card_type = 'staff' THEN 1 ELSE 0 END) as staff_cards,
    SUM(CASE WHEN card_type = 'visitor' THEN 1 ELSE 0 END) as visitor_cards,
    SUM(CASE WHEN valid_until < CURDATE() THEN 1 ELSE 0 END) as expired_by_date,
    SUM(CASE WHEN valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon,
    SUM(CASE WHEN generated_at IS NOT NULL THEN 1 ELSE 0 END) as generated_count,
    SUM(CASE WHEN printed_at IS NOT NULL THEN 1 ELSE 0 END) as printed_count,
    SUM(CASE WHEN issued_at IS NOT NULL THEN 1 ELSE 0 END) as issued_count,
    AVG(DATEDIFF(valid_until, valid_from)) as average_validity_days
FROM `identity_cards`;

-- Insert sample data (optional - remove in production)
INSERT INTO `identity_cards` (
    `student_id`, `card_type`, `card_number`, `valid_from`, `valid_until`, 
    `status`, `notes`, `created_by`
) VALUES 
(1, 'student', 'ID20250001', '2025-01-01', '2025-12-31', 'active', 'Sample student card', 1),
(2, 'student', 'ID20250002', '2025-01-01', '2025-12-31', 'pending', 'Sample pending card', 1),
(3, 'staff', 'ID20250003', '2025-01-01', '2025-12-31', 'generated', 'Sample staff card', 1);

-- Create triggers for automatic status updates
DELIMITER $$

-- Trigger to automatically set status to 'expired' when valid_until date passes
CREATE TRIGGER `tr_identity_cards_auto_expire` 
BEFORE UPDATE ON `identity_cards`
FOR EACH ROW
BEGIN
    IF NEW.valid_until < CURDATE() AND OLD.status NOT IN ('expired', 'cancelled') THEN
        SET NEW.status = 'expired';
    END IF;
END$$

-- Trigger to log card status changes
CREATE TRIGGER `tr_identity_cards_status_log` 
AFTER UPDATE ON `identity_cards`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO `identity_card_status_log` (
            `card_id`, `old_status`, `new_status`, `changed_at`, `changed_by`
        ) VALUES (
            NEW.id, OLD.status, NEW.status, NOW(), NEW.updated_by
        );
    END IF;
END$$

DELIMITER ;

-- Create status log table for tracking status changes
CREATE TABLE IF NOT EXISTS `identity_card_status_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `card_id` int(11) NOT NULL,
    `old_status` varchar(20) NOT NULL,
    `new_status` varchar(20) NOT NULL,
    `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `changed_by` int(11) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_card_id` (`card_id`),
    KEY `idx_changed_at` (`changed_at`),
    KEY `idx_changed_by` (`changed_by`),
    CONSTRAINT `fk_status_log_card` FOREIGN KEY (`card_id`) REFERENCES `identity_cards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_status_log_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log of identity card status changes';

-- Create activity log table for tracking all card activities
CREATE TABLE IF NOT EXISTS `identity_card_activity_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `card_id` int(11) NOT NULL,
    `action` varchar(50) NOT NULL COMMENT 'Action performed (created, updated, generated, printed, issued, etc.)',
    `description` text NOT NULL COMMENT 'Description of the action',
    `old_data` json DEFAULT NULL COMMENT 'Previous data (for updates)',
    `new_data` json DEFAULT NULL COMMENT 'New data (for updates)',
    `performed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `performed_by` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_card_id` (`card_id`),
    KEY `idx_action` (`action`),
    KEY `idx_performed_at` (`performed_at`),
    KEY `idx_performed_by` (`performed_by`),
    CONSTRAINT `fk_activity_log_card` FOREIGN KEY (`card_id`) REFERENCES `identity_cards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Activity log for identity card operations';

-- Create stored procedure for generating card numbers
DELIMITER $$

CREATE PROCEDURE `sp_generate_card_number`(
    IN p_card_type VARCHAR(20),
    OUT p_card_number VARCHAR(50)
)
BEGIN
    DECLARE v_prefix VARCHAR(10);
    DECLARE v_year VARCHAR(4);
    DECLARE v_counter INT DEFAULT 1;
    DECLARE v_card_number VARCHAR(50);
    DECLARE v_exists INT DEFAULT 1;
    
    -- Set prefix based on card type
    CASE p_card_type
        WHEN 'student' THEN SET v_prefix = 'STU';
        WHEN 'staff' THEN SET v_prefix = 'STA';
        WHEN 'visitor' THEN SET v_prefix = 'VIS';
        ELSE SET v_prefix = 'ID';
    END CASE;
    
    -- Get current year
    SET v_year = YEAR(CURDATE());
    
    -- Generate unique card number
    WHILE v_exists > 0 DO
        SET v_card_number = CONCAT(v_prefix, v_year, LPAD(v_counter, 4, '0'));
        
        SELECT COUNT(*) INTO v_exists 
        FROM identity_cards 
        WHERE card_number = v_card_number;
        
        IF v_exists > 0 THEN
            SET v_counter = v_counter + 1;
        END IF;
    END WHILE;
    
    SET p_card_number = v_card_number;
END$$

DELIMITER ;

-- Create stored procedure for bulk status update
DELIMITER $$

CREATE PROCEDURE `sp_bulk_update_card_status`(
    IN p_card_ids TEXT,
    IN p_new_status VARCHAR(20),
    IN p_updated_by INT,
    OUT p_affected_rows INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Update card statuses
    SET @sql = CONCAT('UPDATE identity_cards SET status = ''', p_new_status, ''', updated_at = NOW(), updated_by = ', p_updated_by, ' WHERE id IN (', p_card_ids, ')');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    SET p_affected_rows = ROW_COUNT();
    
    COMMIT;
END$$

DELIMITER ;

-- Create function to check if student has active card
DELIMITER $$

CREATE FUNCTION `fn_student_has_active_card`(p_student_id INT)
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_count
    FROM identity_cards
    WHERE student_id = p_student_id
    AND status IN ('active', 'generated')
    AND valid_until >= CURDATE();
    
    RETURN v_count > 0;
END$$

DELIMITER ;

-- Create function to get days until expiry
DELIMITER $$

CREATE FUNCTION `fn_days_until_expiry`(p_card_id INT)
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_days INT DEFAULT 0;
    
    SELECT DATEDIFF(valid_until, CURDATE()) INTO v_days
    FROM identity_cards
    WHERE id = p_card_id;
    
    RETURN IFNULL(v_days, 0);
END$$

DELIMITER ;

-- Add comments to the main table
ALTER TABLE `identity_cards` COMMENT = 'Identity cards management system - stores all student and staff identity card information including validity, status, and generation details';

-- Create indexes for JSON columns (MySQL 5.7+)
-- ALTER TABLE `identity_cards` ADD INDEX `idx_security_features` ((CAST(`security_features` AS CHAR(255) ARRAY)));
-- ALTER TABLE `identity_cards` ADD INDEX `idx_custom_fields` ((CAST(`custom_fields` AS CHAR(255) ARRAY)));

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON identity_cards TO 'your_app_user'@'localhost';
-- GRANT SELECT ON active_identity_cards TO 'your_app_user'@'localhost';
-- GRANT SELECT ON expiring_identity_cards TO 'your_app_user'@'localhost';
-- GRANT SELECT ON identity_card_statistics TO 'your_app_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE sp_generate_card_number TO 'your_app_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE sp_bulk_update_card_status TO 'your_app_user'@'localhost';

-- Migration completed successfully
SELECT 'Identity Cards table and related objects created successfully' as status;
