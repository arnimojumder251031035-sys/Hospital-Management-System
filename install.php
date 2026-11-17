<?php
require __DIR__ . '/config.php';

$message = '';
$error = '';
try {
    $sql = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            specialization VARCHAR(120) NOT NULL,
            phone VARCHAR(30) NULL,
            email VARCHAR(120) NULL,
            department_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_doctor_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            age TINYINT UNSIGNED NULL,
            gender VARCHAR(20) NULL,
            phone VARCHAR(30) NULL,
            address TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'Scheduled',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_appointment_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            CONSTRAINT fk_appointment_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS bills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description VARCHAR(255) NOT NULL,
            payment_status VARCHAR(30) NOT NULL DEFAULT 'Unpaid',
            bill_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_bill_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    ];
    foreach ($sql as $statement) db()->exec($statement);

    $count = (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $stmt = db()->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $stmt->execute(['admin', password_hash('ChangeMe123!', PASSWORD_DEFAULT)]);
        $message = 'Installation successful. Login with username admin and password ChangeMe123!. Change this password immediately and delete install.php from your hosting account.';
    } else {
        $message = 'Database tables are ready. An administrator account already exists.';
    }
} catch (Throwable $e) {
    $error = 'Installation failed. Check the database settings in config.php. Details: ' . $e->getMessage();
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Install - Hospital Management</title><link rel="stylesheet" href="style.css"></head>
<body class="install-page"><main class="install-card"><h1>Hospital Management System</h1>
<?php if ($message): ?><div class="notice success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<p>After successful installation, open <a href="index.php">the login page</a>.</p></main></body></html>
