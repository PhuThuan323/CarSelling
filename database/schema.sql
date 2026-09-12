USE car_sales;
drop table IF EXISTS users;
create table users(
    id bigint unsigned AUTO_INCREMENT primary key,
    name VARCHAR(100) not null,
    email varchar(100) not NULL,
    phone varchar (20) unique,
    password varchar(255) NULL,
    avata_url varchar(500) null,
    role enum('admin', 'customer', 'staff') default 'customer',
    last_login_at datetime null,
    status enum ('active', 'inactive', 'blocked')
);
create table users_addresses(
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    recip_name varchar(150) not null,
    recip_phone varchar(20) not null,
    
    address_detail varchar(500) not null, 
    is_default_address BOOLEAN not null DEFAULT false,

    constraint fk_user_address_user
        foreign key (user_id)
        references users(id)
        on delete cascade
);
CREATE TABLE password_reset_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_reset_user (user_id)
);

 