<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['siswa']);

// Add rejection_reason column if it doesn't exist
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM artikel LIKE 'rejection_reason'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE artikel ADD COLUMN rejection_reason TEXT NULL");
    }
} catch(Exception $e) {
    // Column might already exist
}

// Ambil artikel yang ditolak milik siswa ini
try {
    $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori FROM artikel a 
                          LEFT JOIN kategori k ON a.kategori_id = k.id 
                          WHERE a.user_id = ? AND a.status = 'rejected'
                          ORDER BY a.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $artikel_rejected = $stmt->fetchAll();
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisi Artikel - E-Magazine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h1>Revisi Artikel</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="profil.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: white; font-size: 14px;">
                    <i class="fas fa-user"></i>
                    <?= getUserName() ?> (<?= ucfirst(getUserRole()) ?>)
                </a>
                <a href="logout.php" style="background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <h2>Menu Siswa</h2>
        <ul>
            <li><a href="dashboard_siswa.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="tambah_artikel.php"><i class="fas fa-plus"></i> Tulis Artikel</a></li>
            <li><a href="revisi_artikel.php" class="active"><i class="fas fa-edit"></i> Revisi Artikel</a></li>
            <li><a href="public.php"><i class="fas fa-eye"></i> Lihat Public</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Artikel Perlu Revisi</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(isset($artikel_rejected) && count($artikel_rejected) > 0): ?>
            <div class="card" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 50%, #fff3cd 100%); border: 1px solid rgba(255, 193, 7, 0.3); margin-bottom: 2rem;">
                <h3 style="color: #856404; margin-bottom: 10px;">
                    <i class="fas fa-exclamation-triangle"></i> Artikel Ditolak - Perlu Revisi
                </h3>
                <p style="color: #856404; margin: 0;">
                    Artikel di bawah ini ditolak oleh guru dan perlu direvisi. Silakan baca alasan penolakan dan lakukan perbaikan sesuai saran.
                </p>
            </div>

            <?php foreach($artikel_rejected as $artikel): ?>
            <div class="card" style="border-left: 4px solid #dc3545;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <div>
                        <h3 style="color: #dc3545;"><?= htmlspecialchars($artikel['judul']) ?></h3>
                        <p style="color: #666; margin: 5px 0;">
                            <i class="fas fa-tag"></i> <?= htmlspecialchars($artikel['nama_kategori'] ?? 'Tanpa Kategori') ?> |
                            <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($artikel['created_at'])) ?>
                        </p>
                    </div>
                    <span class="badge" style="background: #dc3545;">Ditolak</span>
                </div>
                
                <?php if($artikel['rejection_reason']): ?>
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin-bottom: 15px;">
                    <h4 style="color: #721c24; margin-bottom: 10px;">
                        <i class="fas fa-comment-alt"></i> Alasan Penolakan:
                    </h4>
                    <p style="color: #721c24; margin: 0; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($artikel['rejection_reason'])) ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <div style="max-height: 150px; overflow: hidden; margin-bottom: 15px; color: #666;">
                    <?= nl2br(htmlspecialchars(substr($artikel['konten'], 0, 300))) ?>...
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <a href="edit_artikel.php?id=<?= $artikel['id'] ?>" class="btn" style="background: #28a745;">
                        <i class="fas fa-edit"></i> Revisi Artikel
                    </a>
                    <a href="hapus_artikel.php?id=<?= $artikel['id'] ?>&from=revisi" class="btn" style="background: #dc3545;" onclick="return confirm('Yakin ingin menghapus artikel ini?')">
                        <i class="fas fa-trash"></i> Hapus
                    </a>
                </div>
            </div>
            <?php endforeach; ?>

            <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #28a745; margin-bottom: 20px;"></i>
                <h3 style="color: #666; margin-bottom: 10px;">Tidak Ada Artikel yang Perlu Direvisi</h3>
                <p style="color: #999; margin-bottom: 20px;">Semua artikel Anda dalam kondisi baik atau belum ada yang ditolak</p>
                <a href="tambah_artikel.php" class="btn" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6);">
                    <i class="fas fa-plus"></i> Tulis Artikel Baru
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>