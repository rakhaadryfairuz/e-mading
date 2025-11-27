<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['siswa', 'admin']);

$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Ambil data artikel
try {
    $stmt = $pdo->prepare("SELECT * FROM artikel WHERE id = ?");
    $stmt->execute([$id]);
    $artikel = $stmt->fetch();
    
    if (!$artikel) {
        $_SESSION['error'] = 'Artikel tidak ditemukan!';
        header('Location: my_articles.php');
        exit;
    }
    
    // Validasi kepemilikan artikel (kecuali admin)
    if ($user_role !== 'admin' && $artikel['user_id'] != $user_id) {
        $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengedit artikel ini!';
        header('Location: my_articles.php');
        exit;
    }
    
    // Siswa hanya bisa edit artikel dengan status tertentu
    if ($user_role === 'siswa' && !in_array($artikel['status'], ['draft', 'rejected', 'pending'])) {
        $_SESSION['error'] = 'Artikel yang sudah dipublikasikan tidak dapat diedit!';
        header('Location: my_articles.php');
        exit;
    }
    
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Handle kirim ke moderasi
if (isset($_POST['kirim_moderasi'])) {
    $judul = $_POST['judul'];
    $konten = $_POST['konten'];
    $kategori_id = $_POST['kategori_id'];
    
    // Handle upload gambar baru jika ada
    $gambar = $artikel['gambar'];
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "uploads/";
        $file_extension = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_types)) {
            $new_gambar = 'artikel_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $target_file = $target_dir . $new_gambar;
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                if ($artikel['gambar'] && file_exists($target_dir . $artikel['gambar'])) {
                    unlink($target_dir . $artikel['gambar']);
                }
                $gambar = $new_gambar;
            }
        }
    }
    
    try {
        // Update artikel dengan data terbaru dan kirim ke moderasi
        $stmt = $pdo->prepare("UPDATE artikel SET judul = ?, konten = ?, gambar = ?, kategori_id = ?, status = 'pending', rejection_reason = NULL WHERE id = ? AND user_id = ?");
        $stmt->execute([$judul, $konten, $gambar, $kategori_id, $id, $user_id]);
        $success = "Artikel berhasil direvisi dan dikirim ke moderasi!";
        
        // Refresh artikel data
        $stmt = $pdo->prepare("SELECT * FROM artikel WHERE id = ?");
        $stmt->execute([$id]);
        $artikel = $stmt->fetch();
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Update artikel
if ($_POST && !isset($_POST['kirim_moderasi'])) {
    $judul = $_POST['judul'];
    $konten = $_POST['konten'];
    $kategori_id = $_POST['kategori_id'];
    $status = $_POST['status'];
    
    // Validasi status untuk siswa
    if ($user_role === 'siswa' && !in_array($status, ['draft'])) {
        $status = 'draft'; // Paksa ke draft jika siswa mencoba status lain
    }
    
    $tanggal_publish = ($status == 'published') ? date('Y-m-d H:i:s') : null;
    
    // Handle upload gambar baru
    $gambar = $artikel['gambar'];
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "uploads/";
        
        // Pastikan folder uploads ada
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $error = "Gagal membuat folder uploads";
            }
        }
        
        // Cek ukuran file (max 5MB)
        if ($_FILES['gambar']['size'] > 5 * 1024 * 1024) {
            $error = "Ukuran file terlalu besar. Maksimal 5MB";
        } else {
            $file_extension = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_types)) {
                // Generate unique filename
                $new_gambar = 'artikel_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $target_file = $target_dir . $new_gambar;
                
                // Cek apakah tmp_name valid
                if (is_uploaded_file($_FILES['gambar']['tmp_name'])) {
                    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                        // Set permissions
                        chmod($target_file, 0644);
                        
                        // Verifikasi file berhasil diupload
                        if (file_exists($target_file)) {
                            // Hapus gambar lama jika ada
                            if ($artikel['gambar'] && file_exists($target_dir . $artikel['gambar'])) {
                                unlink($target_dir . $artikel['gambar']);
                            }
                            $gambar = $new_gambar;
                        } else {
                            $error = "File gagal disimpan ke server";
                        }
                    } else {
                        $error = "Gagal memindahkan file ke folder uploads. Periksa permission folder.";
                    }
                } else {
                    $error = "File upload tidak valid";
                }
            } else {
                $error = "Format gambar tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF";
            }
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != 4) {
        // Handle upload errors
        switch ($_FILES['gambar']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error = "File terlalu besar";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = "File hanya terupload sebagian";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error = "Folder temporary tidak ditemukan";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error = "Gagal menulis file ke disk";
                break;
            default:
                $error = "Error upload file: " . $_FILES['gambar']['error'];
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE artikel SET judul = ?, konten = ?, gambar = ?, kategori_id = ?, status = ?, tanggal_publish = ? WHERE id = ?");
        $stmt->execute([$judul, $konten, $gambar, $kategori_id, $status, $tanggal_publish, $id]);
        
        $_SESSION['success'] = "Artikel berhasil diupdate!";
        
        // Redirect ke halaman yang sesuai
        if ($user_role === 'admin') {
            header('Location: artikel.php');
        } else {
            header('Location: my_articles.php');
        }
        exit;
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil kategori
$stmt = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori");
$kategori = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Artikel - E-Mading</title>
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
        <h2>Edit Artikel</h2>
        <ul>
            <li><a href="<?= $user_role === 'admin' ? 'admin_dashboard.php' : 'dashboard_siswa.php' ?>"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Edit Artikel</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Judul Artikel</label>
                        <input type="text" name="judul" class="form-control" value="<?= htmlspecialchars($artikel['judul']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori_id" class="form-control" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach($kategori as $kat): ?>
                            <option value="<?= $kat['id'] ?>" <?= $artikel['kategori_id'] == $kat['id'] ? 'selected' : '' ?>>
                                <?= $kat['nama_kategori'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Gambar Saat Ini</label>
                        <?php if($artikel['gambar']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="uploads/<?= $artikel['gambar'] ?>" style="max-width: 200px; height: auto; border-radius: 5px;">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="gambar" class="form-control" accept="image/*">
                        <small style="color: #999;">Kosongkan jika tidak ingin mengubah gambar</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Konten Artikel</label>
                        <textarea name="konten" class="form-control" rows="10" required><?= htmlspecialchars($artikel['konten']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <?php if ($user_role === 'admin'): ?>
                                <option value="draft" <?= $artikel['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="pending" <?= $artikel['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= $artikel['status'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="published" <?= $artikel['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="rejected" <?= $artikel['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <?php else: ?>
                                <option value="draft" <?= $artikel['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                <?php if ($artikel['status'] === 'pending'): ?>
                                    <option value="pending" selected>Pending Review</option>
                                <?php endif; ?>
                                <?php if ($artikel['status'] === 'rejected'): ?>
                                    <option value="draft">Ubah ke Draft</option>
                                <?php endif; ?>
                            <?php endif; ?>
                        </select>
                        <?php if ($user_role === 'siswa'): ?>
                            <small style="color: #999;">Siswa hanya dapat mengubah status ke Draft</small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($user_role === 'siswa' && $artikel['status'] === 'rejected'): ?>
                    <button type="submit" name="kirim_moderasi" class="btn" style="background: #28a745;" onclick="return confirm('Kirim artikel ini ke moderasi guru?')">
                        <i class="fas fa-paper-plane"></i> Kirim ke Moderasi
                    </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>