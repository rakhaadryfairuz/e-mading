<?php
session_start();
include 'config/database.php';

// Cek apakah user sudah login
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;

// Debug: pastikan role terdeteksi
if($isLoggedIn && !$userRole) {
    // Jika user login tapi role tidak ada, redirect ke login
    session_destroy();
    header('Location: login.php');
    exit;
}

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
$sort = $_GET['sort'] ?? '';

try {
    $where = "a.status = 'published'";
    $params = [];
    $orderBy = "a.tanggal_publish DESC";
    
    if (!empty($search)) {
        $where .= " AND (a.judul LIKE ? OR a.konten LIKE ? OR k.nama_kategori LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($kategori_filter)) {
        $where .= " AND k.id = ?";
        $params[] = $kategori_filter;
    }
    
    // Jika sort=popular, urutkan berdasarkan popularity score
    if ($sort === 'popular') {
        $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori, u.nama as penulis, u.foto_profil as penulis_foto,
                              COALESCE(a.views, 0) as total_views,
                              COALESCE(like_count.total_likes, 0) as total_likes,
                              COALESCE(comment_count.total_comments, 0) as total_comments,
                              (COALESCE(a.views, 0) + COALESCE(like_count.total_likes, 0) * 3 + COALESCE(comment_count.total_comments, 0) * 2) as popularity_score
                              FROM artikel a 
                              LEFT JOIN kategori k ON a.kategori_id = k.id 
                              LEFT JOIN users u ON a.user_id = u.id
                              LEFT JOIN (SELECT artikel_id, COUNT(*) as total_likes FROM likes GROUP BY artikel_id) like_count ON a.id = like_count.artikel_id
                              LEFT JOIN (SELECT artikel_id, COUNT(*) as total_comments FROM komentar WHERE status = 'approved' GROUP BY artikel_id) comment_count ON a.id = comment_count.artikel_id
                              WHERE $where 
                              ORDER BY popularity_score DESC, a.tanggal_publish DESC");
    } else {
        $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori, u.nama as penulis, u.foto_profil as penulis_foto,
                              COALESCE(a.views, 0) as total_views FROM artikel a 
                             LEFT JOIN kategori k ON a.kategori_id = k.id 
                             LEFT JOIN users u ON a.user_id = u.id
                             WHERE $where 
                             ORDER BY $orderBy");
    }
    
    $stmt->execute($params);
    $artikel = $stmt->fetchAll();
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Ambil artikel populer (berdasarkan kombinasi likes, komentar, dan views)
try {
    // Buat tabel likes jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        artikel_id INT NOT NULL,
        user_id INT NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_like (artikel_id, user_id),
        UNIQUE KEY unique_ip_like (artikel_id, ip_address)
    )");
    
    // Buat tabel komentar jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS komentar (
        id INT PRIMARY KEY AUTO_INCREMENT,
        artikel_id INT NOT NULL,
        user_id INT NULL,
        nama VARCHAR(100) NULL,
        email VARCHAR(100) NULL,
        komentar TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori, u.nama as penulis, u.foto_profil as penulis_foto,
                          COALESCE(a.views, 0) as total_views,
                          COALESCE(like_count.total_likes, 0) as total_likes,
                          COALESCE(comment_count.total_comments, 0) as total_comments,
                          (COALESCE(a.views, 0) + COALESCE(like_count.total_likes, 0) * 3 + COALESCE(comment_count.total_comments, 0) * 2) as popularity_score
                          FROM artikel a 
                          LEFT JOIN kategori k ON a.kategori_id = k.id 
                          LEFT JOIN users u ON a.user_id = u.id
                          LEFT JOIN (SELECT artikel_id, COUNT(*) as total_likes FROM likes GROUP BY artikel_id) like_count ON a.id = like_count.artikel_id
                          LEFT JOIN (SELECT artikel_id, COUNT(*) as total_comments FROM komentar WHERE status = 'approved' GROUP BY artikel_id) comment_count ON a.id = comment_count.artikel_id
                          WHERE a.status = 'published' 
                          ORDER BY popularity_score DESC, a.tanggal_publish DESC
                          LIMIT 4");
    $stmt->execute();
    $artikel_populer = $stmt->fetchAll();
} catch(Exception $e) {
    $artikel_populer = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mading Sekolah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
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
            color: white;
            padding: 0.5rem 0;
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
        

        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0;
        }
        
        .header p {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .section {
            padding: 3rem 0;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
        }
        
        .artikel-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .artikel-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
            transition: all 0.4s ease;
            border: 1px solid rgba(79, 195, 247, 0.1);
            display: flex;
            flex-direction: column;
        }
        
        .artikel-card:hover {
            transform: translateY(-15px) scale(1.03) rotateY(5deg);
            animation: card-glow 1.5s ease-in-out infinite alternate;
        }
        

        
        @keyframes text-shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }
        
        @keyframes card-entrance {
            0% { transform: translateY(50px) rotateX(-15deg); opacity: 0; }
            100% { transform: translateY(0) rotateX(0deg); opacity: 1; }
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
        

        
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        /* Floating Particles */
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { width: 20px; height: 20px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 15px; height: 15px; left: 20%; animation-delay: 1s; }
        .particle:nth-child(3) { width: 25px; height: 25px; left: 30%; animation-delay: 2s; }
        .particle:nth-child(4) { width: 18px; height: 18px; left: 40%; animation-delay: 3s; }
        .particle:nth-child(5) { width: 22px; height: 22px; left: 50%; animation-delay: 4s; }
        .particle:nth-child(6) { width: 16px; height: 16px; left: 60%; animation-delay: 5s; }
        .particle:nth-child(7) { width: 24px; height: 24px; left: 70%; animation-delay: 0.5s; }
        .particle:nth-child(8) { width: 19px; height: 19px; left: 80%; animation-delay: 1.5s; }
        .particle:nth-child(9) { width: 21px; height: 21px; left: 90%; animation-delay: 2.5s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }
        
        /* Decorative Elements */
        .deco-circle {
            position: absolute;
            border-radius: 50%;
            animation: pulse-glow 4s ease-in-out infinite;
        }
        
        @keyframes pulse-glow {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.2); opacity: 0.6; }
        }
        
        /* Floating Search Animation */
        @keyframes search-float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-6px) rotate(-0.5deg); }
            66% { transform: translateY(-3px) rotate(0.5deg); }
        }
        
        .floating-search {
            animation: search-float 4s ease-in-out infinite 0.5s;
        }
        
        .floating-search:hover {
            animation-play-state: paused;
        }
        
        /* Layout Dua Kolom */
        .main-content {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .artikel-terbaru {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .artikel-populer {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }
        
        .section-header h2 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin: 0;
        }
        
        .section-header i {
            font-size: 1.5rem;
        }
        
        /* Artikel Populer Card Style */
        .populer-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border: 1px solid #f1f3f4;
        }
        
        .populer-card:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        
        .populer-card img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }
        
        .populer-content {
            flex: 1;
        }
        
        .populer-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .populer-meta {
            font-size: 0.8rem;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .populer-views {
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Animasi untuk artikel populer */
        .populer-card {
            animation: slide-in-right 0.6s ease-out;
            animation-delay: calc(var(--delay, 0) * 0.1s);
            animation-fill-mode: both;
        }
        
        @keyframes slide-in-right {
            0% { transform: translateX(50px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }
        
        .populer-placeholder {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            flex-shrink: 0;
        }
        
        .populer-number {
            position: absolute;
            top: -5px;
            left: -5px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }
        
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .artikel-populer {
                position: static;
                order: -1; /* Tampilkan artikel populer di atas pada mobile */
            }
            
            .populer-card {
                padding: 0.8rem;
            }
            
            .populer-card img,
            .populer-placeholder {
                width: 60px;
                height: 60px;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }
            
            .artikel-terbaru,
            .artikel-populer {
                padding: 1.5rem;
            }
            
            .section-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Particles -->
    <div class="floating-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    
    <!-- Header -->
    <div class="header" style="position: relative;">
        <!-- Decorative Circles -->
        <div class="deco-circle" style="width: 100px; height: 100px; top: 20px; right: 15%; z-index: 0;"></div>
        <div class="deco-circle" style="width: 60px; height: 60px; top: 60px; left: 10%; z-index: 0; animation-delay: 1s;"></div>
        <div class="deco-circle" style="width: 80px; height: 80px; bottom: 20px; right: 25%; z-index: 0; animation-delay: 2s;"></div>
        <!-- Logo -->
        <img src="assets/images/logo_bn-removebg-preview.png" alt="Logo Sekolah Menengah Kejuruan Bakti Nusantara 666" style="position: absolute; top: 20px; left: 20px; width: 60px; height: 60px; z-index: 1000;">
        
        <!-- Navbar -->
        <nav style="position: absolute; top: 20px; right: 20px; z-index: 1000;">
            <?php if($isLoggedIn): ?>
                <?php if($userRole === 'pending'): ?>
                    <a href="logout.php" style="background: rgba(255,255,255,0.15); color: white; padding: 12px 20px; border-radius: 30px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <?php 
                    if($userRole === 'admin') {
                        $dashboardUrl = 'admin_dashboard.php';
                    } elseif($userRole === 'guru') {
                        $dashboardUrl = 'dashboard_guru.php';
                    } else {
                        $dashboardUrl = 'dashboard_siswa.php';
                    }
                    ?>
                    <a href="<?= $dashboardUrl ?>" style="background: rgba(255,255,255,0.15); color: white; padding: 12px 20px; border-radius: 30px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" style="background: rgba(255,255,255,0.15); color: white; padding: 12px 20px; border-radius: 30px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            <?php endif; ?>
        </nav>
        

        
        <div class="container">
            <h1><i class="fas fa-newspaper"></i> E-Mading Sekolah</h1>
            <p>Portal Informasi Digital Sekolah</p>
        </div>
    </div>

    <!-- Artikel Section -->
    <div class="section">
        <div class="container">
            <!-- Floating Search Bar -->
            <div class="floating-search" style="text-align: center; margin-bottom: 2rem;">
                <form method="GET" style="margin: 0; display: inline-block;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari artikel..." style="width: 400px; padding: 15px 25px; border: none; border-radius: 30px; font-size: 16px; outline: none; background: rgba(255,255,255,0.9); backdrop-filter: blur(15px); box-shadow: 0 10px 40px rgba(79, 195, 247, 0.2); border: 2px solid rgba(79, 195, 247, 0.1); transition: all 0.4s ease;" onmouseover="this.style.transform='translateY(-5px) scale(1.02)'; this.style.boxShadow='0 20px 60px rgba(79, 195, 247, 0.3)'; this.style.borderColor='rgba(79, 195, 247, 0.3)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 10px 40px rgba(79, 195, 247, 0.2)'; this.style.borderColor='rgba(79, 195, 247, 0.1)'" onfocus="this.style.background='rgba(255,255,255,0.95)'; this.style.transform='translateY(-3px) scale(1.05)'; this.style.borderColor='#4fc3f7'" onblur="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='translateY(0) scale(1)'; this.style.borderColor='rgba(79, 195, 247, 0.1)'">
                </form>
            </div>
            
            <!-- Kategori Filter -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="public.php" style="display: inline-block; padding: 8px 16px; margin: 5px; background: <?= empty($kategori_filter) ? '#4fc3f7' : '#f8f9fa' ?>; color: <?= empty($kategori_filter) ? 'white' : '#333' ?>; text-decoration: none; border-radius: 20px; font-size: 14px;">Semua</a>
                <?php foreach($kategori as $kat): ?>
                <a href="?kategori=<?= $kat['id'] ?>" style="display: inline-block; padding: 8px 16px; margin: 5px; background: <?= $kategori_filter == $kat['id'] ? '#4fc3f7' : '#f8f9fa' ?>; color: <?= $kategori_filter == $kat['id'] ? 'white' : '#333' ?>; text-decoration: none; border-radius: 20px; font-size: 14px;"><?= htmlspecialchars($kat['nama_kategori']) ?></a>
                <?php endforeach; ?>
            </div>
            
            <!-- Layout Dua Kolom -->
            <div class="main-content" style="<?= $sort === 'popular' ? 'grid-template-columns: 1fr;' : '' ?>">
                <!-- Artikel Terbaru (Kiri - Dominan) -->
                <div class="artikel-terbaru">
                    <div class="section-header">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <?php if ($sort === 'popular'): ?>
                                <i class="fas fa-fire"></i>
                                <h2>Semua Artikel Populer</h2>
                            <?php elseif (!empty($search)): ?>
                                <i class="fas fa-search"></i>
                                <h2>Hasil Pencarian: "<?= htmlspecialchars($search) ?>"</h2>
                            <?php else: ?>
                                <i class="fas fa-newspaper"></i>
                                <h2>Artikel Terbaru</h2>
                            <?php endif; ?>
                        </div>
                        <?php if ($sort === 'popular'): ?>
                            <a href="public.php" style="color: #4fc3f7; text-decoration: none; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.color='#29b6f6'; this.style.transform='translateX(-3px)'" onmouseout="this.style.color='#4fc3f7'; this.style.transform='translateX(0)'">
                                <i class="fas fa-arrow-left"></i> Kembali ke Terbaru
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(isset($artikel) && count($artikel) > 0): ?>
                    <div class="artikel-grid">
                        <?php foreach($artikel as $index => $item): ?>
                        <div class="artikel-card" style="--delay: <?= $index ?>; animation: card-entrance 0.8s ease-out <?= $index * 0.2 ?>s both;">
                            <a href="view_artikel.php?id=<?= $item['id'] ?>" style="text-decoration: none; color: inherit;">
                                <?php if($item['gambar'] && file_exists('uploads/' . $item['gambar'])): ?>
                                <img src="uploads/<?= $item['gambar'] ?>" alt="<?= htmlspecialchars($item['judul']) ?>">
                                <?php else: ?>
                                <div style="width: 100%; height: 200px; background: linear-gradient(135deg, #4fc3f7, #29b6f6); display: flex; align-items: center; justify-content: center;">
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
                                        <span><i class="fas fa-eye"></i> <?= $item['total_views'] ?? 0 ?> views</span>
                                        <?php if ($sort === 'popular'): ?>
                                        <div style="display: flex; gap: 10px; font-size: 0.85rem;">
                                            <span style="background: #e3f2fd; color: #1976d2; padding: 3px 8px; border-radius: 12px;">
                                                <i class="fas fa-eye"></i> <?= $item['total_views'] ?? 0 ?>
                                            </span>
                                            <span style="background: #fce4ec; color: #c2185b; padding: 3px 8px; border-radius: 12px;">
                                                <i class="fas fa-heart"></i> <?= $item['total_likes'] ?? 0 ?>
                                            </span>
                                            <span style="background: #e8f5e8; color: #388e3c; padding: 3px 8px; border-radius: 12px;">
                                                <i class="fas fa-comment"></i> <?= $item['total_comments'] ?? 0 ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top: 1.5rem; padding-top: 1.2rem; border-top: 1px solid #f1f3f4; display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; gap: 15px;">
                                            <a href="view_artikel.php?id=<?= $item['id'] ?>" style="background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3); position: relative; overflow: hidden; text-decoration: none;" onmouseover="this.style.transform='translateY(-5px) scale(1.1) rotateZ(2deg)'; this.style.boxShadow='0 10px 30px rgba(255, 107, 107, 0.7)'; this.querySelector('i').style.animation='heartbeat 0.8s ease-in-out infinite'; this.style.animation='button-rainbow 1s linear infinite'" onmouseout="this.style.transform='translateY(0) scale(1) rotateZ(0deg)'; this.style.boxShadow='0 2px 8px rgba(255, 107, 107, 0.3)'; this.querySelector('i').style.animation='none'; this.style.animation='none'">
                                                <i class="fas fa-heart"></i> <span>Like</span>
                                            </a>
                                            
                                            <style>
                                            @keyframes heartbeat {
                                                0%, 100% { transform: scale(1); }
                                                50% { transform: scale(1.3); }
                                            }
                                            @keyframes button-rainbow {
                                                0% { filter: hue-rotate(0deg); }
                                                100% { filter: hue-rotate(360deg); }
                                            }
                                            </style>
                                            <a href="view_artikel.php?id=<?= $item['id'] ?>#komentar" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(79, 195, 247, 0.3); text-decoration: none;" onmouseover="this.style.transform='translateY(-3px) scale(1.05)'; this.style.boxShadow='0 6px 20px rgba(79, 195, 247, 0.5)'; this.querySelector('i').style.animation='wave 0.6s ease-in-out'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 2px 8px rgba(79, 195, 247, 0.3)'; this.querySelector('i').style.animation='none'">
                                                <i class="fas fa-comment"></i> <span>Komentar</span>
                                            </a>
                                        </div>
                                        <button onclick="shareArticle('<?= htmlspecialchars($item['judul']) ?>', '<?= $_SERVER['HTTP_HOST'] ?>/E-Magazine/view_artikel.php?id=<?= $item['id'] ?>')" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(40, 167, 69, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(40, 167, 69, 0.3)'">
                                            <i class="fas fa-share"></i> <span>Bagikan</span>
                                        </button>
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
                        <a href="public.php" style="color: #4fc3f7; text-decoration: none;">← Kembali ke semua artikel</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Artikel Populer (Kanan - Sidebar) -->
                <?php if ($sort !== 'popular'): ?>
                <div class="artikel-populer">
                    <div class="section-header">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-fire"></i>
                            <h2>Artikel Populer</h2>
                        </div>
                        <a href="public.php?sort=popular" style="color: #4fc3f7; text-decoration: none; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.color='#29b6f6'; this.style.transform='translateX(3px)'" onmouseout="this.style.color='#4fc3f7'; this.style.transform='translateX(0)'">
                            Lihat Semua <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <?php if(isset($artikel_populer) && count($artikel_populer) > 0): ?>
                        <?php foreach($artikel_populer as $index => $populer): ?>
                        <div class="populer-card" style="--delay: <?= $index ?>; position: relative;">
                            <div class="populer-number"><?= $index + 1 ?></div>
                            <a href="view_artikel.php?id=<?= $populer['id'] ?>" style="display: flex; gap: 1rem; text-decoration: none; color: inherit; width: 100%;">
                                <?php if($populer['gambar'] && file_exists('uploads/' . $populer['gambar'])): ?>
                                <img src="uploads/<?= $populer['gambar'] ?>" alt="<?= htmlspecialchars($populer['judul']) ?>">
                                <?php else: ?>
                                <div class="populer-placeholder">
                                    <i class="fas fa-newspaper" style="color: white; font-size: 1.5rem; opacity: 0.7;"></i>
                                </div>
                                <?php endif; ?>
                                
                                <div class="populer-content">
                                    <h4 class="populer-title"><?= htmlspecialchars($populer['judul']) ?></h4>
                                    <div class="populer-meta">
                                        <span><i class="fas fa-user" style="margin-right: 5px;"></i><?= htmlspecialchars($populer['penulis']) ?></span>
                                        <div style="display: flex; gap: 8px; font-size: 0.75rem;">
                                            <span style="background: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 10px;">
                                                <i class="fas fa-eye"></i> <?= $populer['total_views'] ?? 0 ?>
                                            </span>
                                            <span style="background: #fce4ec; color: #c2185b; padding: 2px 6px; border-radius: 10px;">
                                                <i class="fas fa-heart"></i> <?= $populer['total_likes'] ?? 0 ?>
                                            </span>
                                            <span style="background: #e8f5e8; color: #388e3c; padding: 2px 6px; border-radius: 10px;">
                                                <i class="fas fa-comment"></i> <?= $populer['total_comments'] ?? 0 ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #adb5bd; margin-top: 0.3rem;">
                                        <i class="fas fa-calendar" style="margin-right: 3px;"></i>
                                        <?= date('d M Y', strtotime($populer['tanggal_publish'])) ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-fire" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>Belum ada artikel populer</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <p>&copy; 2024 E-Mading Sekolah. Semua hak dilindungi.</p>
            <p>Dibuat dengan ❤️ untuk kemajuan pendidikan</p>
        </div>
    </div>
    
    <script>
    // Fungsi untuk berbagi artikel
    function shareArticle(title, url) {
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            }).catch(console.error);
        } else {
            // Fallback untuk browser yang tidak mendukung Web Share API
            const shareText = `${title} - ${url}`;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareText).then(() => {
                    alert('Link artikel berhasil disalin ke clipboard!');
                }).catch(() => {
                    prompt('Salin link artikel ini:', shareText);
                });
            } else {
                prompt('Salin link artikel ini:', shareText);
            }
        }
    }
    </script>
</body>
</html>