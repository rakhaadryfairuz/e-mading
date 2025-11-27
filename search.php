<?php
include 'auth_check.php';
include 'config/database.php';

// Semua user yang login bisa akses pencarian
checkAuth();

$search = $_GET['q'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';

$artikel = [];
if ($search || $kategori || $tanggal) {
    try {
        $sql = "SELECT a.*, k.nama_kategori, u.nama FROM artikel a 
                LEFT JOIN kategori k ON a.kategori_id = k.id 
                LEFT JOIN users u ON a.user_id = u.id 
                WHERE 1=1";
        $params = [];
        
        // Filter berdasarkan role
        if (getUserRole() === 'anggota') {
            $sql .= " AND a.user_id = ?";
            $params[] = getUserId();
        } elseif (getUserRole() === 'guru') {
            $sql .= " AND a.status IN ('pending', 'approved', 'published')";
        }
        
        if ($search) {
            $sql .= " AND (a.judul LIKE ? OR a.konten LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($kategori) {
            $sql .= " AND a.kategori_id = ?";
            $params[] = $kategori;
        }
        
        if ($tanggal) {
            $sql .= " AND DATE(a.created_at) = ?";
            $params[] = $tanggal;
        }
        
        $sql .= " ORDER BY a.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $artikel = $stmt->fetchAll();
        
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil kategori untuk filter
$stmt = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori");
$kategori_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian Artikel - E-Mading</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h1>E-Mading</h1>
            <div style="color: white; font-size: 14px;">
                <i class="fas fa-user"></i> <?= getUserName() ?> (<?= ucfirst(getUserRole()) ?>)
            </div>
        </div>
    </div>

    <div class="sidebar">
        <h2>Dashboard</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            
            <?php if (canCreateArticle()): ?>
            <li><a href="my_articles.php"><i class="fas fa-user-edit"></i> Artikel Saya</a></li>
            <li><a href="tambah_artikel.php"><i class="fas fa-plus"></i> Tulis Artikel</a></li>
            <?php endif; ?>
            
            <?php if (canApproveArticle()): ?>
            <li><a href="moderasi.php"><i class="fas fa-shield-alt"></i> Moderasi Artikel</a></li>
            <?php endif; ?>
            
            <?php if (getUserRole() === 'admin'): ?>
            <li><a href="artikel.php"><i class="fas fa-newspaper"></i> Semua Artikel</a></li>
            <li><a href="kategori.php"><i class="fas fa-tags"></i> Kategori</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <?php endif; ?>
            
            <li><a href="search.php" class="active"><i class="fas fa-search"></i> Pencarian</a></li>
            <li><a href="public.php" target="_blank"><i class="fas fa-eye"></i> Lihat Public</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Pencarian Artikel</h2>
            
            <!-- Form Pencarian -->
            <div class="card">
                <h3><i class="fas fa-search"></i> Filter Pencarian</h3>
                <form method="GET">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label>Kata Kunci</label>
                            <input type="text" name="q" class="form-control" placeholder="Cari judul atau konten..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" class="form-control">
                                <option value="">Semua Kategori</option>
                                <?php foreach($kategori_list as $kat): ?>
                                <option value="<?= $kat['id'] ?>" <?= $kategori == $kat['id'] ? 'selected' : '' ?>>
                                    <?= $kat['nama_kategori'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Cari Artikel
                    </button>
                    <a href="search.php" class="btn" style="background: #6c757d;">
                        <i class="fas fa-times"></i> Reset
                    </a>
                </form>
            </div>

            <!-- Hasil Pencarian -->
            <div class="card">
                <h3><i class="fas fa-list"></i> Hasil Pencarian</h3>
                
                <?php if ($search || $kategori || $tanggal): ?>
                    <div style="margin-bottom: 20px; padding: 15px; background: #222; border-radius: 5px;">
                        <strong>Filter aktif:</strong>
                        <?php if ($search): ?>
                            <span class="badge badge-info">Kata kunci: "<?= htmlspecialchars($search) ?>"</span>
                        <?php endif; ?>
                        <?php if ($kategori): ?>
                            <?php 
                            $kat_name = array_filter($kategori_list, function($k) use ($kategori) { return $k['id'] == $kategori; });
                            $kat_name = reset($kat_name);
                            ?>
                            <span class="badge badge-success">Kategori: <?= $kat_name['nama_kategori'] ?></span>
                        <?php endif; ?>
                        <?php if ($tanggal): ?>
                            <span class="badge badge-warning">Tanggal: <?= date('d/m/Y', strtotime($tanggal)) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($artikel) > 0): ?>
                    <p style="color: #ccc; margin-bottom: 20px;">Ditemukan <?= count($artikel) ?> artikel</p>
                    <table class="table">
                        <thead>
                            <tr>
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
                                    <?php if (getUserRole() === 'admin' || ($item['user_id'] == getUserId() && getUserRole() === 'anggota')): ?>
                                    <a href="edit_artikel.php?id=<?= $item['id'] ?>" class="btn btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="view_artikel.php?id=<?= $item['id'] ?>" class="btn btn-sm" style="background: #17a2b8;">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-search" style="font-size: 4rem; color: #333; margin-bottom: 20px;"></i>
                        <h3 style="color: #666;">Tidak Ada Hasil</h3>
                        <p style="color: #999;">Coba ubah kata kunci atau filter pencarian</p>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-search" style="font-size: 4rem; color: #333; margin-bottom: 20px;"></i>
                    <h3 style="color: #666;">Masukkan Kata Kunci</h3>
                    <p style="color: #999;">Gunakan form di atas untuk mencari artikel</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>