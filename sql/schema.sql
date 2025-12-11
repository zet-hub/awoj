-- Database schema for Medieval Café POS (MSSQL)
-- Run in SSMS. Uncomment CREATE DATABASE if you need to create it first.
CREATE DATABASE CafePOS;
USE CafePOS;
GO

-- Drop existing tables if rebuilding (destructive)
IF OBJECT_ID('dbo.transaction_items', 'U') IS NOT NULL DROP TABLE transaction_items;
IF OBJECT_ID('dbo.void_logs', 'U') IS NOT NULL DROP TABLE void_logs;
IF OBJECT_ID('dbo.access_logs', 'U') IS NOT NULL DROP TABLE access_logs;
IF OBJECT_ID('dbo.transactions', 'U') IS NOT NULL DROP TABLE transactions;
IF OBJECT_ID('dbo.menu_items', 'U') IS NOT NULL DROP TABLE menu_items;
IF OBJECT_ID('dbo.categories', 'U') IS NOT NULL DROP TABLE categories;
IF OBJECT_ID('dbo.users', 'U') IS NOT NULL DROP TABLE users;

CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(100) NOT NULL,
    email NVARCHAR(150) NOT NULL UNIQUE,
    password_hash NVARCHAR(255) NOT NULL,
    role NVARCHAR(20) NOT NULL CHECK (role IN ('admin', 'manager', 'cashier', 'barista')),
    created_at DATETIME2 DEFAULT GETDATE()
);

CREATE TABLE categories (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(100) NOT NULL
);

CREATE TABLE menu_items (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL FOREIGN KEY REFERENCES categories(id),
    image_url NVARCHAR(255) NULL
);

CREATE TABLE transactions (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NOT NULL FOREIGN KEY REFERENCES users(id),
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    cash_received DECIMAL(10,2) NOT NULL,
    change_given DECIMAL(10,2) NOT NULL,
    payment_method NVARCHAR(20) NOT NULL DEFAULT 'cash',
    status NVARCHAR(20) NOT NULL DEFAULT 'completed', -- completed | voided | refunded
    created_at DATETIME2 DEFAULT GETDATE()
);

CREATE TABLE transaction_items (
    id INT IDENTITY(1,1) PRIMARY KEY,
    transaction_id INT NOT NULL FOREIGN KEY REFERENCES transactions(id),
    menu_item_id INT NOT NULL FOREIGN KEY REFERENCES menu_items(id),
    quantity INT NOT NULL,
    price_each DECIMAL(10,2) NOT NULL
);

-- Log login events
CREATE TABLE access_logs (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NOT NULL FOREIGN KEY REFERENCES users(id),
    event NVARCHAR(50) NOT NULL, -- login
    ip_address NVARCHAR(64) NULL,
    user_agent NVARCHAR(255) NULL,
    created_at DATETIME2 DEFAULT GETDATE()
);

-- Log void/refund actions
CREATE TABLE void_logs (
    id INT IDENTITY(1,1) PRIMARY KEY,
    transaction_id INT NOT NULL FOREIGN KEY REFERENCES transactions(id),
    acted_by INT NOT NULL FOREIGN KEY REFERENCES users(id),
    action NVARCHAR(20) NOT NULL, -- voided | refunded
    reason NVARCHAR(255) NULL,
    created_at DATETIME2 DEFAULT GETDATE()
);

-- Seed roles/users (passwords hashed via PHP password_hash)
-- For testing simplicity only: storing plain passwords (not recommended for production).
INSERT INTO users (name, email, password_hash, role)
VALUES
('Admin', 'admin@example.com', 'Password123!', 'admin'),
('Manager', 'manager@example.com', 'Password123!', 'manager'),
('Cashier', 'cashier@example.com', 'Password123!', 'cashier'),
('Barista', 'barista@example.com', 'Password123!', 'barista');

-- Seed categories/items
INSERT INTO categories (name) VALUES ('Beverages'), ('Pastries'), ('Meals');

INSERT INTO menu_items (name, price, category_id, image_url) VALUES
('Honeyed Ale', 5.50, 1, NULL),
('Spiced Mead', 6.00, 1, NULL),
('Berry Tart', 4.25, 2, NULL),
('Hearth Bread', 2.75, 2, NULL),
('Roast Chicken', 9.50, 3, NULL);

