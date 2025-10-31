








CREATE DATABASE IF NOT EXISTS auction_db;
USE auction_db;

-- Tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    bio TEXT,
    profile_image VARCHAR(255),
    is_admin TINYINT(1) DEFAULT 0,
    is_blocked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel listings (ganti dari items)
CREATE TABLE listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_price DECIMAL(10, 2) NOT NULL,
    current_price DECIMAL(10, 2) NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('aktif', 'selesai', 'dibatalkan') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Tabel listing_images
CREATE TABLE listing_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

-- Tabel bids
CREATE TABLE bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id INT NOT NULL,
    bid_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert data kategori
INSERT INTO categories (name, description) VALUES 
('Elektronik', 'Peralatan elektronik seperti smartphone, laptop, dll.'),
('Fashion', 'Pakaian, sepatu, aksesoris fashion'),
('Koleksi', 'Barang-barang koleksi seperti koin, perangko, dll.'),
('Kendaraan', 'Mobil, motor, dan kendaraan lainnya'),
('Properti', 'Rumah, apartemen, tanah, dll.'),
('Seni', 'Karya seni seperti lukisan, patung, dll.'),
('Lainnya', 'Kategori lain yang tidak termasuk di atas');

-- Insert admin user (password: admin123)
INSERT INTO users (username, email, password_hash, full_name, is_admin) 
VALUES ('admin', 'admin@example.com', '$2y$10$8K1O/ZGq4KvG5X5M0J9K8.4J5W8K3O/ZGq4KvG5X5M0J9K8.4J5W8K', 'Administrator', 1);
