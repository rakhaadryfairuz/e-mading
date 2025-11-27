<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle tambah prestasi
if ($_POST && isset($_POST['tambah'])) {
    $nama_siswa = $_POST['nama_siswa'];
    $kelas = $_POST['kelas'];
    $prestasi = $_POST['prestasi'];
    $tingkat = $_POST['tingkat'];
    $tanggal = $_POST['tanggal'];
    $deskripsi = $_POST['deskripsi'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO prestasi (nama_siswa, kelas, prestasi, tingkat, tanggal, deskripsi, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama_siswa, $kelas, $prestasi, $tingkat, $tanggal, $deskripsi, $_SESSION['user_id']]);
        $success = "Prestasi berhasil ditambahkan!";
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil semua prestasi
try {
    $stmt = $pdo->query("SELECT p.*, u.nama as input_by FROM prestasi p 
                         LEFT JOIN users u ON p.user_id = u.id 
                         ORDER BY p.tanggal DESC");
    $prestasi = $stmt->fetchAll();
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestasi Siswa - E-Mading</title>
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
            <li><a href="lomba.php"><i class="fas fa-trophy"></i> Lomba</a></li>
            <li><a href="prestasi.php" class="active"><i class="fas fa-award"></i> Prestasi</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Prestasi Siswa</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <!-- Form Tambah Prestasi -->
            <div class="card">
                <h3><i class="fas fa-plus"></i> Tambah Prestasi Baru</h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama_siswa" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <input type="text" name="kelas" class="form-control" placeholder="Contoh: XII IPA 1" required>
                        </div>
                        <div class="form-group">
                            <label>Prestasi</label>
                            <input type="text" name="prestasi" class="form-control" placeholder="Contoh: Juara 1 Olimpiade Matematika" required>
                        </div>
                        <div class="form-group">
                            <label>Tingkat</label>
                            <select name="tingkat" class="form-control" required>
                                <option value="">Pilih Tingkat</option>
                                <option value="Sekolah">Sekolah</option>
                                <option value="Kecamatan">Kecamatan</option>
                                <option value="Kabupaten">Kabupaten</option>
                                <option value="Provinsi">Provinsi</option>
                                <option value="Nasional">Nasional</option>
                                <option value="Internasional">Internasional</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi prestasi..."></textarea>
                    </div>
                    <button type="submit" name="tambah" class="btn">
                        <i class="fas fa-save"></i> Simpan Prestasi
                    </button>
                </form>
            </div>

            <!-- Daftar Prestasi -->
            <div class="card">
                <h3><i class="fas fa-award"></i> Daftar Prestasi</h3>
                <?php if(isset($prestasi) && count($prestasi) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Prestasi</th>
                            <th>Tingkat</th>
                            <th>Tanggal</th>
                            <th>Input By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($prestasi as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nama_siswa']) ?></td>
                            <td><?= htmlspecialchars($item['kelas']) ?></td>
                            <td><?= htmlspecialchars($item['prestasi']) ?></td>
                            <td>
                                <span class="badge badge-success"><?= $item['tingkat'] ?></span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($item['tanggal'])) ?></td>
                            <td><?= $item['input_by'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-award" style="font-size: 4rem; color: #333; margin-bottom: 20px;"></i>
                    <h3 style="color: #666;">Belum Ada Prestasi</h3>
                    <p style="color: #999;">Tambahkan prestasi siswa pertama</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>