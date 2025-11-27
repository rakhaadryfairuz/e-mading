<?php
session_start();
include 'config/database.php';

// Cek apakah user sudah login
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;

// Ambil kategori
$kategori = [];
try {
    $stmt = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori");
    $kategori = $stmt->fetchAll();
} catch(Exception $e) {
    // Ignore error
}

// Ambil artikel published dengan pencarian
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';
try {
    $where = "a.status = 'published'";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (a.judul LIKE ? OR a.konten LIKE ? OR k.nama_kategori LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($kategori_filter)) {
        $where .= " AND k.id = ?";
        $params[] = $kategori_filter;
    }
    
    $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori, u.nama as penulis FROM artikel a 
                         LEFT JOIN kategori k ON a.kategori_id = k.id 
                         LEFT JOIN users u ON a.user_id = u.id
                         WHERE $where 
                         ORDER BY a.tanggal_publish DESC");
    $stmt->execute($params);
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
    <title>E-Mading Sekolah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 50%, #03a9f4 100%);
            color: white;
            padding: 1rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: header-sweep 8s linear infinite;
        }
        
        @keyframes header-sweep {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
        }
        
        .section {
            padding: 3rem 0;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #0277bd;
        }
        
        .artikel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2.5rem;
            margin-bottom: 3rem;
        }
        
        .artikel-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
            transition: all 0.4s ease;
            border: 1px solid rgba(79, 195, 247, 0.1);
        }
        
        .artikel-card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 30px 60px rgba(79, 195, 247, 0.4);
        }
        
        .artikel-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .artikel-content {
            padding: 2rem;
        }
        
        .artikel-kategori {
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .artikel-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            color: #2c3e50;
            line-height: 1.4;
        }
        
        .artikel-excerpt {
            color: #6c757d;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .artikel-meta {
            font-size: 0.85rem;
            color: #adb5bd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #f1f3f4;
        }
        
        .artikel-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .artikel-meta i {
            color: #4fc3f7;
        }
        
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 50%, #e1f5fe 100%);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid rgba(79, 195, 247, 0.2);
        }
        
        .welcome-banner h3 {
            color: #0277bd;
            margin-bottom: 10px;
        }
        
        .welcome-banner p {
            color: #666;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header" style="position: relative;">
        <!-- Logo -->
        <img src="assets/images/logo_bn-removebg-preview.png" alt="Logo Sekolah" style="position: absolute; top: 20px; left: 20px; width: 60px; height: 60px; z-index: 1000;">
        
        <?php if($isLoggedIn): ?>
        <!-- User Info -->
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username']) ?></span>
            <span style="font-size: 12px; opacity: 0.8;">(<?= ucfirst($userRole) ?>)</span>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        <?php else: ?>
        <!-- Login Button -->
        <a href="login.php" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); color: white; padding: 12px 20px; border-radius: 25px; text-decoration: none; backdrop-filter: blur(10px);">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
        <?php endif; ?>
        
        <div class="container">
            <h1><i class="fas fa-newspaper"></i> E-Mading Sekolah</h1>
            <p>Portal Informasi Digital Sekolah</p>
            <form method="GET" style="margin-top: 1rem; max-width: 500px; margin-left: auto; margin-right: auto;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari artikel..." style="width: 100%; padding: 12px 20px; border: none; border-radius: 25px; font-size: 16px; outline: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            </form>
        </div>
    </div>

    <div class="container">
        <?php if($isLoggedIn): ?>
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h3><i class="fas fa-hand-wave"></i> Selamat Datang, <?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username']) ?>!</h3>
            <p>Anda login sebagai <strong><?= ucfirst($userRole) ?></strong>. Nikmati membaca artikel-artikel terbaru dari sekolah.</p>
        </div>
        <?php endif; ?>
        
        <!-- Artikel Section -->
        <div class="section">
            <!-- Kategori Filter -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="<?= $isLoggedIn ? 'public_home.php' : 'public.php' ?>" style="display: inline-block; padding: 8px 16px; margin: 5px; background: <?= empty($kategori_filter) ? '#4fc3f7' : '#f8f9fa' ?>; color: <?= empty($kategori_filter) ? 'white' : '#333' ?>; text-decoration: none; border-radius: 20px; font-size: 14px;">Semua</a>
                <?php foreach($kategori as $kat): ?>
                <a href="?kategori=<?= $kat['id'] ?>" style="display: inline-block; padding: 8px 16px; margin: 5px; background: <?= $kategori_filter == $kat['id'] ? '#4fc3f7' : '#f8f9fa' ?>; color: <?= $kategori_filter == $kat['id'] ? 'white' : '#333' ?>; text-decoration: none; border-radius: 20px; font-size: 14px;"><?= htmlspecialchars($kat['nama_kategori']) ?></a>
                <?php endforeach; ?>
            </div>
            
            <h2 class="section-title"><?= !empty($search) ? 'Hasil Pencarian: "' . htmlspecialchars($search) . '"' : 'Artikel Terbaru' ?></h2>
            
            <?php if(isset($artikel) && count($artikel) > 0): ?>
            <div class="artikel-grid">
                <?php foreach($artikel as $index => $item): ?>
                <div class="artikel-card" style="--delay: <?= $index ?>;">
                    <a href="view_artikel.php?id=<?= $item['id'] ?>" style="text-decoration: none; color: inherit;">
                        <?php if($item['gambar'] && file_exists('uploads/' . $item['gambar'])): ?>
                        <img src="uploads/<?= $item['gambar'] ?>" alt="<?= htmlspecialchars($item['judul']) ?>">
                        <?php else: ?>
                        <div style="height: 200px; background: linear-gradient(135deg, #4fc3f7, #29b6f6); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-newspaper" style="font-size: 3rem; color: white; opacity: 0.7;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="artikel-content">
                            <span class="artikel-kategori"><?= htmlspecialchars($item['nama_kategori']) ?></span>
                            <h3 class="artikel-title"><?= htmlspecialchars($item['judul']) ?></h3>
                            <p class="artikel-excerpt"><?= substr(strip_tags($item['konten']), 0, 150) ?>...</p>
                            <div class="artikel-meta">
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($item['penulis']) ?></span>
                                <span><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($item['tanggal_publish'])) ?></span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #666;">
                <i class="fas fa-search" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <h3><?= !empty($search) ? 'Tidak Ada Hasil' : 'Belum Ada Artikel' ?></h3>
                <p><?= !empty($search) ? 'Coba kata kunci lain' : 'Artikel akan segera hadir' ?></p>
                <?php if (!empty($search)): ?>
                <a href="<?= $isLoggedIn ? 'public_home.php' : 'public.php' ?>" style="color: #4fc3f7; text-decoration: none;">← Kembali ke semua artikel</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <p>&copy; 2024 E-Mading Sekolah. Semua hak dilindungi.</p>
            <p>Dibuat dengan ❤️ untuk kemajuan pendidikan</p>
        </div>
    </div>
</body>
</html>