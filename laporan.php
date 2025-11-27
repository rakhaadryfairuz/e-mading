<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['admin']);

$bulan = $_GET['bulan'] ?? date('Y-m');
$kategori = $_GET['kategori'] ?? '';

// Ambil data untuk laporan
try {
    $sql = "SELECT a.*, k.nama_kategori, u.nama FROM artikel a 
            LEFT JOIN kategori k ON a.kategori_id = k.id 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE DATE_FORMAT(a.created_at, '%Y-%m') = ?";
    $params = [$bulan];
    
    if ($kategori) {
        $sql .= " AND a.kategori_id = ?";
        $params[] = $kategori;
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $artikel = $stmt->fetchAll();
    
    // Statistik
    $total_artikel = count($artikel);
    $published = count(array_filter($artikel, function($a) { return $a['status'] == 'published'; }));
    $draft = $total_artikel - $published;
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Ambil kategori
$stmt = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori");
$kategori_list = $stmt->fetchAll();

// Export ke Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="laporan_artikel_' . $bulan . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th>No</th><th>Judul</th><th>Kategori</th><th>Penulis</th><th>Status</th><th>Tanggal</th></tr>";
    
    $no = 1;
    foreach($artikel as $item) {
        echo "<tr>";
        echo "<td>" . $no++ . "</td>";
        echo "<td>" . htmlspecialchars($item['judul']) . "</td>";
        echo "<td>" . htmlspecialchars($item['nama_kategori']) . "</td>";
        echo "<td>" . htmlspecialchars($item['nama']) . "</td>";
        echo "<td>" . ucfirst($item['status']) . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($item['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Aktivitas - E-Mading</title>
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
        <h2>Laporan</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Laporan Aktivitas</h2>
            


            <!-- Statistik -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= $total_artikel ?></h3>
                    <p>Total Artikel</p>
                </div>
                <div class="stat-card">
                    <h3><?= $published ?></h3>
                    <p>Published</p>
                </div>
                <div class="stat-card">
                    <h3><?= $draft ?></h3>
                    <p>Draft</p>
                </div>
                <div class="stat-card">
                    <h3><?= $published > 0 ? round(($published/$total_artikel)*100) : 0 ?>%</h3>
                    <p>Tingkat Publikasi</p>
                </div>
            </div>

            <!-- Tabel Laporan -->
            <div class="card">
                <h3><i class="fas fa-table"></i> Detail Laporan - <?= date('F Y', strtotime($bulan . '-01')) ?></h3>
                
                <?php if(isset($artikel) && count($artikel) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Penulis</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach($artikel as $item): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($item['judul']) ?></td>
                            <td><?= htmlspecialchars($item['nama_kategori'] ?? 'Tidak ada') ?></td>
                            <td><?= htmlspecialchars($item['nama']) ?></td>
                            <td>
                                <span class="badge <?= $item['status'] == 'published' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= ucfirst($item['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-chart-bar" style="font-size: 4rem; color: #333; margin-bottom: 20px;"></i>
                    <h3 style="color: #666;">Tidak Ada Data</h3>
                    <p style="color: #999;">Tidak ada artikel pada periode yang dipilih</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>