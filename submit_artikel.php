<?php
include 'auth_check.php';
include 'config/database.php';

// Hanya siswa dan admin yang bisa submit artikel
checkAuth(['siswa', 'admin']);

if ($_POST && isset($_POST['artikel_id'])) {
    $artikel_id = $_POST['artikel_id'];
    
    // Pastikan artikel milik user yang login
    $stmt = $pdo->prepare("SELECT * FROM artikel WHERE id = ? AND user_id = ?");
    $stmt->execute([$artikel_id, getUserId()]);
    $artikel = $stmt->fetch();
    
    if ($artikel && in_array($artikel['status'], ['draft', 'rejected'])) {
        // Update status ke pending
        $stmt = $pdo->prepare("UPDATE artikel SET status = 'pending' WHERE id = ?");
        $stmt->execute([$artikel_id]);
        
        header('Location: dashboard_siswa.php?success=sent');
        exit;
    }
}

header('Location: my_articles.php?error=invalid');
exit;
?>