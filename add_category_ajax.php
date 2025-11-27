<?php
include 'auth_check.php';
include 'config/database.php';

// Hanya anggota dan admin yang bisa tambah kategori
checkAuth(['anggota', 'admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    
    if (empty($nama_kategori)) {
        echo json_encode(['success' => false, 'message' => 'Nama kategori harus diisi!']);
        exit;
    }
    
    if (strlen($nama_kategori) < 3) {
        echo json_encode(['success' => false, 'message' => 'Nama kategori minimal 3 karakter!']);
        exit;
    }
    
    try {
        // Cek apakah kategori sudah ada
        $stmt = $pdo->prepare("SELECT id FROM kategori WHERE nama_kategori = ?");
        $stmt->execute([$nama_kategori]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Kategori sudah ada!']);
            exit;
        }
        
        // Tambah kategori baru
        $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, deskripsi) VALUES (?, ?)");
        $stmt->execute([$nama_kategori, "Kategori $nama_kategori"]);
        
        $kategori_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'id' => $kategori_id,
            'message' => 'Kategori berhasil ditambahkan!'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error database: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid!']);
}
?>