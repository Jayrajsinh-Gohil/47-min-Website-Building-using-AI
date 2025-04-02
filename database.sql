-- Create database
CREATE DATABASE IF NOT EXISTS mobile_shop;
USE mobile_shop;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    stock INT NOT NULL DEFAULT 0,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_address TEXT NOT NULL,
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, is_admin) 
VALUES ('admin', '$2y$10$8WxYR0AIj3UK/0TvNrXNYOeEjq5k5zDfYzNjryYE.b9UwP.WvRDOm', 'admin@mobilestore.com', 'Administrator', 1);

-- Insert sample products
INSERT INTO products (name, description, price, image, stock, category) VALUES
('iPhone 15 Pro', 'Latest iPhone with A17 Pro chip, 48MP camera, and titanium design.', 999.00, 'iphone15pro.jpg', 50, 'Smartphone'),
('iPhone 15', 'A16 Bionic chip, 48MP camera, and all-day battery life.', 799.00, 'iphone15.jpg', 75, 'Smartphone'),
('iPhone SE', 'Affordable iPhone with A15 Bionic chip and 4.7-inch display.', 429.00, 'iphonese.jpg', 100, 'Smartphone'),
('iPad Pro', '12.9-inch Liquid Retina XDR display, M2 chip, and all-day battery life.', 1099.00, 'ipadpro.jpg', 40, 'Tablet'),
('iPad Air', '10.9-inch Liquid Retina display, M1 chip, and all-day battery life.', 599.00, 'ipadair.jpg', 60, 'Tablet'),
('MacBook Pro', '14-inch Liquid Retina XDR display, M3 Pro chip, and up to 22 hours of battery life.', 1999.00, 'macbookpro.jpg', 30, 'Laptop'); 