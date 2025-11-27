<?php
include 'config/database.php';
include 'auth_check.php';

$id = $_GET['id'] ?? 0;

// Ambil artikel dan update views
try {
    // Cek dan buat kolom views jika belum ada
    $stmt = $pdo->query("SHOW COLUMNS FROM artikel LIKE 'views'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE artikel ADD COLUMN views INT DEFAULT 0");
    }
    
    $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori, u.nama as penulis, u.foto_profil as penulis_foto FROM artikel a 
                           LEFT JOIN kategori k ON a.kategori_id = k.id 
                           LEFT JOIN users u ON a.user_id = u.id 
                           WHERE a.id = ? AND a.status = 'published'");
    $stmt->execute([$id]);
    $artikel = $stmt->fetch();
    
    if (!$artikel) {
        header('Location: public.php');
        exit;
    }
    
    // Update views count
    $stmt = $pdo->prepare("UPDATE artikel SET views = COALESCE(views, 0) + 1 WHERE id = ?");
    $stmt->execute([$id]);
    
} catch(Exception $e) {
    header('Location: public.php');
    exit;
}

// Handle like
if ($_POST && isset($_POST['like'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    try {
        // Cek apakah tabel likes ada, jika tidak buat
        $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            artikel_id INT NOT NULL,
            user_id INT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_like (artikel_id, user_id),
            UNIQUE KEY unique_ip_like (artikel_id, ip_address)
        )");
        
        if ($user_id) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO likes (artikel_id, user_id) VALUES (?, ?)");
            $stmt->execute([$id, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT IGNORE INTO likes (artikel_id, ip_address) VALUES (?, ?)");
            $stmt->execute([$id, $ip_address]);
        }
        
        // Redirect untuk refresh data
        header("Location: view_artikel.php?id=$id");
        exit;
    } catch(Exception $e) {
        $like_error = "Error: " . $e->getMessage();
    }
}

// Handle komentar
if ($_POST && isset($_POST['komentar'])) {
    $komentar_text = $_POST['komentar'];
    $user_id = $_SESSION['user_id'] ?? null;
    $nama = $_POST['nama'] ?? null;
    $email = $_POST['email'] ?? null;
    
    try {
        if ($user_id) {
            $stmt = $pdo->prepare("INSERT INTO komentar (artikel_id, user_id, komentar, status) VALUES (?, ?, ?, 'approved')");
            $stmt->execute([$id, $user_id, $komentar_text]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO komentar (artikel_id, nama, email, komentar, status) VALUES (?, ?, ?, ?, 'approved')");
            $stmt->execute([$id, $nama, $email, $komentar_text]);
        }
        $success = "Komentar berhasil ditambahkan!";
        
        // Refresh halaman untuk menampilkan komentar baru
        header("Location: view_artikel.php?id=$id#komentar");
        exit;
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil jumlah likes
$total_likes = 0;
$user_liked = false;

try {
    // Cek apakah tabel likes ada
    $stmt = $pdo->query("SHOW TABLES LIKE 'likes'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE artikel_id = ?");
        $stmt->execute([$id]);
        $total_likes = $stmt->fetchColumn();
        
        // Cek apakah user sudah like
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE artikel_id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $user_liked = $stmt->fetchColumn() > 0;
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE artikel_id = ? AND ip_address = ?");
            $stmt->execute([$id, $_SERVER['REMOTE_ADDR']]);
            $user_liked = $stmt->fetchColumn() > 0;
        }
    }
} catch(Exception $e) {
    // Tabel likes belum ada, gunakan default values
    $total_likes = 0;
    $user_liked = false;
}

// Ambil semua komentar (tidak perlu approved untuk demo)
$stmt = $pdo->prepare("SELECT k.*, u.nama as user_nama, u.foto_profil as user_foto FROM komentar k 
                       LEFT JOIN users u ON k.user_id = u.id 
                       WHERE k.artikel_id = ? 
                       ORDER BY k.created_at DESC");
$stmt->execute([$id]);
$komentar = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($artikel['judul']) ?> - E-Mading</title>
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
            background: #f8f9fa;
        }
        
        .header {
            color: white;
            padding: 1rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            animation: header-shine 6s linear infinite;
        }
        
        @keyframes header-shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .back-btn {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            color: white;
            text-decoration: none;
        }
        
        .artikel-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            animation: content-entrance 1s ease-out;
        }
        
        @keyframes content-entrance {
            0% { transform: translateY(50px) scale(0.9); opacity: 0; }
            100% { transform: translateY(0) scale(1); opacity: 1; }
        }
        

        
        @keyframes gradient-flow {
            0% { background-position: 0% 0%; }
            100% { background-position: 200% 0%; }
        }
        
        .artikel-meta {
            color: #666;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .artikel-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .artikel-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .artikel-text {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
        }
        
        .like-section {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .like-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .like-btn:hover {
            transform: translateY(-8px) scale(1.15) rotateZ(-3deg);
            box-shadow: 0 15px 40px rgba(255, 107, 107, 0.6);
            animation: like-pulse 0.8s ease-in-out infinite, like-glow 2s linear infinite;
        }
        
        @keyframes like-pulse {
            0%, 100% { transform: translateY(-8px) scale(1.15) rotateZ(-3deg); }
            50% { transform: translateY(-10px) scale(1.2) rotateZ(3deg); }
        }
        
        @keyframes like-glow {
            0%, 100% { box-shadow: 0 15px 40px rgba(255, 107, 107, 0.6), 0 0 30px rgba(255, 107, 107, 0.4); }
            50% { box-shadow: 0 15px 40px rgba(255, 107, 107, 0.8), 0 0 50px rgba(255, 107, 107, 0.6); }
        }
        
        .like-btn.liked {
            background: linear-gradient(135deg, #dc3545, #c82333);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            opacity: 0.8;
        }
        
        .like-btn.liked:hover {
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }
        
        .komentar-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn {
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(79, 195, 247, 0.4);
        }
        
        .komentar-item {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            animation: comment-slide-in 0.8s ease-out;
            animation-delay: calc(var(--delay, 0) * 0.2s);
        }
        
        .komentar-item:hover {
            transform: translateY(-3px);
        }
        
        @keyframes comment-slide-in {
            0% { transform: translateX(-50px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }
        

        

        
        .komentar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .komentar-author {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .komentar-date {
            font-size: 0.85rem;
            color: #6c757d;
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
        }
        
        .komentar-text {
            color: #495057;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Floating Elements */
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float-shape 8s linear infinite;
        }
        
        .shape:nth-child(1) {
            left: 5%;
            animation-delay: 0s;
            font-size: 2rem;
        }
        
        .shape:nth-child(2) {
            left: 15%;
            animation-delay: 2s;
            font-size: 1.5rem;
        }
        
        .shape:nth-child(3) {
            left: 25%;
            animation-delay: 4s;
            font-size: 2.5rem;
        }
        
        .shape:nth-child(4) {
            left: 35%;
            animation-delay: 1s;
            font-size: 1.8rem;
        }
        
        .shape:nth-child(5) {
            left: 45%;
            animation-delay: 3s;
            font-size: 2.2rem;
        }
        
        .shape:nth-child(6) {
            left: 55%;
            animation-delay: 5s;
            font-size: 1.6rem;
        }
        
        .shape:nth-child(7) {
            left: 65%;
            animation-delay: 0.5s;
            font-size: 2.3rem;
        }
        
        .shape:nth-child(8) {
            left: 75%;
            animation-delay: 2.5s;
            font-size: 1.7rem;
        }
        
        .shape:nth-child(9) {
            left: 85%;
            animation-delay: 4.5s;
            font-size: 2.1rem;
        }
        
        .shape:nth-child(10) {
            left: 95%;
            animation-delay: 1.5s;
            font-size: 1.9rem;
        }
        
        @keyframes float-shape {
            0% { transform: translateY(100vh) rotate(0deg); }
            100% { transform: translateY(-100px) rotate(360deg); }
        }
        
        /* Reading Progress Bar */
        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background: linear-gradient(90deg, #4fc3f7, #29b6f6, #03a9f4);
            z-index: 1000;
            transition: width 0.3s ease;
        }
        
        /* Decorative Corner Elements */
        .corner-deco {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            animation: corner-pulse 3s ease-in-out infinite;
        }
        
        @keyframes corner-pulse {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.3; }
            50% { transform: scale(1.3) rotate(180deg); opacity: 0.6; }
        }
    </style>
</head>
<body>
    <!-- Reading Progress Bar -->
    <div class="reading-progress" id="readingProgress"></div>
    
    <!-- Floating Shapes -->
    <div class="floating-shapes">
        <i class="fas fa-star shape"></i>
        <i class="fas fa-heart shape"></i>
        <i class="fas fa-bookmark shape"></i>
        <i class="fas fa-thumbs-up shape"></i>
        <i class="fas fa-comment shape"></i>
        <i class="fas fa-share shape"></i>
        <i class="fas fa-eye shape"></i>
        <i class="fas fa-lightbulb shape"></i>
        <i class="fas fa-magic shape"></i>
        <i class="fas fa-gem shape"></i>
    </div>

    
    <!-- Header -->
    <div class="header" style="position: relative;">
        <!-- Corner Decorations -->
        <div class="corner-deco" style="top: 20px; left: 20px;"></div>
        <div class="corner-deco" style="top: 20px; right: 20px; animation-delay: 1s;"></div>
        <div class="corner-deco" style="bottom: 20px; left: 50%; transform: translateX(-50%); animation-delay: 2s;"></div>
        
        <!-- Back Button -->
        <a href="public.php" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); color: white; padding: 12px 20px; border-radius: 25px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px); transition: all 0.3s ease; z-index: 1001;" onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
        </a>
        
        <div class="container">
            <h1><i class="fas fa-newspaper"></i> E-Mading Sekolah</h1>
        </div>
    </div>

    <div class="container">
        <?php 
        $ref = $_GET['ref'] ?? '';
        if ($ref === 'profil') {
            $back_url = 'profil.php';
            $back_text = 'Kembali ke Profil';
        } else {
            $back_url = 'public.php';
            $back_text = 'Kembali ke Beranda';
        }
        ?>
        <a href="<?= $back_url ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> <?= $back_text ?>
        </a>

        <!-- Artikel -->
        <div class="artikel-content">
            <div class="artikel-meta">
                <span><i class="fas fa-tag"></i> <?= htmlspecialchars($artikel['nama_kategori']) ?></span> |
                <span style="display: inline-flex; align-items: center; gap: 5px;">
                    <?php if($artikel['penulis_foto'] && file_exists('uploads/' . $artikel['penulis_foto'])): ?>
                        <img src="uploads/<?= $artikel['penulis_foto'] ?>" alt="Penulis" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($artikel['penulis']) ?>
                </span> |
                <span><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($artikel['tanggal_publish'])) ?></span> |
                <span><i class="fas fa-eye"></i> <?= $artikel['views'] ?? 0 ?> views</span>
            </div>
            
            <h1 class="artikel-title"><?= htmlspecialchars($artikel['judul']) ?></h1>
            
            <?php if($artikel['gambar']): ?>
            <img src="uploads/<?= $artikel['gambar'] ?>" alt="<?= htmlspecialchars($artikel['judul']) ?>" class="artikel-image">
            <?php endif; ?>
            
            <div class="artikel-text">
                <?= nl2br(htmlspecialchars($artikel['konten'])) ?>
            </div>
        </div>

        <!-- Like Section -->
        <div class="like-section">
            <div style="display: flex; justify-content: center; gap: 15px; align-items: center;">
                <form method="POST" style="display: inline;" id="likeForm">
                    <button type="submit" name="like" class="like-btn <?= $user_liked ? 'liked' : '' ?>" <?= $user_liked ? 'disabled' : '' ?> id="likeBtn">
                        <i class="fas fa-heart"></i> 
                        <span id="likeText"><?= $user_liked ? 'Disukai' : 'Suka' ?></span> (<span id="likeCount"><?= $total_likes ?></span>)
                    </button>
                </form>
            </div>
            
            <?php if(isset($like_error)): ?>
            <div class="alert alert-danger" style="margin-top: 1rem;"><?= $like_error ?></div>
            <?php endif; ?>
        </div>

        <!-- Komentar -->
        <div class="komentar-section" id="komentar">
            <h3><i class="fas fa-comments"></i> Komentar (<?= count($komentar) ?>)</h3>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <!-- Form Komentar -->
            <form method="POST" style="margin: 2rem 0;">
                <div class="form-group">
                    <label>Komentar</label>
                    <textarea name="komentar" class="form-control" rows="4" placeholder="Tulis komentar Anda..." required></textarea>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Kirim Komentar
                </button>
            </form>

            <!-- Daftar Komentar -->
            <?php if(count($komentar) > 0): ?>
            <div style="margin-top: 2rem;">
                <?php foreach($komentar as $index => $kom): ?>
                <div class="komentar-item" style="--delay: <?= $index ?>;">
                    <div class="komentar-header">
                        <div class="komentar-author">
                            <?php if($kom['user_foto'] && file_exists('uploads/' . $kom['user_foto'])): ?>
                                <img src="uploads/<?= $kom['user_foto'] ?>" alt="User" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 2px solid #4fc3f7;">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="color: #4fc3f7; font-size: 1.2rem;"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($kom['user_nama'] ?? $kom['nama']) ?>
                        </div>
                        <div class="komentar-date">
                            <i class="fas fa-clock" style="margin-right: 4px;"></i>
                            <?= date('d M Y H:i', strtotime($kom['created_at'])) ?>
                        </div>
                    </div>
                    <div class="komentar-text">
                        <?= nl2br(htmlspecialchars($kom['komentar'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 2rem; color: #666;">
                <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <p>Belum ada komentar. Jadilah yang pertama berkomentar!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Reading Progress Script -->
    <script>
    window.addEventListener('scroll', function() {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        document.getElementById('readingProgress').style.width = scrolled + '%';
    });
    
    
    // Animasi tombol like
    document.getElementById('likeForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('likeBtn');
        if (!btn.disabled) {
            btn.style.transform = 'scale(0.9)';
            setTimeout(() => {
                btn.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    btn.style.transform = 'scale(1)';
                }, 150);
            }, 100);
        }
    });
    </script>
</body>
</html>