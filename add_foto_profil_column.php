<?php
include 'config/database.php';

try {
    // Cek apakah kolom foto_profil sudah ada
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'foto_profil'");
    if ($stmt->rowCount() == 0) {
        // Tambah kolom foto_profil jika belum ada
        $pdo->exec("ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) NULL");
        echo "Kolom foto_profil berhasil ditambahkan ke tabel users.";
    } else {
        echo "Kolom foto_profil sudah ada di tabel users.";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>