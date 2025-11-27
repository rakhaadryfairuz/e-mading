<?php
include 'auth_check.php';
include 'config/database.php';

// Cek login
checkAuth();

// Redirect user non-admin ke dashboard mereka masing-masing
if (getUserRole() !== 'admin') {
    redirectToDashboard();
}

// Pesan error jika ada
$message = '';
if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'access_denied':
            $message = '<div class="alert alert-danger">Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman tersebut.</div>';
            break;
    }
}

// Ambil statistik
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM artikel");
    $total_artikel = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status = 'published'");
    $artikel_published = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM kategori");
    $total_kategori = $stmt->fetchColumn();
    
    // Artikel terbaru (1 minggu terakhir)
    $stmt = $pdo->query("SELECT a.*, k.nama_kategori, u.nama FROM artikel a 
                         LEFT JOIN kategori k ON a.kategori_id = k.id 
                         LEFT JOIN users u ON a.user_id = u.id 
                         WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
                         ORDER BY a.created_at DESC");
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
    <title>Dashboard Admin - E-Magazine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h1>E-Mading Admin</h1>
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

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Dashboard</h2>
        <ul>
            <li><a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            
            <?php if (canCreateArticle() && getUserRole() !== 'admin'): ?>
            <li><a href="tambah_artikel.php"><i class="fas fa-plus"></i> Tulis Artikel</a></li>
            <?php endif; ?>
            
            <?php if (canApproveArticle()): ?>
            <li><a href="moderasi.php"><i class="fas fa-shield-alt"></i> Moderasi Artikel</a></li>
            <?php endif; ?>
            
            <?php if (getUserRole() === 'admin'): ?>
            <li><a href="admin_publish.php"><i class="fas fa-upload"></i> Publish Artikel</a></li>
            <li><a href="artikel.php"><i class="fas fa-newspaper"></i> Semua Artikel</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <?php endif; ?>

        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Dashboard Overview</h2>
            
            <?= $message ?>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card" style="--delay: 0;">
                    <h3><?= $total_artikel ?? 0 ?></h3>
                    <p>Total Artikel</p>
                </div>
                <div class="stat-card" style="--delay: 1;">
                    <h3><?= $artikel_published ?? 0 ?></h3>
                    <p>Artikel Published</p>
                </div>
                <div class="stat-card" style="--delay: 2;">
                    <h3><?= $total_users ?? 0 ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card" style="--delay: 3;">
                    <h3><?= $total_kategori ?? 0 ?></h3>
                    <p>Kategori</p>
                </div>
            </div>

            <!-- Recent Articles -->
            <div class="card" style="--delay: 4;">
                <h3><i class="fas fa-newspaper"></i> Artikel Terbaru</h3>
                <?php if(isset($artikel_terbaru) && count($artikel_terbaru) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Penulis</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(array_slice($artikel_terbaru, 0, 5) as $artikel): ?>
                        <tr class="article-row">
                            <td><?= htmlspecialchars($artikel['judul']) ?></td>
                            <td><?= htmlspecialchars($artikel['nama_kategori'] ?? 'Tidak ada') ?></td>
                            <td><?= htmlspecialchars($artikel['nama']) ?></td>
                            <td>
                                <span class="badge <?= $artikel['status'] == 'published' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= ucfirst($artikel['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($artikel['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(count($artikel_terbaru) > 5): ?>
                        <?php foreach(array_slice($artikel_terbaru, 5) as $artikel): ?>
                        <tr class="article-row hidden-row" style="display: none;">
                            <td><?= htmlspecialchars($artikel['judul']) ?></td>
                            <td><?= htmlspecialchars($artikel['nama_kategori'] ?? 'Tidak ada') ?></td>
                            <td><?= htmlspecialchars($artikel['nama']) ?></td>
                            <td>
                                <span class="badge <?= $artikel['status'] == 'published' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= ucfirst($artikel['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($artikel['created_at'])) ?></td>
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
                <p>Belum ada artikel.</p>
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
    <script src="assets/js/main.js"></script>
</body>
</html>