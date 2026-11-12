-- Database for the multi-vendor e-commerce project
CREATE DATABASE IF NOT EXISTS omnimart_store;
USE omnimart_store;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('customer','vendor') NOT NULL DEFAULT 'customer',
  api_token VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  status ENUM('pending','approved','blocked') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  image VARCHAR(255) DEFAULT '',
  category_id INT DEFAULT NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE product_variations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  attribute VARCHAR(50) NOT NULL,
  value VARCHAR(50) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  coupon_code VARCHAR(50) DEFAULT NULL,
  status ENUM('Pending','Processing','Shipped','Delivered') NOT NULL DEFAULT 'Pending',
  phone VARCHAR(20),
  address TEXT,
  payment_method VARCHAR(30),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  variation VARCHAR(100) DEFAULT '',
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  status ENUM('Pending','Processing','Shipped','Delivered') NOT NULL DEFAULT 'Pending',
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  discount_type ENUM('percent','fixed') NOT NULL,
  value DECIMAL(10,2) NOT NULL,
  expiry DATE NOT NULL
);

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  rating INT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100),
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- extra tables needed for the extra features
CREATE TABLE wishlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  UNIQUE KEY one_item (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE banners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150),
  subtitle VARCHAR(255),
  image VARCHAR(255) DEFAULT '',
  link VARCHAR(255) DEFAULT '',
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE settings (
  name VARCHAR(50) PRIMARY KEY,
  value TEXT
);

CREATE TABLE newsletter (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  question TEXT NOT NULL,
  answer TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- sample data
-- logins: admin/admin123, vendor@test.com/vendor123, user@test.com/user123
INSERT INTO admin (username, password) VALUES ('admin', '$2y$10$P6tTiyw27ju/hcNRORswk.7zHTV.MgOkqd5XsxiOOcu0vNzGBaDCG');
INSERT INTO users (name, email, password, role) VALUES
 ('Demo Vendor', 'vendor@test.com', '$2y$10$7DvI/SJmorBQH8Xs14BOyeF8E5uRj.qycweZ0T3Jfo/Yv80GOS9mi', 'vendor'),
 ('Demo Customer', 'user@test.com', '$2y$10$fwsOgHLOQLNYDKLQgsytSe97DfW9KJkuhuNvteBNyLO9ZLgLemW6O', 'customer');
INSERT INTO vendors (user_id, name, status) VALUES (1, 'Demo Store', 'approved');
INSERT INTO categories (name) VALUES ('Electronics'), ('Clothing'), ('Books'), ('Home');
INSERT INTO products (vendor_id, name, description, price, stock, image, category_id, featured) VALUES
 (1, 'Wireless Headphones', 'Bluetooth headphones with good bass and 20 hour battery.', 1499.00, 25, 'headphones.svg', 1, 1),
 (1, 'Smart Watch', 'Fitness tracker with heart rate and sleep monitor.', 2999.00, 15, 'smartwatch.svg', 1, 1),
 (1, 'Cotton T-Shirt', 'Soft cotton t-shirt for daily use.', 399.00, 60, 'tshirt.svg', 2, 1),
 (1, 'Denim Jacket', 'Classic blue denim jacket.', 1799.00, 20, 'denim-jacket.svg', 2, 0),
 (1, 'PHP and MySQL Book', 'Beginner friendly book to learn web development.', 550.00, 40, 'book.svg', 3, 1),
 (1, 'Table Lamp', 'LED table lamp with touch dimmer.', 699.00, 30, 'table-lamp.svg', 4, 0);
INSERT INTO product_variations (product_id, attribute, value, stock) VALUES
 (3, 'Size', 'S', 15), (3, 'Size', 'M', 20), (3, 'Size', 'L', 15), (3, 'Size', 'XL', 10),
 (4, 'Size', 'M', 10), (4, 'Size', 'L', 10),
 (1, 'Color', 'Black', 15), (1, 'Color', 'White', 10);
INSERT INTO coupons (code, discount_type, value, expiry) VALUES
 ('WELCOME10', 'percent', 10, '2030-12-31'),
 ('FLAT100', 'fixed', 100, '2030-12-31');
INSERT INTO banners (title, subtitle, link, active) VALUES
 ('Big Festive Sale', 'Up to 40 percent off on top products', 'search.php', 1),
 ('New Arrivals', 'Fresh products from our vendors', 'search.php', 1);
INSERT INTO settings (name, value) VALUES
 ('site_name', 'OmniMart'),
 ('tagline', 'Shop from many vendors in one place'),
 ('meta_description', 'OmniMart is a multi-vendor online store.'),
 ('meta_keywords', 'shop, online store, vendors'),
 ('theme_color', '#2563eb'),
 ('admin_email', 'admin@example.com');
