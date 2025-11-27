<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth();

$message = '';

// Handle update foto profil
if (isset($_POST['update_foto']) && isset($_FILES['foto_profil'])) {
    $file = $_FILES['foto_profil'];
    
    // Debug info
    $debug_info = "File error: " . $file['error'] . ", Size: " . $file['size'] . ", Name: " . $file['name'];
    
    if ($file['error'] === 0 && $file['size'] > 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed) && $file['size'] <= 2097152) { // 2MB
            // Gunakan folder uploads yang sudah ada (bukan subfolder)
            $upload_dir = 'uploads/';
            $new_filename = 'profil_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            // Cek apakah folder writable
            if (!is_writable($upload_dir)) {
                $message = '<div class="alert alert-danger">Folder uploads tidak dapat ditulis! Permission: ' . substr(sprintf('%o', fileperms($upload_dir)), -4) . '</div>';
            } else {
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    try {
                        // Cek apakah kolom foto_profil ada
                        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'foto_profil'");
                        if ($stmt->rowCount() == 0) {
                            // Tambah kolom jika belum ada
                            $pdo->exec("ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) NULL");
                        }
                        
                        $stmt = $pdo->prepare("UPDATE users SET foto_profil = ? WHERE id = ?");
                        $stmt->execute([$new_filename, $_SESSION['user_id']]);
                        $message = '<div class="alert alert-success">Foto profil berhasil diupdate!</div>';
                    } catch(Exception $e) {
                        $message = '<div class="alert alert-danger">Error database: ' . $e->getMessage() . '</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">Gagal upload file! Debug: ' . $debug_info . '</div>';
                }
            }
        } else {
            $message = '<div class="alert alert-danger">File tidak valid! Gunakan JPG/PNG/GIF maksimal 2MB. Debug: ' . $debug_info . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Error upload! Debug: ' . $debug_info . '</div>';
    }
}

