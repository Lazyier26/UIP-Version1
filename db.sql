-- Create database
CREATE DATABASE IF NOT EXISTS uip_registration;
USE uip_registration;

-- Create table for incoming intern registrations
CREATE TABLE incoming_interns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    contact VARCHAR(50) NOT NULL,
    birthday DATE NOT NULL,
    address TEXT NOT NULL,
    school VARCHAR(255) NOT NULL,
    program VARCHAR(255) NOT NULL,
    school_address TEXT NOT NULL,
    ojt_hours INT NOT NULL,
    available_days JSON NOT NULL,
    cv_filename VARCHAR(255),
    cv_filepath VARCHAR(500),
    picture_filename VARCHAR(255),
    picture_filepath VARCHAR(500),
    endorsement_filename VARCHAR(255),
    endorsement_filepath VARCHAR(500),
    moa_filename VARCHAR(255),
    moa_filepath VARCHAR(500),
    terms_accepted BOOLEAN DEFAULT FALSE,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'
);

-- Create index for faster searches
CREATE INDEX idx_email ON incoming_interns(email);
CREATE INDEX idx_registration_date ON incoming_interns(registration_date);
CREATE INDEX idx_status ON incoming_interns(status);