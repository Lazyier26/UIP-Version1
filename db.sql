-- UIP Registration Database Schema 

-- Create database
CREATE DATABASE uip_registration;
USE uip_registration;

-- 1. Universities table
CREATE TABLE universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_name VARCHAR(255) NOT NULL,
    university_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_university_name (university_name)
);

-- 2. Programs table 
CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(255) NOT NULL,
    program_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_program_name (program_name)
);

-- 3. Main registration table
CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Personal Information
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    contact_number VARCHAR(20) NOT NULL,
    birthday DATE NOT NULL,
    complete_address TEXT NOT NULL,
    
    -- Academic Information
    university_id INT,
    program_id INT,
    total_ojt_hours INT NOT NULL CHECK (total_ojt_hours > 0),
    
    -- File uploads 
    cv_file_path VARCHAR(500),
    cv_original_name VARCHAR(255),
    cv_file_size INT,
    
    picture_file_path VARCHAR(500),
    picture_original_name VARCHAR(255),
    picture_file_size INT,
    
    endorsement_file_path VARCHAR(500),
    endorsement_original_name VARCHAR(255),
    endorsement_file_size INT,
    
    moa_file_path VARCHAR(500),
    moa_original_name VARCHAR(255),
    moa_file_size INT,
    
    -- Application Status
    application_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    registration_type ENUM('incoming_intern') DEFAULT 'incoming_intern',
    
    -- Terms and conditions
    terms_accepted BOOLEAN NOT NULL DEFAULT FALSE,
    terms_accepted_at TIMESTAMP NULL,
    terms_ip_address VARCHAR(45),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE SET NULL,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_email (email),
    INDEX idx_application_status (application_status),
    INDEX idx_created_at (created_at),
    INDEX idx_full_name (full_name),
    INDEX idx_contact_number (contact_number)
);

-- 4. Available days table 
CREATE TABLE available_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    
    -- Unique constraint to prevent duplicate days for same registration
    UNIQUE KEY unique_registration_day (registration_id, day_of_week),
    
    -- Index
    INDEX idx_registration_id (registration_id)
);

-- 5. File uploads audit table
CREATE TABLE file_uploads_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    file_type ENUM('cv', 'picture', 'endorsement', 'moa') NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    upload_ip_address VARCHAR(45),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    
    -- Index
    INDEX idx_registration_id (registration_id),
    INDEX idx_file_type (file_type)
);

-- 6. System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index
    INDEX idx_setting_key (setting_key)
);

-- Insert some initial system settings
INSERT INTO system_settings (setting_key, setting_value, setting_description) VALUES
('max_file_size_mb', '10', 'Maximum file size in MB for uploads'),
('allowed_cv_types', 'pdf', 'Allowed file types for CV upload'),
('allowed_picture_types', 'jpg,jpeg,png', 'Allowed file types for picture upload'),
('allowed_document_types', 'pdf,doc,docx,jpg,jpeg,png', 'Allowed file types for documents'),
('registration_open', 'true', 'Whether registration is currently open');

-- Create views for common queries

-- View for registration summary
CREATE VIEW registration_summary AS
SELECT 
    r.id,
    r.full_name,
    r.email,
    r.contact_number,
    r.birthday,
    u.university_name,
    p.program_name,
    r.total_ojt_hours,
    r.application_status,
    r.created_at,
    GROUP_CONCAT(ad.day_of_week ORDER BY 
        FIELD(ad.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday')
    ) as available_days
FROM registrations r
LEFT JOIN universities u ON r.university_id = u.id
LEFT JOIN programs p ON r.program_id = p.id
LEFT JOIN available_days ad ON r.id = ad.registration_id
GROUP BY r.id;

-- View for pending applications
CREATE VIEW pending_applications AS
SELECT 
    rs.*,
    DATEDIFF(CURRENT_DATE, DATE(rs.created_at)) as days_pending
FROM registration_summary rs
WHERE rs.application_status = 'pending'
ORDER BY rs.created_at DESC;

-- Stored procedures for common operations

DELIMITER //

-- Procedure to insert a new registration
CREATE PROCEDURE InsertRegistration(
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_contact_number VARCHAR(20),
    IN p_birthday DATE,
    IN p_complete_address TEXT,
    IN p_university_name VARCHAR(255),
    IN p_university_address TEXT,
    IN p_program_name VARCHAR(255),
    IN p_total_ojt_hours INT,
    IN p_available_days JSON,
    IN p_terms_ip_address VARCHAR(45),
    OUT p_registration_id INT
)
BEGIN
    DECLARE v_university_id INT;
    DECLARE v_program_id INT;
    DECLARE v_day VARCHAR(20);
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE day_cursor CURSOR FOR 
        SELECT JSON_UNQUOTE(JSON_EXTRACT(p_available_days, CONCAT('$[', @row_number, ']')))
        FROM (SELECT @row_number := @row_number + 1 as rn 
              FROM (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t1
              CROSS JOIN (SELECT @row_number := -1) t2) numbers
        WHERE @row_number < JSON_LENGTH(p_available_days);
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;

    START TRANSACTION;

    -- Insert or get university
    INSERT INTO universities (university_name, university_address) 
    VALUES (p_university_name, p_university_address)
    ON DUPLICATE KEY UPDATE university_name = university_name;
    
    SELECT id INTO v_university_id FROM universities 
    WHERE university_name = p_university_name LIMIT 1;

    -- Insert or get program
    INSERT INTO programs (program_name) 
    VALUES (p_program_name)
    ON DUPLICATE KEY UPDATE program_name = program_name;
    
    SELECT id INTO v_program_id FROM programs 
    WHERE program_name = p_program_name LIMIT 1;

    -- Insert registration
    INSERT INTO registrations (
        full_name, email, contact_number, birthday, complete_address,
        university_id, program_id, total_ojt_hours, terms_accepted, 
        terms_accepted_at, terms_ip_address
    ) VALUES (
        p_full_name, p_email, p_contact_number, p_birthday, p_complete_address,
        v_university_id, v_program_id, p_total_ojt_hours, TRUE, 
        CURRENT_TIMESTAMP, p_terms_ip_address
    );

    SET p_registration_id = LAST_INSERT_ID();

    -- Insert available days
    SET @row_number = -1;
    OPEN day_cursor;
    
    read_loop: LOOP
        FETCH day_cursor INTO v_day;
        IF v_done THEN
            LEAVE read_loop;
        END IF;
        
        IF v_day IS NOT NULL THEN
            INSERT INTO available_days (registration_id, day_of_week)
            VALUES (p_registration_id, v_day);
        END IF;
    END LOOP;
    
    CLOSE day_cursor;

    COMMIT;
END //

DELIMITER ;