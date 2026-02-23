-- Nusantara API Documentation Database Schema
-- Move from JSON based to MySQL for multi-user and multi-project support

CREATE DATABASE IF NOT EXISTS nusantara_apidoc;
USE nusantara_apidoc;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'editor', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Projects Table (Collections)
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Environments Table (For variable substitution like {{base_url}})
CREATE TABLE IF NOT EXISTS environments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    variables JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Endpoints Table
CREATE TABLE IF NOT EXISTS endpoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    url TEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'Default',
    params JSON,
    headers JSON,
    body_type VARCHAR(20) DEFAULT 'none',
    body TEXT,
    last_updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (last_updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity/Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    endpoint_id INT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial Seed Data (Default password: admin123)
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$UG3JqSyXfAOkqTc0cs/dRu7qh5umZoxhlabig2mQ3Wlu/wF/GHB4e', 'superadmin'),
('editor1', '$2y$10$UG3JqSyXfAOkqTc0cs/dRu7qh5umZoxhlabig2mQ3Wlu/wF/GHB4e', 'editor'),
('viewer1', '$2y$10$UG3JqSyXfAOkqTc0cs/dRu7qh5umZoxhlabig2mQ3Wlu/wF/GHB4e', 'viewer')
ON DUPLICATE KEY UPDATE password=VALUES(password), role=VALUES(role);

-- Initial Project
INSERT INTO projects (name, description) VALUES 
('Nusantara Core', 'Dokumentasi internal untuk sistem inti Nusantara')
ON DUPLICATE KEY UPDATE id=id;
