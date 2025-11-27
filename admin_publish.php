<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['admin']);

$message = '';

// Proses publish artikel
if ($_POST) {
    $artikel_id = $_POST['artikel_id'];
    $action = $_POST['action'];
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    if ($action === 'publish') {
        $stmt = $pdo->prepare("UPDATE artikel SET status = 'published', tanggal_publish = NOW() WHERE id = ?");
        $stmt->execute([$artikel_id]);
        $message = '<div class="alert alert-success">Artikel berhasil dipublikasikan!</div>';
    } elseif ($action === 'reject') {
        // Set status artikel menjadi rejected
        $stmt = $pdo->prepare("UPDATE artikel SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$rejection_reason, $artikel_id]);
        $message = '<div class="alert alert-success">Artikel ditolak dan dikembalikan ke siswa untuk revisi!</div>';
    }
}

// Ambil artikel yang sudah disetujui guru
$stmt = $pdo->query("
    SELECT a.*, u.nama as penulis, k.nama_kategori 
    FROM artikel a 
    LEFT JOIN users u ON a.user_id = u.id 
    LEFT JOIN kategori k ON a.kategori_id = k.id
    WHERE a.status = 'approved' 
    ORDER BY a.created_at DESC
");
$artikel_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Publish - E-Mading</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h1>E-Mading</h1>
            <div style="color: white; font-size: 14px;">
                <i class="fas fa-user"></i> <?= getUserName() ?> (Admin)
            </div>
        </div>
    </div>

    <div class="sidebar">
        <h2>Publish Artikel</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Publish Artikel</h2>
            <p style="color: #ccc; margin-bottom: 2rem;">Artikel yang sudah disetujui guru dan siap untuk dipublikasikan</p>
            
            <?= $message ?>
            
            <?php if (count($artikel_list) > 0): ?>
            <?php foreach ($artikel_list as $artikel): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <div>
                        <h3><?= htmlspecialchars($artikel['judul']) ?></h3>
                        <p style="color: #ccc; margin: 5px 0;">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($artikel['penulis']) ?> | 
                            <i class="fas fa-tag"></i> <?= htmlspecialchars($artikel['nama_kategori'] ?: 'Tanpa Kategori') ?> |
                            <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($artikel['created_at'])) ?>
                        </p>
                    </div>
                    <span class="badge badge-warning">Siap Publish</span>
                </div>
                
                <div style="max-height: 100px; overflow: hidden; margin-bottom: 15px; color: #ccc;">
                    <?= nl2br(htmlspecialchars(substr($artikel['konten'], 0, 200))) ?>...
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="artikel_id" value="<?= $artikel['id'] ?>">
                        <input type="hidden" name="action" value="publish">
                        <button type="submit" class="btn" style="background: #28a745;" onclick="return confirm('Yakin ingin mempublikasikan artikel ini?')">
                            <i class="fas fa-upload"></i> Publish ke Public
                        </button>
                    </form>
                    
                    <a href="preview_artikel.php?id=<?= $artikel['id'] ?>&from=publish" class="btn btn-sm">
                        <i class="fas fa-eye"></i> Preview
                    </a>
                    
                    <button onclick="showRejectForm(<?= $artikel['id'] ?>)" class="btn" style="background: #dc3545;">
                        <i class="fas fa-times"></i> Tolak
                    </button>
                </div>
                
                <div id="reject-form-<?= $artikel['id'] ?>" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;">
                    <form method="POST">
                        <input type="hidden" name="artikel_id" value="<?= $artikel['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <div class="form-group">
                            <label style="color: #333; margin-bottom: 8px; display: block;">Alasan Penolakan:</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Berikan alasan penolakan..." required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
                        </div>
                        <div style="margin-top: 10px;">
                            <button type="submit" class="btn" style="background: #dc3545; margin-right: 10px;">Tolak & Kembalikan</button>
                            <button type="button" onclick="hideRejectForm(<?= $artikel['id'] ?>)" class="btn" style="background: #6c757d;">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php else: ?>
            <div class="card">
                <p style="text-align: center; color: #ccc;">
                    <i class="fas fa-inbox"></i><br>
                    Tidak ada artikel yang siap untuk dipublikasikan.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function showRejectForm(id) {
        document.getElementById('reject-form-' + id).style.display = 'block';
    }
    
    function hideRejectForm(id) {
        document.getElementById('reject-form-' + id).style.display = 'none';
    }
    </script>
</body>
</html>