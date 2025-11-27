<?php
// Script untuk menginisialisasi views pada artikel yang sudah ada
include 'config/database.php';

try {
    // Cek apakah kolom views sudah ada
    $stmt = $pdo->query("SHOW COLUMNS FROM artikel LIKE 'views'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Tambahkan kolom views
        $pdo->exec("ALTER TABLE artikel ADD COLUMN views INT DEFAULT 0");
        echo "✓ Kolom 'views' berhasil ditambahkan ke tabel artikel.<br>";
    } else {
        echo "✓ Kolom 'views' sudah ada di tabel artikel.<br>";
    }
    
    // Buat tabel likes dan komentar jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        artikel_id INT NOT NULL,
        user_id INT NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_like (artikel_id, user_id),
        UNIQUE KEY unique_ip_like (artikel_id, ip_address)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS komentar (
        id INT PRIMARY KEY AUTO_INCREMENT,
        artikel_id INT NOT NULL,
        user_id INT NULL,
        nama VARCHAR(100) NULL,
        email VARCHAR(100) NULL,
        komentar TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "✓ Tabel likes dan komentar berhasil dibuat/diverifikasi.<br>";
    
    // Reset views ke 0
    $stmt = $pdo->prepare("UPDATE artikel SET views = 0");
    $stmt->execute();
    echo "✓ Views artikel direset ke 0.<br>";
    
    // Hapus data likes dan komentar yang ada
    $pdo->exec("DELETE FROM likes");
    $pdo->exec("DELETE FROM komentar");
    echo "✓ Data likes dan komentar dihapus (mulai dari 0).<br>";
    
    // Tampilkan statistik
    $stmt = $pdo->query("SELECT COUNT(*) as total, AVG(views) as avg_views, MAX(views) as max_views FROM artikel WHERE status = 'published'");
    $stats = $stmt->fetch();
    
    echo "<br><strong>Statistik Artikel:</strong><br>";
    echo "- Total artikel published: " . $stats['total'] . "<br>";
    echo "- Rata-rata views: " . round($stats['avg_views'], 1) . "<br>";
    echo "- Views tertinggi: " . $stats['max_views'] . "<br>";
    
    echo "<br>✅ Setup selesai! Artikel populer sekarang berdasarkan kombinasi views, likes, dan komentar.";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 600px;
    margin: 50px auto;
    padding: 20px;
    background: #f8f9fa;
}
</style>