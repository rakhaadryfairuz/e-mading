<?php
include 'config/database.php';

try {
    // Buat tabel likes
    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        artikel_id INT NOT NULL,
        user_id INT NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_like (artikel_id, user_id),
        UNIQUE KEY unique_ip_like (artikel_id, ip_address)
    )");
    
    echo "✅ Tabel likes berhasil dibuat!";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>