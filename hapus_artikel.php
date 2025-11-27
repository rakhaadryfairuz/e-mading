<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['siswa', 'admin']);

$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

try {
    // Ambil data artikel untuk validasi dan hapus gambar
    $stmt = $pdo->prepare("SELECT * FROM artikel WHERE id = ?");
    $stmt->execute([$id]);
    $artikel = $stmt->fetch();
    
    if ($artikel) {
        // Cek apakah user berhak menghapus artikel ini
        if ($user_role === 'admin' || $artikel['user_id'] == $user_id) {
            // Hapus gambar jika ada
            if ($artikel['gambar'] && file_exists("uploads/" . $artikel['gambar'])) {
                unlink("uploads/" . $artikel['gambar']);
            }
            
            // Hapus artikel dari database
            $stmt = $pdo->prepare("DELETE FROM artikel WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "Artikel berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Anda tidak memiliki izin untuk menghapus artikel ini!";
        }
    } else {
        $_SESSION['error'] = "Artikel tidak ditemukan!";
    }
} catch(Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

// Check if request comes from revisi page
$from_revisi = $_GET['from'] ?? '';

if ($from_revisi === 'revisi') {
    // No redirect, just return success response
    echo '<script>window.history.back();</script>';
} else {
    // Normal redirect based on role
    if ($user_role === 'admin') {
        header('Location: artikel.php');
    } else {
        header('Location: my_articles.php');
    }
}
exit;
?>