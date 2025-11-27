<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle tambah lomba
if ($_POST && isset($_POST['tambah'])) {
    $nama_lomba = $_POST['nama_lomba'];
    $penyelenggara = $_POST['penyelenggara'];
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $deskripsi = $_POST['deskripsi'];
    $link_pendaftaran = $_POST['link_pendaftaran'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO lomba (nama_lomba, penyelenggara, tanggal_mulai, tanggal_selesai, deskripsi, link_pendaftaran, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama_lomba, $penyelenggara, $tanggal_mulai, $tanggal_selesai, $deskripsi, $link_pendaftaran, $_SESSION['user_id']]);
        $success = "Lomba berhasil ditambahkan!";
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil semua lomba
try {
    $stmt = $pdo->query("SELECT l.*, u.nama as input_by FROM lomba l 
                         LEFT JOIN users u ON l.user_id = u.id 
                         ORDER BY l.tanggal_mulai DESC");
    $lomba = $stmt->fetchAll();
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Info Lomba - E-Mading</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>E-Mading</h1>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Dashboard</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="artikel.php"><i class="fas fa-newspaper"></i> Artikel</a></li>
            <li><a href="kategori.php"><i class="fas fa-tags"></i> Kategori</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="galeri.php"><i class="fas fa-images"></i> Galeri</a></li>
            <li><a href="lomba.php" class="active"><i class="fas fa-trophy"></i> Lomba</a></li>
            <li><a href="prestasi.php"><i class="fas fa-award"></i> Prestasi</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Informasi Lomba</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <!-- Form Tambah Lomba -->
            <div class="card">
                <h3><i class="fas fa-plus"></i> Tambah Info Lomba</h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Nama Lomba</label>
                            <input type="text" name="nama_lomba" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Penyelenggara</label>
                            <input type="text" name="penyelenggara" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Link Pendaftaran (Opsional)</label>
                        <input type="url" name="link_pendaftaran" class="form-control" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="4" placeholder="Deskripsi lomba..." required></textarea>
                    </div>
                    <button type="submit" name="tambah" class="btn">
                        <i class="fas fa-save"></i> Simpan Lomba
                    </button>
                </form>
            </div>

            <!-- Daftar Lomba -->
            <div class="card">
                <h3><i class="fas fa-trophy"></i> Daftar Lomba</h3>
                <?php if(isset($lomba) && count($lomba) > 0): ?>
                <div style="display: grid; gap: 20px;">
                    <?php foreach($lomba as $item): ?>
                    <div style="background: #222; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                        <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 10px;">
                            <h4 style="color: #667eea; margin: 0;"><?= htmlspecialchars($item['nama_lomba']) ?></h4>
                            <small style="color: #999;">
                                <?= date('d/m/Y', strtotime($item['tanggal_mulai'])) ?> - 
                                <?= date('d/m/Y', strtotime($item['tanggal_selesai'])) ?>
                            </small>
                        </div>
                        <p style="color: #ccc; margin: 10px 0;"><strong>Penyelenggara:</strong> <?= htmlspecialchars($item['penyelenggara']) ?></p>
                        <p style="color: #ddd; margin: 10px 0;"><?= htmlspecialchars($item['deskripsi']) ?></p>
                        <?php if($item['link_pendaftaran']): ?>
                        <a href="<?= $item['link_pendaftaran'] ?>" target="_blank" class="btn btn-sm" style="margin-top: 10px;">
                            <i class="fas fa-external-link-alt"></i> Link Pendaftaran
                        </a>
                        <?php endif; ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #333;">
                            <small style="color: #999;">
                                Diinput oleh: <?= $item['input_by'] ?> | 
                                <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-trophy" style="font-size: 4rem; color: #333; margin-bottom: 20px;"></i>
                    <h3 style="color: #666;">Belum Ada Info Lomba</h3>
                    <p style="color: #999;">Tambahkan informasi lomba pertama</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>