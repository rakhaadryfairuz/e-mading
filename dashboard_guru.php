<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['guru']);

// Ambil statistik untuk guru
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status = 'pending'");
    $artikel_pending = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status = 'approved'");
    $artikel_approved = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status = 'published'");
    $artikel_published = $stmt->fetchColumn();
    
    // Artikel yang perlu direview
    $stmt = $pdo->query("SELECT a.*, u.nama FROM artikel a 
                         LEFT JOIN users u ON a.user_id = u.id 
                         WHERE a.status = 'pending'
                         ORDER BY a.created_at DESC LIMIT 5");
    $artikel_review = $stmt->fetchAll();
    
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - E-Magazine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h1>Dashboard Guru</h1>
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
        <h2>Menu Guru</h2>
        <ul>
            <li><a href="dashboard_guru.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="moderasi.php"><i class="fas fa-shield-alt"></i> Moderasi Artikel</a></li>
            <li><a href="public.php"><i class="fas fa-eye"></i> Lihat Public</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Dashboard Guru</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card" style="--delay: 0;">
                    <h3><?= $artikel_pending ?? 0 ?></h3>
                    <p>Artikel Pending Review</p>
                </div>
                <div class="stat-card" style="--delay: 1;">
                    <h3><?= $artikel_approved ?? 0 ?></h3>
                    <p>Artikel Disetujui</p>
                </div>
                <div class="stat-card" style="--delay: 2;">
                    <h3><?= $artikel_published ?? 0 ?></h3>
                    <p>Artikel Published</p>
                </div>
            </div>

            <!-- Artikel Perlu Review -->
            <div class="card" style="--delay: 3;">
                <h3><i class="fas fa-clipboard-check"></i> Artikel Perlu Review</h3>
                <?php if(isset($artikel_review) && count($artikel_review) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Penulis</th>
                            <th>Tanggal Submit</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($artikel_review as $artikel): ?>
                        <tr>
                            <td><?= htmlspecialchars($artikel['judul']) ?></td>
                            <td><?= htmlspecialchars($artikel['nama']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($artikel['created_at'])) ?></td>
                            <td>
                                <a href="moderasi.php?id=<?= $artikel['id'] ?>" class="btn btn-sm">
                                    <i class="fas fa-eye"></i> Review
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #28a745; margin-bottom: 20px;"></i>
                    <h3 style="color: #666; margin-bottom: 10px;">Tidak Ada Artikel Pending</h3>
                    <p style="color: #999;">Semua artikel sudah direview</p>
                </div>
                <?php endif; ?>
            </div>


        </div>
    </div>
</body>
</html>