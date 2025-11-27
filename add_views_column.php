<?php
// Script untuk menambahkan kolom views ke tabel artikel
include 'config/database.php';

try {
    // Cek apakah kolom views sudah ada
    $stmt = $pdo->query("SHOW COLUMNS FROM artikel LIKE 'views'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Tambahkan kolom views
        $pdo->exec("ALTER TABLE artikel ADD COLUMN views INT DEFAULT 0");
        echo "Kolom 'views' berhasil ditambahkan ke tabel artikel.<br>";
        
        // Update views dengan nilai random untuk artikel yang sudah ada (simulasi)
        $pdo->exec("UPDATE artikel SET views = FLOOR(RAND() * 100) + 1 WHERE views IS NULL OR views = 0");
        echo "Nilai views berhasil diinisialisasi untuk artikel yang sudah ada.<br>";
    } else {
        echo "Kolom 'views' sudah ada di tabel artikel.<br>";
    }
    
    echo "Setup selesai!";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>