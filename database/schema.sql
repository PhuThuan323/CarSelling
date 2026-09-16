# Hãng tạo ra chiếc xe
-- Product table
CREATE TABLE brands(
    id bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) not NULL,
    slug VARCHAR(120) not null, 
    country VARCHAR(100) null,
    logo varchar(500) null,
    description text null,
    status enum('active','inactive') not null default 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME null,
    unique key uq_brands_name (name),
    unique key uq_brands_slug (slug),
    index idx_brands_status (status)
);

# database lưu model xe 
Create table vehicle_models(
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_id bigint UNSIGNED not null, 
    name varchar(150) not null,
    slug varchar(180) not null, 
    body_type enum (
        'sedan',
        'suv',
        'hatchback',
        'pickup',
        'coupe',
        'mpv',
        'van',
        'wagon',
        'convertible',
        'other'
    ) NULL,
    description text null, 
    status enum(
        'active', 'inactive'
    ) not null DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,
    constraint fk_models_brand FOREIGN key (brand_id) references brands(id),
    UNIQUE KEY uq_model_brand_slug (brand_id, slug),
    INDEX idx_models_brand (brand_id),
    INDEX idx_models_status (status)
);

# Database của đời xe 
CREATE Table vehicle_versions(
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_id bigint UNSIGNED not null, 
    name varchar(150) not null, 
    slug varchar(150) not null, 
    production_year_from SMALLINT UNSIGNED null,
    production_year_to smallint UNSIGNED null, 
    engine_name varchar(100) null, 
    engine_code varchar(50) null, 
    fuel_type enum(
        'gasoline', 
        'diesel',
        'hybrid',
        'phev',
        'ev',
        'other'
    ) NULL,
    transmission enum (
        'manual',
        'automatic',
        'cvt',
        'dct',
        'other'
    ) NULL,
    drivetrain enum(
        'fwd',
        'rwd',
        'awd',
        '4wd',
        'other'
    ) null,
    horsepower INT UNSIGNED NULL,

    torque_nm INT UNSIGNED NULL,

    seats TINYINT UNSIGNED NULL,

    doors TINYINT UNSIGNED NULL,

    battery_capacity_kwh DECIMAL(8,2) NULL,

    range_km INT UNSIGNED NULL,

    description TEXT NULL,

    specifications JSON NULL,

    status ENUM(
        'active',
        'inactive'
    ) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_versions_model
        FOREIGN KEY (model_id)
        REFERENCES vehicle_models(id),

    UNIQUE KEY uq_version_model_slug (
        model_id,
        slug
    ),

    INDEX idx_versions_model (
        model_id
    ),

    INDEX idx_versions_status (
        status
    )
);

# Database của một chiếc xe 
CREATE TABLE vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    vehicle_version_id BIGINT UNSIGNED NOT NULL,

    stock_code VARCHAR(50) NOT NULL,

    title VARCHAR(255) NOT NULL,

    manufacture_year SMALLINT UNSIGNED NOT NULL,

    vin VARCHAR(100) NULL,

    license_plate VARCHAR(30) NULL,

    odometer_km INT UNSIGNED DEFAULT 0,

    condition_type ENUM(
        'new',
        'used'
    ) NOT NULL DEFAULT 'used',

    exterior_color VARCHAR(100) NULL,

    interior_color VARCHAR(100) NULL,

    price DECIMAL(15,2) NOT NULL,

    original_price DECIMAL(15,2) NULL,

    cost_price DECIMAL(15,2) NULL,

    negotiable BOOLEAN NOT NULL DEFAULT TRUE,

    owners_count TINYINT UNSIGNED NULL,

    registration_province VARCHAR(100) NULL,

    accident_history ENUM(
        'none',
        'minor',
        'major',
        'unknown'
    ) NOT NULL DEFAULT 'unknown',

    service_history BOOLEAN DEFAULT FALSE,

    warranty_months SMALLINT UNSIGNED NULL,

    location VARCHAR(255) NULL,

    description TEXT NULL,

    status ENUM(
        'draft',
        'available',
        'reserved',
        'sold',
        'maintenance',
        'inactive'
    ) NOT NULL DEFAULT 'draft',

    is_featured BOOLEAN NOT NULL DEFAULT FALSE,

    published_at DATETIME NULL,

    created_by BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_vehicle_version
        FOREIGN KEY (vehicle_version_id)
        REFERENCES vehicle_versions(id),

    CONSTRAINT fk_vehicle_creator
        FOREIGN KEY (created_by)
        REFERENCES users(id),

    UNIQUE KEY uq_vehicle_stock_code (
        stock_code
    ),

    UNIQUE KEY uq_vehicle_vin (
        vin
    ),

    INDEX idx_vehicle_version (
        vehicle_version_id
    ),

    INDEX idx_vehicle_status (
        status
    ),

    INDEX idx_vehicle_price (
        price
    ),

    INDEX idx_vehicle_year (
        manufacture_year
    ),

    INDEX idx_vehicle_odometer (
        odometer_km
    )
);

#  database lưu ảnh xe 
CREATE TABLE vehicle_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    vehicle_id BIGINT UNSIGNED NOT NULL,

    image_url VARCHAR(500) NOT NULL,

    alt_text VARCHAR(255) NULL,

    is_primary BOOLEAN NOT NULL DEFAULT FALSE,

    sort_order INT UNSIGNED NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_images_vehicle
        FOREIGN KEY (vehicle_id)
        REFERENCES vehicles(id)
        ON DELETE CASCADE,

    INDEX idx_images_vehicle (
        vehicle_id
    )
);

# API lưu trang bị xe 
CREATE TABLE features (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    group_name VARCHAR(100) NULL,

    name VARCHAR(150) NOT NULL,

    slug VARCHAR(180) NOT NULL,

    UNIQUE KEY uq_feature_slug (
        slug
    )
);

# Trang bị tiêu chuẩn của các hãng
CREATE TABLE vehicle_version_features (
    vehicle_version_id BIGINT UNSIGNED NOT NULL,

    feature_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (
        vehicle_version_id,
        feature_id
    ),

    CONSTRAINT fk_version_feature_version
        FOREIGN KEY (vehicle_version_id)
        REFERENCES vehicle_versions(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_version_feature_feature
        FOREIGN KEY (feature_id)
        REFERENCES features(id)
        ON DELETE CASCADE
);