// Handle update bio/profil
if ($_POST && !isset($_POST['update_foto'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'] ?? '';
    $bio = $_POST['bio'] ?? '';

    
    try {
        // Cek apakah kolom bio ada
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'bio'");
        if ($stmt->rowCount() == 0) {
            // Tambah kolom jika belum ada
            $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT NULL");
        }
        
        $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, bio = ? WHERE id = ?");
        $stmt->execute([$nama, $email, $bio, $_SESSION['user_id']]);
        
        $_SESSION['nama'] = $nama;
        $message = '<div class="alert alert-success">Bio berhasil diupdate!</div>';
    } catch(Exception $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Ambil data user
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(Exception $e) {
    $user = ['nama' => $_SESSION['nama'], 'username' => '', 'email' => '', 'role' => $_SESSION['role'], 'created_at' => date('Y-m-d')];
}

// Ambil artikel user (2 terbaru untuk preview)
try {
    $stmt = $pdo->prepare("SELECT judul, created_at, status FROM artikel WHERE user_id = ? ORDER BY created_at DESC LIMIT 2");
    $stmt->execute([$_SESSION['user_id']]);
    $articles = $stmt->fetchAll();
} catch(Exception $e) {
    $articles = [];
}

// Ambil semua artikel user untuk tabel
try {
    $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori FROM artikel a LEFT JOIN kategori k ON a.kategori_id = k.id WHERE a.user_id = ? ORDER BY a.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $allArticles = $stmt->fetchAll();
} catch(Exception $e) {
    $allArticles = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - E-Magazine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <!-- Fixed Navbar -->
    <nav style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <a href="logout.php" style="background: rgba(79, 195, 247, 0.9); color: white; padding: 12px 20px; border-radius: 30px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
    
    <!-- Back Button -->
    <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        <?php 
        $dashboardUrl = 'dashboard_siswa.php';
        if($_SESSION['role'] === 'admin') {
            $dashboardUrl = 'admin_dashboard.php';
        } elseif($_SESSION['role'] === 'guru') {
            $dashboardUrl = 'dashboard_guru.php';
        }
        ?>
        <a href="<?= $dashboardUrl ?>" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 20px; border-radius: 30px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Profile Container -->
    <div style="min-height: 100vh; background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 50%, #03a9f4 100%); padding: 80px 20px 40px; display: flex; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; padding: 60px; max-width: 900px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.1); position: relative;">
            <?= $message ?>
            
            <?= $message ?>
            
            <!-- Profile Header -->
            <div style="display: flex; align-items: center; gap: 40px; margin-bottom: 40px;">
                <!-- Profile Image -->
                <div style="width: 200px; height: 200px; border-radius: 50%; border: 8px solid #4fc3f7; box-shadow: 0 10px 30px rgba(79, 195, 247, 0.3); flex-shrink: 0; overflow: hidden; background: linear-gradient(135deg, #4fc3f7, #29b6f6); display: flex; align-items: center; justify-content: center;">
                    <?php if (!empty($user['foto_profil']) && file_exists('uploads/' . $user['foto_profil'])): ?>
                        <img src="uploads/<?= htmlspecialchars($user['foto_profil']) ?>" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user" style="font-size: 5rem; color: white;"></i>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Info -->
                <div style="flex: 1;">
                    <h1 style="font-size: 3rem; font-weight: bold; color: #0277bd; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 2px;"><?= strtoupper(htmlspecialchars($_SESSION['nama'])) ?></h1>
                    <p style="font-size: 1.5rem; color: #546e7a; margin: 0 0 20px 0; font-weight: 500;"><?= ucfirst($user['role'] ?? $_SESSION['role']) ?></p>
                    <p style="font-size: 1.2rem; color: #78909c; margin: 0 0 20px 0;">E-Magazine</p>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div style="height: 8px; background: #e0e0e0; border-radius: 4px; margin-bottom: 40px; overflow: hidden;">
                <div style="width: 30%; height: 100%; background: #4fc3f7; border-radius: 4px;"></div>
            </div>
            
            <!-- Content Sections -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                <!-- Bio Section -->
                <div style="background: rgba(79, 195, 247, 0.05); padding: 30px; border-radius: 15px; border: 1px solid rgba(79, 195, 247, 0.2);">
                    <h2 style="font-size: 1.8rem; font-weight: bold; color: #0277bd; margin: 0 0 20px 0;">Bio</h2>
                    <p style="color: #546e7a; line-height: 1.6; margin: 0;"><?= htmlspecialchars($user['bio'] ?? 'Passionate in digital storytelling & data-driven strategy. Proven track record in boosting brand awareness and engagement for diverse clients.') ?></p>
                </div>
                
                <!-- Artikel Section -->
                <div style="background: rgba(79, 195, 247, 0.05); padding: 30px; border-radius: 15px; border: 1px solid rgba(79, 195, 247, 0.2);">
                    <h2 style="font-size: 1.8rem; font-weight: bold; color: #0277bd; margin: 0 0 20px 0;">Artikel Saya</h2>
                    <?php if (empty($articles)): ?>
                        <p style="color: #78909c; font-style: italic;">Belum ada artikel yang ditulis</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach ($articles as $article): ?>
                                <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: white; border-radius: 8px; border-left: 4px solid #4fc3f7;">
                                    <div style="width: 8px; height: 8px; background: <?= $article['status'] === 'published' ? '#28a745' : ($article['status'] === 'pending' ? '#ffc107' : '#dc3545') ?>; border-radius: 50%; margin-top: 6px; flex-shrink: 0;"></div>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 5px 0; color: #0277bd; font-size: 0.95rem; font-weight: 600; line-height: 1.3;"><?= htmlspecialchars(substr($article['judul'], 0, 50)) ?><?= strlen($article['judul']) > 50 ? '...' : '' ?></h4>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <small style="color: #78909c;"><?= date('d M Y', strtotime($article['created_at'])) ?></small>
                                            <span style="font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; background: <?= $article['status'] === 'published' ? '#d4edda' : ($article['status'] === 'pending' ? '#fff3cd' : '#f8d7da') ?>; color: <?= $article['status'] === 'published' ? '#155724' : ($article['status'] === 'pending' ? '#856404' : '#721c24') ?>;"><?= ucfirst($article['status']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="text-align: center; margin-top: 15px;">
                            <button onclick="toggleAllArticles()" style="background: #4fc3f7; color: white; border: none; padding: 8px 20px; border-radius: 20px; font-weight: 500; font-size: 0.9rem; cursor: pointer;">
                                <i class="fas fa-table"></i> Lihat Semua Artikel
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 40px; flex-wrap: wrap;">
                <button onclick="togglePhotoEdit()" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); color: white; padding: 12px 25px; border: none; border-radius: 25px; font-size: 1rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 195, 247, 0.3); transition: all 0.3s ease;">
                    <i class="fas fa-camera"></i> Edit Foto
                </button>
                <button onclick="toggleBioEdit()" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); color: white; padding: 12px 25px; border: none; border-radius: 25px; font-size: 1rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 195, 247, 0.3); transition: all 0.3s ease;">
                    <i class="fas fa-user-edit"></i> Edit Bio
                </button>
            </div>
            
            <!-- Edit Foto Form (Hidden by default) -->
            <div id="photoEditForm" style="display: none; margin-top: 30px; padding: 30px; background: rgba(79, 195, 247, 0.05); border-radius: 15px; border: 1px solid rgba(79, 195, 247, 0.2);">
                <h3 style="color: #0277bd; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-camera"></i> Edit Foto Profil
                </h3>
                <form method="POST" enctype="multipart/form-data">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 10px; color: #0277bd; font-weight: 500;">Upload Foto Profil</label>
                        <input type="file" name="foto_profil" accept="image/*" style="width: 100%; padding: 12px; border: 2px solid rgba(79, 195, 247, 0.2); border-radius: 8px; font-size: 1rem;">
                        <small style="color: #78909c; margin-top: 5px; display: block;">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <button type="submit" name="update_foto" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); color: white; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-upload"></i> Upload Foto
                        </button>
                        <button type="button" onclick="togglePhotoEdit()" style="background: #78909c; color: white; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Edit Bio Form (Hidden by default) -->
            <div id="bioEditForm" style="display: none; margin-top: 30px; padding: 30px; background: rgba(79, 195, 247, 0.05); border-radius: 15px; border: 1px solid rgba(79, 195, 247, 0.2);">
                <h3 style="color: #0277bd; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-user-edit"></i> Edit Bio & Info
                </h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #0277bd; font-weight: 500;">Nama Lengkap</label>
                            <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required style="width: 100%; padding: 12px; border: 2px solid rgba(79, 195, 247, 0.2); border-radius: 8px; font-size: 1rem;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #0277bd; font-weight: 500;">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" style="width: 100%; padding: 12px; border: 2px solid rgba(79, 195, 247, 0.2); border-radius: 8px; font-size: 1rem;">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; color: #0277bd; font-weight: 500;">Bio</label>
                        <textarea name="bio" rows="4" placeholder="Tulis bio Anda..." style="width: 100%; padding: 12px; border: 2px solid rgba(79, 195, 247, 0.2); border-radius: 8px; font-size: 1rem; resize: vertical;"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <button type="submit" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); color: white; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-save"></i> Simpan Bio
                        </button>
                        <button type="button" onclick="toggleBioEdit()" style="background: #78909c; color: white; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Tabel Semua Artikel (Hidden by default) -->
            <div id="allArticlesTable" style="display: none; margin-top: 30px; background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(79, 195, 247, 0.1); border: 1px solid rgba(79, 195, 247, 0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="font-size: 1.8rem; font-weight: bold; color: #0277bd; margin: 0;">Semua Artikel Saya</h2>
                    <button onclick="toggleAllArticles()" style="background: #78909c; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer;">
                        <i class="fas fa-times"></i> Tutup
                    </button>
                </div>
                
                <?php if (!empty($allArticles)): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(79, 195, 247, 0.1);">
                            <thead>
                                <tr style="background: linear-gradient(135deg, rgba(79, 195, 247, 0.1) 0%, rgba(41, 182, 246, 0.05) 100%);">
                                    <th style="padding: 16px; text-align: left; color: #0277bd; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Judul</th>
                                    <th style="padding: 16px; text-align: left; color: #0277bd; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Kategori</th>
                                    <th style="padding: 16px; text-align: center; color: #0277bd; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                                    <th style="padding: 16px; text-align: center; color: #0277bd; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Tanggal</th>
                                    <th style="padding: 16px; text-align: center; color: #0277bd; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allArticles as $article): ?>
                                    <tr style="border-bottom: 1px solid rgba(79, 195, 247, 0.1); transition: all 0.3s ease;" onmouseover="this.style.background='rgba(79, 195, 247, 0.05)'; this.style.transform='scale(1.01)'" onmouseout="this.style.background=''; this.style.transform='scale(1)'">
                                        <td style="padding: 16px; color: #2c3e50; font-weight: 500;"><?= htmlspecialchars($article['judul']) ?></td>
                                        <td style="padding: 16px; color: #546e7a;"><?= htmlspecialchars($article['nama_kategori'] ?? 'Tidak ada') ?></td>
                                        <td style="padding: 16px; text-align: center;">
                                            <span style="padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; background: <?= $article['status'] === 'published' ? 'linear-gradient(135deg, #4caf50, #66bb6a)' : ($article['status'] === 'pending' ? 'linear-gradient(135deg, #ff9800, #ffb74d)' : ($article['status'] === 'approved' ? 'linear-gradient(135deg, #4fc3f7, #29b6f6)' : 'linear-gradient(135deg, #f44336, #ef5350)')) ?>; color: white;">
                                                <?= ucfirst($article['status']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 16px; text-align: center; color: #78909c; font-size: 0.85rem;"><?= date('d/m/Y', strtotime($article['created_at'])) ?></td>
                                        <td style="padding: 16px; text-align: center;">
                                            <?php if ($article['status'] === 'published'): ?>
                                                <a href="view_artikel.php?id=<?= $article['id'] ?>&ref=profil" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); color: white; padding: 8px 16px; border-radius: 25px; text-decoration: none; font-size: 12px; font-weight: 500; transition: all 0.3s ease;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #78909c;">
                        <i class="fas fa-file-alt" style="font-size: 4rem; color: #b0bec5; margin-bottom: 20px; opacity: 0.7;"></i>
                        <h3 style="color: #78909c; font-weight: 500; font-size: 1.3rem;">Belum Ada Artikel</h3>
                        <p style="color: #90a4ae; font-size: 14px; margin-top: 10px;">Mulai menulis artikel pertama Anda</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    
    <script>
    function togglePhotoEdit() {
        const form = document.getElementById('photoEditForm');
        const bioForm = document.getElementById('bioEditForm');
        const articleTable = document.getElementById('allArticlesTable');
        
        // Tutup form lain
        bioForm.style.display = 'none';
        articleTable.style.display = 'none';
        
        // Toggle foto form
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
    
    function toggleBioEdit() {
        const form = document.getElementById('bioEditForm');
        const photoForm = document.getElementById('photoEditForm');
        const articleTable = document.getElementById('allArticlesTable');
        
        // Tutup form lain
        photoForm.style.display = 'none';
        articleTable.style.display = 'none';
        
        // Toggle bio form
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
    
    function toggleAllArticles() {
        const table = document.getElementById('allArticlesTable');
        const photoForm = document.getElementById('photoEditForm');
        const bioForm = document.getElementById('bioEditForm');
        
        // Tutup form lain
        photoForm.style.display = 'none';
        bioForm.style.display = 'none';
        
        // Toggle artikel table
        table.style.display = table.style.display === 'none' ? 'block' : 'none';
    }
    </script>
</body>
</html>