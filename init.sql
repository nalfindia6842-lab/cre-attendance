CREATE TABLE centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    whatsapp_group_link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    center_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE SET NULL
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) DEFAULT NULL,
    admission_no VARCHAR(50) DEFAULT NULL,
    roll_no VARCHAR(20) DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    age INT DEFAULT NULL,
    gender ENUM('male', 'female') DEFAULT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') DEFAULT NULL,
    dob DATE DEFAULT NULL,
    address TEXT DEFAULT NULL,
    phone_number VARCHAR(20) NOT NULL, -- Corresponds to Mobile No: 1
    mobile2 VARCHAR(20) DEFAULT NULL,
    whatsapp_no VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    class VARCHAR(50) NOT NULL, -- Corresponds to course/class
    academic_year VARCHAR(20) DEFAULT NULL,
    institution_name VARCHAR(150) DEFAULT NULL,
    place VARCHAR(100) DEFAULT NULL,
    center_id INT NOT NULL,
    photo_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (student_id, date),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Insert initial centers
INSERT INTO centers (name, whatsapp_group_link) VALUES 
('SALAFI CENTER WANDOOR', 'https://chat.whatsapp.com/example1'),
('SALAFI CENTER SHANDHI NAGAR', 'https://chat.whatsapp.com/example2'),
('SALAFI CENTER THALIYAMKUNDU', 'https://chat.whatsapp.com/example3'),
('SALAFI CENTER THODIKAPPULAM', 'https://chat.whatsapp.com/example4');

-- Insert default admin (password: admin123)
-- MD5/Bcrypt can be used, for simplicity in this script we will assume password_hash is created in PHP with password_hash('admin123', PASSWORD_DEFAULT).
-- Precomputed bcrypt for 'admin123': $2y$10$e.Z.yT.T9B113B9V6T9Y..J6xT3s5q3hKz6sQ6r6q6Kx6sQ6r6q6K (this is just an example, a real one follows)
INSERT INTO users (name, username, password_hash, role) VALUES 
('System Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- The bcrypt hash above is for 'password'

CREATE TABLE fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    center_id INT NOT NULL,
    fee_type ENUM('admission', 'monthly', 'exam', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_center_fee (center_id, fee_type),
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE
);

CREATE TABLE fee_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type ENUM('admission', 'monthly', 'exam', 'other') NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    fee_month VARCHAR(20) DEFAULT NULL,
    payment_date DATE NOT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
