<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['admin', 'siswa']);

$userRole = getUserRole();

// Handle tambah kategori
if ($_POST && isset($_POST['tambah'])) {
    $nama_kategori = $_POST['nama_kategori'];
    $deskripsi = $_POST['deskripsi'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, deskripsi) VALUES (?, ?)");
        $stmt->execute([$nama_kategori, $deskripsi]);
        $success = "Kategori berhasil ditambahkan!";
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle hapus kategori
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Kategori berhasil dihapus!";
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil semua kategori
try {
    $stmt = $pdo->query("SELECT k.*, COUNT(a.id) as total_artikel FROM kategori k 
                         LEFT JOIN artikel a ON k.id = a.kategori_id 
                         GROUP BY k.id 
                         ORDER BY k.nama_kategori");
    $kategori = $stmt->fetchAll();
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - E-Mading</title>
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
        <?php if($userRole == 'admin'): ?>
        <h2>Dashboard</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="artikel.php"><i class="fas fa-newspaper"></i> Artikel</a></li>
            <li><a href="kategori.php" class="active"><i class="fas fa-tags"></i> Kategori</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
        <?php else: ?>
        <h2>Menu Siswa</h2>
        <ul>
            <li><a href="dashboard_siswa.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="my_articles.php"><i class="fas fa-user-edit"></i> Artikel Saya</a></li>
            <li><a href="tambah_artikel.php"><i class="fas fa-plus"></i> Tulis Artikel</a></li>
            <li><a href="kategori.php" class="active"><i class="fas fa-tags"></i> Tambah Kategori</a></li>
            <li><a href="public.php" target="_blank"><i class="fas fa-eye"></i> Lihat Public</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Kelola Kategori</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <!-- Form Tambah Kategori -->
            <div class="card">
                <h3><i class="fas fa-plus"></i> Tambah Kategori Baru</h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                        <div class="form-group">
                            <label>Nama Kategori</label>
                            <input type="text" name="nama_kategori" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="tambah" class="btn">
                        <i class="fas fa-save"></i> Simpan Kategori
                    </button>
                </form>
            </div>

            <!-- Daftar Kategori -->
            <div class="card">
                <h3><i class="fas fa-tags"></i> Daftar Kategori</h3>
                <?php if(isset($kategori) && count($kategori) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Total Artikel</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($kategori as $item): ?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            <td><strong><?= htmlspecialchars($item['nama_kategori']) ?></strong></td>
                            <td><?= htmlspecialchars($item['deskripsi']) ?></td>
                            <td>
                                <span class="badge badge-info"><?= $item['total_artikel'] ?> artikel</span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($item['created_at'])) ?></td>
                            <td>
                                <a href="?hapus=<?= $item['id'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h3>Belum Ada Kategori</h3>
                    <p>Tambahkan kategori pertama untuk mengorganisir artikel</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>