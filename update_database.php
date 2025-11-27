<?php
include 'config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Database - E-Magazine</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <h2>Update Database Schema</h2>
<?php

try {
    // Cek apakah kolom email sudah ada di tabel users
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE AFTER username");
        echo "<p class='success'>✅ Kolom email berhasil ditambahkan ke tabel users</p>";
    } else {
        echo "<p class='info'>ℹ️ Kolom email sudah ada di tabel users</p>";
    }
    
    // Cek apakah kolom status sudah ada di tabel users
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER role");
        echo "<p class='success'>✅ Kolom status berhasil ditambahkan ke tabel users</p>";
    } else {
        echo "<p class='info'>ℹ️ Kolom status sudah ada di tabel users</p>";
    }
    
    // Update enum role untuk menambahkan 'pending' dan 'siswa'
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'guru', 'siswa', 'anggota', 'pending') DEFAULT 'pending'");
        echo "<p class='success'>✅ Enum role berhasil diupdate (admin, guru, siswa, anggota, pending)</p>";
    } catch(Exception $e) {
        echo "<p class='warning'>⚠️ Error updating role enum: " . $e->getMessage() . "</p>";
    }
    
    // Cek apakah tabel likes sudah ada
    $stmt = $pdo->query("SHOW TABLES LIKE 'likes'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            artikel_id INT NOT NULL,
            user_id INT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_like (artikel_id, user_id),
            UNIQUE KEY unique_ip_like (artikel_id, ip_address)
        )");
        echo "<p class='success'>✅ Tabel likes berhasil dibuat</p>";
    } else {
        echo "<p class='info'>ℹ️ Tabel likes sudah ada</p>";
    }
    
    // Cek apakah tabel komentar sudah ada
    $stmt = $pdo->query("SHOW TABLES LIKE 'komentar'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE komentar (
            id INT AUTO_INCREMENT PRIMARY KEY,
            artikel_id INT NOT NULL,
            user_id INT NULL,
            nama VARCHAR(255) NULL,
            email VARCHAR(255) NULL,
            komentar TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        echo "<p class='success'>✅ Tabel komentar berhasil dibuat</p>";
    } else {
        echo "<p class='info'>ℹ️ Tabel komentar sudah ada</p>";
    }
    
    echo "<h3 class='success'>Database Update Selesai!</h3>";
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='register.php' class='btn'>← Kembali ke Register</a>";
    echo "<a href='login.php' class='btn'>Login</a>";
    echo "<a href='test_system.php' class='btn'>Test System</a>";
    echo "</div>";
    
} catch(Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
</body>
</html>
<?php
?>