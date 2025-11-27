<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle upload foto
if ($_POST && isset($_POST['upload'])) {
    $judul = $_POST['judul'];
    $deskripsi = $_POST['deskripsi'];
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/galeri/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = time() . '.' . $file_extension;
        $target_file = $target_dir . $foto;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO galeri (judul, deskripsi, foto, user_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$judul, $deskripsi, $foto, $_SESSION['user_id']]);
                $success = "Foto berhasil diupload!";
            } catch(Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "Gagal mengupload foto!";
        }
    }
}

// Ambil semua foto galeri
try {
    $stmt = $pdo->query("SELECT g.*, u.nama FROM galeri g 
                         LEFT JOIN users u ON g.user_id = u.id 
                         ORDER BY g.created_at DESC");
    $galeri = $stmt->fetchAll();
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri Foto - E-Mading</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
    <style>
        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .galeri-item {
            background: #222;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .galeri-item:hover {
            transform: translateY(-5px);
        }
        
        .galeri-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .galeri-info {
            padding: 15px;
        }
        
        .galeri-info h4 {
            color: #667eea;
            margin-bottom: 10px;
        }
    </style>
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
            <li><a href="galeri.php" class="active"><i class="fas fa-images"></i> Galeri</a></li>
            <li><a href="lomba.php"><i class="fas fa-trophy"></i> Lomba</a></li>
            <li><a href="prestasi.php"><i class="fas fa-award"></i> Prestasi</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Galeri Foto Kegiatan</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <!-- Form Upload -->
            <div class="card">
                <h3><i class="fas fa-upload"></i> Upload Foto Baru</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Judul Foto</label>
                            <input type="text" name="judul" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>File Foto</label>
                            <input type="file" name="foto" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" name="upload" class="btn">
                        <i class="fas fa-upload"></i> Upload Foto
                    </button>
                </form>
            </div>

            <!-- Galeri -->
            <div class="card">
                <h3><i class="fas fa-images"></i> Galeri Foto</h3>
                <?php if(isset($galeri) && count($galeri) > 0): ?>
                <div class="galeri-grid">
                    <?php foreach($galeri as $item): ?>
                    <div class="galeri-item">
                        <img src="uploads/galeri/<?= $item['foto'] ?>" alt="<?= $item['judul'] ?>">
                        <div class="galeri-info">
                            <h4><?= htmlspecialchars($item['judul']) ?></h4>
                            <p style="color: #ccc; font-size: 14px;"><?= htmlspecialchars($item['deskripsi']) ?></p>
                            <small style="color: #999;">
                                Oleh: <?= $item['nama'] ?> | <?= date('d/m/Y', strtotime($item['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-images" style="font-size: 4rem; color: #333; margin-bottom: 20px;"></i>
                    <h3 style="color: #666;">Belum Ada Foto</h3>
                    <p style="color: #999;">Upload foto kegiatan sekolah pertama Anda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>