-- EAV's Homestay Booking and Management System
-- Import in phpMyAdmin (XAMPP) or: mysql -u root < database.sql
CREATE DATABASE IF NOT EXISTS eavs_homestay CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE eavs_homestay;

-- Rooms Table
CREATE TABLE IF NOT EXISTS rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_name VARCHAR(100) NOT NULL,
  color VARCHAR(50) NOT NULL,
  capacity INT NOT NULL DEFAULT 4,
  price_per_night DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
  description TEXT,
  image_urls TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  guest_name VARCHAR(150) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  email VARCHAR(150) NOT NULL,
  num_guests INT NOT NULL DEFAULT 1,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_type ENUM('Advance','Full') NOT NULL DEFAULT 'Advance',
  payment_method ENUM('Cash','GCash','Maya') NOT NULL DEFAULT 'Cash',
  payment_status ENUM('Pending','Advance Paid','Fully Paid') NOT NULL DEFAULT 'Pending',
  proof_image VARCHAR(255) DEFAULT NULL,
  booking_status ENUM('Pending','Approved','Declined','Completed','CheckedIn','CheckedOut') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  INDEX idx_room_dates (room_id, check_in, check_out),
  INDEX idx_status (booking_status)
);

-- Admins Table (owner creates staff, no public signup)
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','staff') NOT NULL DEFAULT 'staff',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Rooms (both P3000 for now, Room 2 package TBD)
-- Fixed IDs so re-import does not create duplicates
INSERT INTO rooms (id, room_name, color, capacity, price_per_night, description, image_urls) VALUES
(1, 'Room 1 - Brown Unit', 'Brown', 6, 3000.00, 'Large-capacity brown unit with wooden bunk beds, air conditioning, flat-screen TV, hot/cold water dispenser, private dining setup. Base package includes FREE 1-day scooter rental.', 'assets/images/room1/537256849_122134520288410695_3908963705729607760_n.jpg'),
(2, 'Room 2 - Blue Unit', 'Blue', 6, 3000.00, 'Large-capacity blue unit with queen bed, air conditioning, flat-screen TV, mini-fridge, hot/cold water dispenser, private wooden dining setup. Package to be updated.', 'assets/images/room2/538113582_122134519310410695_8735526467688003549_n.jpg')
ON DUPLICATE KEY UPDATE room_name=VALUES(room_name), price_per_night=VALUES(price_per_night), description=VALUES(description);

-- Seed Admin: username=admin password=123 (real bcrypt hash for '123', owner role)
INSERT INTO admins (username, password_hash, role, active) VALUES
('admin', '$2y$10$kYKSeUgM6ehKwLRPJnrYge3bOIJHlXCPOmx2Een8Him..0XSoZeUO', 'owner', 1)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role='owner', active=1;
