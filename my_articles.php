<?php
include 'auth_check.php';
include 'config/database.php';

// Hanya siswa dan admin yang bisa akses
checkAuth(['siswa', 'admin']);

$success = '';
if (isset($_GET['success']) && $_GET['success'] == 'sent') {
    $success = 'Artikel berhasil dikirim ke guru untuk review!';
}

// Ambil artikel milik user yang login
try {
    $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori FROM artikel a 
                           LEFT JOIN kategori k ON a.kategori_id = k.id 
                           WHERE a.user_id = ? 
                           ORDER BY a.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $artikel = $stmt->fetchAll();
    
    // Statistik artikel user
    $total = count($artikel);
    $published = count(array_filter($artikel, function($a) { return $a['status'] == 'published'; }));
    $pending = count(array_filter($artikel, function($a) { return $a['status'] == 'pending'; }));
    $approved = count(array_filter($artikel, function($a) { return $a['status'] == 'approved'; }));
    $rejected = count(array_filter($artikel, function($a) { return $a['status'] == 'rejected'; }));
    $draft = count(array_filter($artikel, function($a) { return $a['status'] == 'draft'; }));
    
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $showWelcome ? 'Dashboard Siswa - E-Magazine' : 'Artikel Saya - E-Magazine' ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>


    <!-- Fixed Navbar -->
    <nav style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <a href="logout.php" style="background: rgba(79, 195, 247, 0.9); color: white; padding: 12px 20px; border-radius: 30px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
    
    <!-- Back Button -->
    <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        <a href="profil.php" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 20px; border-radius: 30px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
            <i class="fas fa-arrow-left"></i> Kembali ke Profil
        </a>
    </div>

    <!-- Main Container -->
    <div style="min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 80px 20px 40px;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <!-- Header -->
            <div style="background: white; border-radius: 20px; padding: 40px; margin-bottom: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
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
                        <div style="width: 60px; height: 60px; border-radius: 50%; border: 3px solid #4fc3f7; overflow: hidden; background: linear-gradient(135deg, #4fc3f7, #29b6f6); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <?php if($user_foto && file_exists('uploads/' . $user_foto)): ?>
                                <img src="uploads/<?= $user_foto ?>" alt="Profil" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user" style="font-size: 1.5rem; color: white;"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h1 style="font-size: 2.5rem; font-weight: bold; color: #2c3e50; margin: 0; text-transform: uppercase; letter-spacing: 1px;">ARTIKEL SAYA</h1>
                            <p style="color: #666; margin: 5px 0 0 0; font-size: 1.1rem;"><?= htmlspecialchars($_SESSION['nama']) ?> - <?= ucfirst($_SESSION['role']) ?></p>
                        </div>
                    </div>
                    <a href="tambah_artikel.php" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); color: white; padding: 15px 30px; border-radius: 30px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 10px; box-shadow: 0 5px 15px rgba(79, 195, 247, 0.3);">
                        <i class="fas fa-plus"></i> Tulis Artikel Baru
                    </a>
                </div>
            
            <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
    <?php endif; ?>
            
                <!-- Statistik -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 20px; border-radius: 15px; text-align: center; color: white;">
                        <h3 style="font-size: 2rem; margin: 0; font-weight: bold;"><?= $total ?></h3>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">Total Artikel</p>
                    </div>
                    <div style="background: linear-gradient(135deg, #28a745, #20c997); padding: 20px; border-radius: 15px; text-align: center; color: white;">
                        <h3 style="font-size: 2rem; margin: 0; font-weight: bold;"><?= $published ?></h3>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">Published</p>
                    </div>
                    <div style="background: linear-gradient(135deg, #ffc107, #fd7e14); padding: 20px; border-radius: 15px; text-align: center; color: white;">
                        <h3 style="font-size: 2rem; margin: 0; font-weight: bold;"><?= $pending ?></h3>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">Pending</p>
                    </div>
                    <div style="background: linear-gradient(135deg, #17a2b8, #6f42c1); padding: 20px; border-radius: 15px; text-align: center; color: white;">
                        <h3 style="font-size: 2rem; margin: 0; font-weight: bold;"><?= $approved ?></h3>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">Disetujui</p>
                    </div>
                    <div style="background: linear-gradient(135deg, #dc3545, #e83e8c); padding: 20px; border-radius: 15px; text-align: center; color: white;">
                        <h3 style="font-size: 2rem; margin: 0; font-weight: bold;"><?= $rejected ?></h3>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">Ditolak</p>
                    </div>
                </div>
            </div>
            
            <!-- Daftar Artikel -->
            <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.1);">
                <h2 style="font-size: 1.8rem; font-weight: bold; color: #2c3e50; margin: 0 0 30px 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-list"></i> Daftar Artikel
                </h2>
                <?php if(isset($artikel) && count($artikel) > 0): ?>
                    <div style="display: grid; gap: 20px;">
                        <?php foreach($artikel as $item): ?>
                            <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; border-left: 5px solid <?= $item['status'] === 'published' ? '#28a745' : ($item['status'] === 'pending' ? '#ffc107' : ($item['status'] === 'approved' ? '#17a2b8' : '#dc3545')) ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                    <div style="flex: 1;">
                                        <h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 1.3rem; font-weight: 600;"><?= htmlspecialchars($item['judul']) ?></h3>
                                        <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 10px;">
                                            <span style="background: #e9ecef; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; color: #666;">
                                                <i class="fas fa-folder"></i> <?= htmlspecialchars($item['nama_kategori'] ?? 'Tidak ada') ?>
                                            </span>
                                            <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; background: <?= $item['status'] === 'published' ? '#d4edda' : ($item['status'] === 'pending' ? '#fff3cd' : ($item['status'] === 'approved' ? '#d1ecf1' : '#f8d7da')) ?>; color: <?= $item['status'] === 'published' ? '#155724' : ($item['status'] === 'pending' ? '#856404' : ($item['status'] === 'approved' ? '#0c5460' : '#721c24')) ?>;">
                                                <?= ucfirst($item['status']) ?>
                                            </span>
                                        </div>
                                        <div style="display: flex; gap: 20px; font-size: 0.9rem; color: #666;">
                                            <span><i class="fas fa-calendar-plus"></i> Dibuat: <?= date('d M Y', strtotime($item['created_at'])) ?></span>
                                            <?php if($item['tanggal_publish']): ?>
                                                <span><i class="fas fa-calendar-check"></i> Publish: <?= date('d M Y', strtotime($item['tanggal_publish'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($item['status'] == 'rejected' && !empty($item['rejection_reason'])): ?>
                                            <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border-radius: 8px; border-left: 3px solid #dc3545;">
                                                <small style="color: #721c24; font-weight: 500;">
                                                    <i class="fas fa-exclamation-triangle"></i> Alasan Ditolak: <?= htmlspecialchars($item['rejection_reason']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <?php if(in_array($item['status'], ['draft', 'rejected', 'pending'])): ?>
                                            <a href="edit_artikel.php?id=<?= $item['id'] ?>" style="background: #4fc3f7; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 0.85rem; font-weight: 500;">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if($item['status'] == 'draft' || $item['status'] == 'rejected'): ?>
                                            <form method="POST" action="submit_artikel.php" style="display: inline;">
                                                <input type="hidden" name="artikel_id" value="<?= $item['id'] ?>">
                                                <button type="submit" style="background: #ffc107; color: #000; padding: 8px 16px; border: none; border-radius: 20px; font-size: 0.85rem; font-weight: 500; cursor: pointer;">
                                                    <i class="fas fa-paper-plane"></i> Submit
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if($item['status'] == 'published'): ?>
                                            <a href="view_artikel.php?id=<?= $item['id'] ?>" target="_blank" style="background: #17a2b8; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 0.85rem; font-weight: 500;">
                                                <i class="fas fa-eye"></i> Lihat
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="hapus_artikel.php?id=<?= $item['id'] ?>" onclick="return confirm('Yakin ingin menghapus artikel ini?')" style="background: #dc3545; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 0.85rem; font-weight: 500;">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #e9ecef, #dee2e6); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
                            <i class="fas fa-file-alt" style="font-size: 3rem; color: #6c757d;"></i>
                        </div>
                        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 1.5rem;">Belum Ada Artikel</h3>
                        <p style="color: #666; margin-bottom: 30px; font-size: 1.1rem;">Mulai menulis artikel pertama Anda dan bagikan ide-ide menarik!</p>
                        <a href="tambah_artikel.php" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); color: white; padding: 15px 30px; border-radius: 30px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 5px 15px rgba(79, 195, 247, 0.3);">
                            <i class="fas fa-plus"></i> Tulis Artikel Pertama
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>