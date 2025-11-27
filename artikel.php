<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['admin']);

// Ambil semua artikel
try {
    $stmt = $pdo->query("SELECT a.*, k.nama_kategori, u.nama FROM artikel a 
                         LEFT JOIN kategori k ON a.kategori_id = k.id 
                         LEFT JOIN users u ON a.user_id = u.id 
                         ORDER BY a.created_at DESC");
    $artikel = $stmt->fetchAll();
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel - E-Magazine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>E-Magazine</h1>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Semua Artikel</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="page-title">Kelola Artikel</h2>
                <a href="tambah_artikel.php" class="btn">
                    <i class="fas fa-plus"></i> Tambah Artikel
                </a>
            </div>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); endif; ?>
            
            <div class="card">
                <?php if(isset($artikel) && count($artikel) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gambar</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Penulis</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($artikel as $item): ?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            <td>
                                <?php if($item['gambar'] && file_exists('uploads/' . $item['gambar'])): ?>
                                <img src="uploads/<?= $item['gambar'] ?>" alt="<?= htmlspecialchars($item['judul']) ?>" 
                                     style="width: 60px; height: 40px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd;">
                                <?php else: ?>
                                <div style="width: 60px; height: 40px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="color: #ccc; font-size: 14px;"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['judul']) ?></td>
                            <td><?= htmlspecialchars($item['nama_kategori'] ?? 'Tidak ada') ?></td>
                            <td><?= htmlspecialchars($item['nama']) ?></td>
                            <td>
                                <span class="badge <?= $item['status'] == 'published' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= ucfirst($item['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                            <td>
                                <a href="edit_artikel.php?id=<?= $item['id'] ?>" class="btn btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="hapus_artikel.php?id=<?= $item['id'] ?>" class="btn btn-sm" 
                                   onclick="return confirm('Yakin ingin menghapus artikel ini?')"
                                   style="background: #dc3545;">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-newspaper" style="font-size: 4rem; color: #333; margin-bottom: 20px;"></i>
                    <h3 style="color: #666; margin-bottom: 10px;">Belum Ada Artikel</h3>
                    <p style="color: #999; margin-bottom: 20px;">Mulai dengan menambahkan artikel pertama Anda</p>
                    <a href="tambah_artikel.php" class="btn">
                        <i class="fas fa-plus"></i> Tambah Artikel
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>