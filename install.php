<?php
// One-click installer for XAMPP. Visit: http://localhost/EAVS/install.php then delete this file.
$host='localhost'; $user='root'; $pass='';
$mysqli = new mysqli($host,$user,$pass);
if ($mysqli->connect_error) die('MySQL not running in XAMPP: '.$mysqli->connect_error);
$mysqli->query("CREATE DATABASE IF NOT EXISTS eavs_homestay CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$mysqli->select_db('eavs_homestay');
$sql = file_get_contents(__DIR__.'/database.sql');
// strip CREATE DATABASE / USE lines, run the rest
$sql = preg_replace('/CREATE DATABASE.*?;/is','',$sql);
$sql = preg_replace('/USE\s+.*?;/i','',$sql);
foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) { $mysqli->query($q); }
// Migrate admins table for roles (owner/staff, no public signup)
$mysqli->query("ALTER TABLE admins ADD COLUMN IF NOT EXISTS role ENUM('owner','staff') NOT NULL DEFAULT 'staff'");
$mysqli->query("ALTER TABLE admins ADD COLUMN IF NOT EXISTS active TINYINT(1) NOT NULL DEFAULT 1");
// Ensure admin admin/123 with a FRESH bcrypt hash (fixes placeholder hash in database.sql)
$hash = password_hash('123', PASSWORD_DEFAULT);
$st = $mysqli->prepare("INSERT INTO admins (username,password_hash,role,active) VALUES ('admin',?,'owner',1) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role='owner', active=1");
$st->bind_param('s',$hash); $st->execute();
// Ensure rooms exist
$mysqli->query("INSERT IGNORE INTO rooms (id,room_name,color,capacity,price_per_night,description,image_urls) VALUES
(1,'Room 1 - Brown Unit','Brown',6,3000.00,'Large-capacity brown unit with wooden bunk beds, air conditioning, flat-screen TV, hot/cold water dispenser, private dining setup. Base package includes FREE 1-day scooter rental.','assets/images/room1/537256849_122134520288410695_3908963705729607760_n.jpg'),
(2,'Room 2 - Blue Unit','Blue',6,3000.00,'Large-capacity blue unit with queen bed, air conditioning, flat-screen TV, mini-fridge, hot/cold water dispenser, private wooden dining setup. Package to be updated.','assets/images/room2/538113582_122134519310410695_8735526467688003549_n.jpg')");
echo "<h2>EAV's Homestay installed OK</h2><p>Admin login: <b>admin</b> / <b>123</b></p><p><a href='index.php'>Go to site</a> | <a href='admin/login.php'>Admin login</a></p>";
