<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['siswa']);

$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'sent' || $_GET['success'] == 'submitted') {
        $success = 'Artikel berhasil dikirim ke guru untuk review!';
    }
}

// Ambil artikel siswa ini
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM artikel WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $artikel_saya = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM artikel WHERE user_id = ? AND status = 'published'");
    $stmt->execute([$_SESSION['user_id']]);
    $artikel_published = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM artikel WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $artikel_pending = $stmt->fetchColumn();
    
    // Semua artikel siswa untuk dashboard
    $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori FROM artikel a 
                          LEFT JOIN kategori k ON a.kategori_id = k.id 
                          WHERE a.user_id = ? 
                          ORDER BY a.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $artikel_terbaru = $stmt->fetchAll();
    
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - E-Magazine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h1>Dashboard Siswa</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php
                // Ambil foto profil user
                try {
                    $stmt = $pdo->prepare("SELECT foto_profil FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user_foto = $stmt->fetchColumn();
                } catch(Exception $e) {
                    $user_foto = null;
                }
                ?>
                <a href="profil.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: white; font-size: 14px; transition: opacity 0.3s ease;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                    <?php if($user_foto && file_exists('uploads/' . $user_foto)): ?>
                        <img src="uploads/<?= $user_foto ?>" alt="Profil" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.5);">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                    <?= getUserName() ?> (<?= ucfirst(getUserRole()) ?>)
                </a>
                <a href="logout.php" style="background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 6px; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <h2>Menu Siswa</h2>
        <ul>
            <li><a href="dashboard_siswa.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="tambah_artikel.php"><i class="fas fa-plus"></i> Tulis Artikel</a></li>
            <li><a href="revisi_artikel.php"><i class="fas fa-edit"></i> Revisi Artikel</a></li>
            <li><a href="public.php"><i class="fas fa-eye"></i> Lihat Public</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Dashboard Siswa</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
            <!-- Success Message -->
            <div class="card" style="background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 50%, #e8f5e8 100%); border: 1px solid rgba(76, 175, 80, 0.2); margin-bottom: 2rem;">
                <h3 style="color: #2e7d32; margin-bottom: 10px;">
                    <i class="fas fa-check-circle"></i> Selamat! Artikel Berhasil Dikirim
                </h3>
                <p style="color: #388e3c; margin: 0;">
                    <?= htmlspecialchars($success) ?> Anda dapat melanjutkan menulis artikel lain atau melihat status artikel Anda di bawah ini.
                </p>
            </div>
            <?php else: ?>
            <!-- Welcome Message -->
            <div class="card" style="background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 50%, #e1f5fe 100%); border: 1px solid rgba(79, 195, 247, 0.2); margin-bottom: 2rem;">
                <h3 style="color: #0277bd; margin-bottom: 10px;">
                    <i class="fas fa-hand-wave"></i> Selamat Datang, <?= getUserName() ?>!
                </h3>
                <p style="color: #666; margin: 0;">
                    Sebagai siswa, Anda dapat menulis artikel dan mengirimkannya untuk direview oleh guru. 
                    Artikel yang disetujui akan dipublikasikan di halaman public.
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card" style="--delay: 0;">
                    <h3><?= $artikel_saya ?? 0 ?></h3>
                    <p>Total Artikel Saya</p>
                </div>
                <div class="stat-card" style="--delay: 1;">
                    <h3><?= $artikel_published ?? 0 ?></h3>
                    <p>Artikel Published</p>
                </div>
                <div class="stat-card" style="--delay: 2;">
                    <h3><?= $artikel_pending ?? 0 ?></h3>
                    <p>Artikel Pending Review</p>
                </div>
            </div>

            <!-- Artikel Terbaru Saya -->
            <div class="card" style="--delay: 3;">
                <h3><i class="fas fa-newspaper"></i> Artikel Terbaru Saya</h3>
                <?php if(isset($artikel_terbaru) && count($artikel_terbaru) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="articleTableBody">
                        <?php foreach(array_slice($artikel_terbaru, 0, 5) as $artikel): ?>
                        <tr class="article-row">
                            <td><?= htmlspecialchars($artikel['judul']) ?></td>
                            <td><?= htmlspecialchars($artikel['nama_kategori'] ?? 'Tidak ada') ?></td>
                            <td>
                                <span class="badge <?= $artikel['status'] == 'published' ? 'badge-success' : ($artikel['status'] == 'pending' ? 'badge-warning' : 'badge-info') ?>">
                                    <?= ucfirst($artikel['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($artikel['created_at'])) ?></td>
                            <td>
                                <?php if(in_array($artikel['status'], ['draft', 'rejected', 'pending'])): ?>
                                <a href="edit_artikel.php?id=<?= $artikel['id'] ?>" class="btn btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php endif; ?>
                                
                                <?php if($artikel['status'] == 'published'): ?>
                                <a href="view_artikel.php?id=<?= $artikel['id'] ?>" target="_blank" class="btn btn-sm" style="background: #17a2b8; margin-left: 5px;">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                                <?php endif; ?>
                                
                                <!-- Siswa bisa hapus semua artikel miliknya -->
                                <a href="hapus_artikel.php?id=<?= $artikel['id'] ?>" class="btn btn-sm" style="background: #dc3545; margin-left: 5px;" onclick="return confirm('Yakin ingin menghapus artikel ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(count($artikel_terbaru) > 5): ?>
                        <?php foreach(array_slice($artikel_terbaru, 5) as $artikel): ?>
                        <tr class="article-row hidden-row" style="display: none;">
                            <td><?= htmlspecialchars($artikel['judul']) ?></td>
                            <td><?= htmlspecialchars($artikel['nama_kategori'] ?? 'Tidak ada') ?></td>
                            <td>
                                <span class="badge <?= $artikel['status'] == 'published' ? 'badge-success' : ($artikel['status'] == 'pending' ? 'badge-warning' : 'badge-info') ?>">
                                    <?= ucfirst($artikel['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($artikel['created_at'])) ?></td>
                            <td>
                                <?php if(in_array($artikel['status'], ['draft', 'rejected', 'pending'])): ?>
                                <a href="edit_artikel.php?id=<?= $artikel['id'] ?>" class="btn btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php endif; ?>
                                
                                <?php if($artikel['status'] == 'published'): ?>
                                <a href="view_artikel.php?id=<?= $artikel['id'] ?>" target="_blank" class="btn btn-sm" style="background: #17a2b8; margin-left: 5px;">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                                <?php endif; ?>
                                
                                <a href="hapus_artikel.php?id=<?= $artikel['id'] ?>" class="btn btn-sm" style="background: #dc3545; margin-left: 5px;" onclick="return confirm('Yakin ingin menghapus artikel ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div style="text-align: center; margin-top: 20px;">
                    <?php if(count($artikel_terbaru) > 5): ?>
                    <button onclick="toggleAllArticles()" class="btn" id="toggleBtn">
                        <i class="fas fa-chevron-down"></i> Lihat Semua (<?= count($artikel_terbaru) - 5 ?> lainnya)
                    </button>
                    <?php else: ?>
                    <span style="color: #666; font-size: 14px;">Total: <?= count($artikel_terbaru) ?> artikel</span>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-pen" style="font-size: 4rem; color: #4fc3f7; margin-bottom: 20px;"></i>
                    <h3 style="color: #666; margin-bottom: 10px;">Belum Ada Artikel</h3>
                    <p style="color: #999; margin-bottom: 20px;">Mulai menulis artikel pertama Anda</p>
                    <a href="tambah_artikel.php" class="btn" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); margin-top: 10px;">
                        <i class="fas fa-plus"></i> Tulis Artikel Pertama
                    </a>
                </div>
                <?php endif; ?>
            </div>


        </div>
    </div>
    
    <script>
    function toggleAllArticles() {
        const hiddenRows = document.querySelectorAll('.hidden-row');
        const toggleBtn = document.getElementById('toggleBtn');
        const totalHidden = <?= max(0, count($artikel_terbaru) - 5) ?>;
        
        if (hiddenRows[0].style.display === 'none') {
            hiddenRows.forEach(row => row.style.display = 'table-row');
            toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Sembunyikan';
        } else {
            hiddenRows.forEach(row => row.style.display = 'none');
            toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Lihat Semua (' + totalHidden + ' lainnya)';
        }
    }
    </script>
</body>
</html>