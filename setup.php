<?php
include 'config/database.php';

try {
    // Drop foreign key constraints first
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop tables if exist
    $pdo->exec("DROP TABLE IF EXISTS likes");
    $pdo->exec("DROP TABLE IF EXISTS komentar");
    $pdo->exec("DROP TABLE IF EXISTS artikel");
    $pdo->exec("DROP TABLE IF EXISTS galeri");
    $pdo->exec("DROP TABLE IF EXISTS prestasi");
    $pdo->exec("DROP TABLE IF EXISTS lomba");
    $pdo->exec("DROP TABLE IF EXISTS kategori");
    $pdo->exec("DROP TABLE IF EXISTS users");
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Buat tabel users
    $pdo->exec("CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nama VARCHAR(100) NOT NULL,
        role ENUM('admin', 'anggota', 'guru') NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Buat tabel kategori
    $pdo->exec("CREATE TABLE kategori (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama_kategori VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Buat tabel artikel
    $pdo->exec("CREATE TABLE artikel (
        id INT PRIMARY KEY AUTO_INCREMENT,
        judul VARCHAR(255) NOT NULL,
        konten TEXT NOT NULL,
        gambar VARCHAR(255),
        kategori_id INT,
        user_id INT,
        status ENUM('draft', 'pending', 'approved', 'rejected', 'published') DEFAULT 'draft',
        approved_by INT NULL,
        approved_at TIMESTAMP NULL,
        rejection_reason TEXT NULL,
        tanggal_publish DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Buat tabel galeri
    $pdo->exec("CREATE TABLE galeri (
        id INT PRIMARY KEY AUTO_INCREMENT,
        judul VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        foto VARCHAR(255) NOT NULL,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Buat tabel prestasi
    $pdo->exec("CREATE TABLE prestasi (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama_siswa VARCHAR(100) NOT NULL,
        kelas VARCHAR(50) NOT NULL,
        prestasi VARCHAR(255) NOT NULL,
        tingkat ENUM('Sekolah', 'Kecamatan', 'Kabupaten', 'Provinsi', 'Nasional', 'Internasional') NOT NULL,
        tanggal DATE NOT NULL,
        deskripsi TEXT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Buat tabel lomba
    $pdo->exec("CREATE TABLE lomba (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama_lomba VARCHAR(255) NOT NULL,
        penyelenggara VARCHAR(255) NOT NULL,
        tanggal_mulai DATE NOT NULL,
        tanggal_selesai DATE NOT NULL,
        deskripsi TEXT NOT NULL,
        link_pendaftaran VARCHAR(500),
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Buat tabel komentar
    $pdo->exec("CREATE TABLE komentar (
        id INT PRIMARY KEY AUTO_INCREMENT,
        artikel_id INT NOT NULL,
        user_id INT,
        nama VARCHAR(100),
        email VARCHAR(100),
        komentar TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Buat tabel likes
    $pdo->exec("CREATE TABLE likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        artikel_id INT NOT NULL,
        user_id INT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Insert data awal
    $pdo->exec("INSERT INTO users (username, password, nama, role, status) VALUES 
        ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active'),
        ('anggota1', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anggota Satu', 'anggota', 'active'),
        ('guru1', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Guru Satu', 'guru', 'active')");
    
    $pdo->exec("INSERT INTO kategori (nama_kategori, deskripsi) VALUES 
        ('Berita', 'Berita terkini sekolah'),
        ('Prestasi', 'Prestasi siswa dan sekolah'),
        ('Kegiatan', 'Kegiatan sekolah'),
        ('Opini', 'Artikel opini')");
    
    // Insert artikel contoh
    $pdo->exec("INSERT INTO artikel (judul, konten, gambar, kategori_id, user_id, status, tanggal_publish) VALUES 
        ('Perlombaan Badminton Tingkat SMA/SMK Se-Jawa Barat 2024', 
         'BANDUNG - Dinas Pendidikan Provinsi Jawa Barat menggelar Perlombaan Badminton Tingkat SMA/SMK Se-Jawa Barat 2024 yang akan dilaksanakan pada tanggal 15-20 Maret 2024 di GOR Pajajaran Bandung.', 
         'badminton.jpg', 2, 1, 'published', NOW()),
        ('Kegiatan Ekstrakurikuler Robotika', 
         'Ekstrakurikuler robotika sekolah mengadakan workshop pembuatan robot sederhana untuk siswa kelas X dan XI.', 
         NULL, 3, 2, 'pending', NULL),
        ('Prestasi Siswa dalam Olimpiade Matematika', 
         'Siswa kelas XII berhasil meraih medali emas dalam Olimpiade Matematika tingkat provinsi.', 
         NULL, 2, 2, 'draft', NULL)");
    
    echo "<div style='text-align: center; padding: 50px; font-family: Arial;'>";
    echo "<h2 style='color: #667eea;'>✅ Database setup berhasil!</h2>";
    echo "<p>Semua tabel telah dibuat dan data contoh telah ditambahkan.</p>";
    echo "<p><strong>Login:</strong> admin | <strong>Password:</strong> password</p>";
    echo "<a href='index.php' style='background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 10px 20px; text-decoration: none; border-radius: 25px; margin: 10px;'>Dashboard</a>";
    echo "<a href='public.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 25px; margin: 10px;'>Lihat Public</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='text-align: center; padding: 50px; font-family: Arial;'>";
    echo "<h2 style='color: #ef4444;'>❌ Error: " . $e->getMessage() . "</h2>";
    echo "<a href='setup.php' style='background: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 25px;'>Coba Lagi</a>";
    echo "</div>";
}
?>