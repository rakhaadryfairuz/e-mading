<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['guru', 'admin']);

$artikel_id = $_GET['id'] ?? 0;
$from = $_GET['from'] ?? 'moderasi';

$stmt = $pdo->prepare("SELECT a.*, k.nama_kategori, u.nama as penulis FROM artikel a 
                       LEFT JOIN kategori k ON a.kategori_id = k.id
                       LEFT JOIN users u ON a.user_id = u.id 
                       WHERE a.id = ?");
$stmt->execute([$artikel_id]);
$artikel = $stmt->fetch();

if (!$artikel) {
    $backUrl = ($from === 'publish') ? 'admin_publish.php' : 'moderasi.php';
    header('Location: ' . $backUrl);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?= htmlspecialchars($artikel['judul']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h1><i class="fas fa-eye"></i> Preview Artikel</h1>
            <?php 
            $backUrl = ($from === 'publish') ? 'admin_publish.php' : 'moderasi.php';
            $backText = ($from === 'publish') ? 'Kembali ke Publish' : 'Kembali ke Moderasi';
            ?>
            <a href="<?= $backUrl ?>" class="btn" style="background: #6c757d;">
                <i class="fas fa-arrow-left"></i> <?= $backText ?>
            </a>
        </div>
    </div>

    <div style="max-width: 800px; margin: 120px auto 2rem; padding: 20px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <div style="padding: 20px;">
            
            <div style="background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%); padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #4fc3f7;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; color: #0277bd;">
                    <div><i class="fas fa-user"></i> <?= htmlspecialchars($artikel['penulis']) ?></div>
                    <div><i class="fas fa-tag"></i> <?= htmlspecialchars($artikel['nama_kategori'] ?? 'Tanpa Kategori') ?></div>
                    <div><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($artikel['created_at'])) ?></div>
                    <div><span style="background: #ffc107; color: #000; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85rem;">PREVIEW</span></div>
                </div>
            </div>
            
            <h1 style="color: #0277bd; margin-bottom: 25px; font-size: 2.5rem; line-height: 1.3; text-align: center;"><?= htmlspecialchars($artikel['judul']) ?></h1>
            
            <?php if($artikel['gambar']): ?>
            <div style="margin-bottom: 25px; text-align: center;">
                <?php if(file_exists('uploads/' . $artikel['gambar'])): ?>
                <img src="uploads/<?= $artikel['gambar'] ?>" alt="<?= htmlspecialchars($artikel['judul']) ?>" 
                     style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.1);"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display: none; background: #f8f9fa; padding: 40px; border-radius: 12px; border: 2px dashed #ddd; color: #666;">
                    <i class="fas fa-image" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i><br>
                    <strong>Gambar tidak dapat dimuat</strong><br>
                    <small>File: <?= htmlspecialchars($artikel['gambar']) ?></small>
                </div>
                <?php else: ?>
                <div style="background: #f8f9fa; padding: 40px; border-radius: 12px; border: 2px dashed #ddd; color: #666;">
                    <i class="fas fa-image" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i><br>
                    <strong>File gambar tidak ditemukan</strong><br>
                    <small>File: <?= htmlspecialchars($artikel['gambar']) ?></small>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid #e3f2fd; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="line-height: 1.8; color: #2c3e50; font-size: 16px; text-align: justify;">
                    <?= nl2br(htmlspecialchars($artikel['konten'])) ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>