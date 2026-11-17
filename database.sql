-- Arni Hospital Management System
-- MySQL schema and fictional demo records.
-- Import this file into the existing hospital database.
-- It matches the table structure in install.php.
-- CREATE TABLE IF NOT EXISTS does not erase existing tables or records.

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    specialization VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    department_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_department
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    age TINYINT UNSIGNED NULL,
    gender VARCHAR(20) NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Scheduled',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointment_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    payment_status VARCHAR(30) NOT NULL DEFAULT 'Unpaid',
    bill_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bill_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fictional demo departments (skip if the same department name exists).
INSERT INTO departments (name, description)
SELECT 'General Medicine', 'General health consultation and care'
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE name = 'General Medicine');

INSERT INTO departments (name, description)
SELECT 'Cardiology', 'Heart and cardiovascular care'
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE name = 'Cardiology');

INSERT INTO departments (name, description)
SELECT 'Pediatrics', 'Healthcare for children'
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE name = 'Pediatrics');

-- Fictional demo patients (skip if the phone number already exists).
INSERT INTO patients (name, age, gender, phone, address)
SELECT 'Rahim Uddin', 35, 'Male', '01700000000', 'Feni, Bangladesh'
WHERE NOT EXISTS (SELECT 1 FROM patients WHERE phone = '01700000000');

INSERT INTO patients (name, age, gender, phone, address)
SELECT 'Karim Ahmed', 42, 'Male', '01800000000', 'Feni, Bangladesh'
WHERE NOT EXISTS (SELECT 1 FROM patients WHERE phone = '01800000000');

INSERT INTO patients (name, age, gender, phone, address)
SELECT 'Nusrat Jahan', 28, 'Female', '01900000000', 'Dhaka, Bangladesh'
WHERE NOT EXISTS (SELECT 1 FROM patients WHERE phone = '01900000000');

INSERT INTO patients (name, age, gender, phone, address)
SELECT 'Ayesha Akter', 31, 'Female', '01600000000', 'Chittagong, Bangladesh'
WHERE NOT EXISTS (SELECT 1 FROM patients WHERE phone = '01600000000');

INSERT INTO patients (name, age, gender, phone, address)
SELECT 'Sakib Hasan', 24, 'Male', '01500000000', 'Feni, Bangladesh'
WHERE NOT EXISTS (SELECT 1 FROM patients WHERE phone = '01500000000');

-- Fictional demo doctors (skip if the email already exists).
INSERT INTO doctors (name, specialization, phone, email, department_id)
SELECT 'Dr. Farhan Ahmed', 'General Physician', '01711111111',
       'farhan.demo@example.com',
       (SELECT id FROM departments WHERE name = 'General Medicine' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE email = 'farhan.demo@example.com');

INSERT INTO doctors (name, specialization, phone, email, department_id)
SELECT 'Dr. Samira Islam', 'Cardiologist', '01822222222',
       'samira.demo@example.com',
       (SELECT id FROM departments WHERE name = 'Cardiology' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE email = 'samira.demo@example.com');

INSERT INTO doctors (name, specialization, phone, email, department_id)
SELECT 'Dr. Nadia Rahman', 'Pediatrician', '01933333333',
       'nadia.demo@example.com',
       (SELECT id FROM departments WHERE name = 'Pediatrics' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE email = 'nadia.demo@example.com');

-- One fictional appointment and bill linked to the first demo patient/doctor.
INSERT INTO appointments
    (patient_id, doctor_id, appointment_date, appointment_time, status, notes)
SELECT p.id, d.id, '2026-12-01', '10:00:00', 'Scheduled', 'Fictional demo appointment'
FROM patients p
JOIN doctors d ON d.email = 'farhan.demo@example.com'
WHERE p.phone = '01700000000'
  AND NOT EXISTS (
      SELECT 1 FROM appointments a
      WHERE a.patient_id = p.id
        AND a.doctor_id = d.id
        AND a.appointment_date = '2026-12-01'
  );

INSERT INTO bills (patient_id, amount, description, payment_status, bill_date)
SELECT p.id, 500.00, 'General consultation - demo', 'Unpaid', '2026-12-01'
FROM patients p
WHERE p.phone = '01700000000'
  AND NOT EXISTS (
      SELECT 1 FROM bills b
      WHERE b.patient_id = p.id
        AND b.description = 'General consultation - demo'
        AND b.bill_date = '2026-12-01'
  );

-- Note: install.php creates the initial admin user if users is empty.
-- After importing, visit install.php once if the tables were not installed yet.
