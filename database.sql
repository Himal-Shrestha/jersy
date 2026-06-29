CREATE DATABASE jersey_club;
USE jersey_club;

-- USERS
CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('user','admin') DEFAULT 'user',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- JERSEYS
CREATE TABLE jerseys (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    team VARCHAR(100) NOT NULL,
    category ENUM('club','national') NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT 'default.jpg',
    size_small INT DEFAULT 0,
    size_medium INT DEFAULT 0,
    size_large INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- CART
CREATE TABLE cart (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    jersey_id INT NOT NULL,
    size ENUM('small','medium','large') NOT NULL,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY(id),

    INDEX(user_id),
    INDEX(jersey_id),

    FOREIGN KEY(user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY(jersey_id)
        REFERENCES jerseys(id)
        ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    jersey_id INT NOT NULL,
    size ENUM('small','medium','large') NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    status ENUM(
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled'
    ) DEFAULT 'pending',
    ordered_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (jersey_id)
        REFERENCES jerseys(id)
        ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,

    amount DECIMAL(10,2) NOT NULL,

    ref_id VARCHAR(100) DEFAULT NULL,
    transaction_uuid VARCHAR(100) DEFAULT NULL,

    shipping_address TEXT NOT NULL,

    payment_method ENUM('cod','esewa') DEFAULT 'esewa',

    payment_status ENUM(
        'success',
        'failed',
        'unpaid'
    ) DEFAULT 'unpaid',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);




