--Add User Table
USE car_sales;
USE car_sales;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(191) NOT NULL UNIQUE,

    password_hash VARCHAR(255) NULL,

    phone VARCHAR(20) NULL,

    avatar VARCHAR(500) NULL,

    google_id VARCHAR(255) NULL UNIQUE,

    status ENUM('active', 'inactive', 'blocked')
        NOT NULL DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);