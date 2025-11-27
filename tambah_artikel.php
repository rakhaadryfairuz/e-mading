<?php
include 'auth_check.php';
include 'config/database.php';

// Hanya siswa, guru dan admin yang bisa tambah artikel
checkAuth(['siswa', 'guru', 'admin']);

if ($_POST) {
    $judul = $_POST['judul'];
    $konten = $_POST['konten'];
    $action = $_POST['action'];
    $kategori_id = $_POST['kategori_id'] ?? null;
    
    // Validasi kategori
    if (empty($kategori_id)) {
        $error = "Pilih kategori untuk artikel!";
    } else {
        $status = ($action === 'send') ? 'pending' : 'draft';
        $tanggal_publish = null;
        
        // Handle upload gambar
        $gambar = null;
        if (isset($_FILES['gambar']) && !empty($_FILES['gambar']['name'])) {
            // Debug info
            $upload_error = $_FILES['gambar']['error'];
            $file_size = $_FILES['gambar']['size'];
            $tmp_name = $_FILES['gambar']['tmp_name'];
            
            if ($upload_error !== 0) {
                $error = "Error upload: " . $upload_error;
            } elseif ($file_size > 2 * 1024 * 1024) {
                $error = "File terlalu besar. Maksimal 2MB";
            } elseif (!is_uploaded_file($tmp_name)) {
                $error = "File tidak valid";
            } else {
                $file_extension = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($file_extension, $allowed_types)) {
                    $error = "Format tidak didukung: " . $file_extension;
                } else {
                    $target_dir = "uploads/";
                    $gambar = 'artikel_' . time() . '.' . $file_extension;
                    $target_file = $target_dir . $gambar;
                    
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0755, true);
                    }
                    
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        chmod($target_file, 0644);
                        // Verify file exists
                        if (!file_exists($target_file)) {
                            $gambar = null;
                            $error = "File tidak tersimpan: " . $target_file;
                        }
                    } else {
                        $gambar = null;
                        $error = "Move failed. Tmp: " . $tmp_name . ", Target: " . $target_file . ", Writable: " . (is_writable($target_dir) ? 'yes' : 'no');
                    }
                }
            }
        }
    
        if (!isset($error)) {
            try {
                // Insert artikel
                $stmt = $pdo->prepare("INSERT INTO artikel (judul, konten, gambar, kategori_id, user_id, status, tanggal_publish) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$judul, $konten, $gambar, $kategori_id, $_SESSION['user_id'], $status, $tanggal_publish]);
                
                if ($action === 'send') {
                    header('Location: dashboard_siswa.php?success=sent');
                    exit;
                } else {
                    $success = "Artikel berhasil disimpan sebagai draft!";
                }
            } catch(Exception $e) {
                // Delete uploaded file if database insert fails
                if ($gambar && file_exists('uploads/' . $gambar)) {
                    unlink('uploads/' . $gambar);
                }
                $error = "Error menyimpan artikel: " . $e->getMessage();
            }
        }
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
    <title>Tambah Artikel - E-Mading</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <?php 
                $dashboardUrl = getUserRole() === 'admin' ? 'admin_dashboard.php' : (getUserRole() === 'guru' ? 'dashboard_guru.php' : 'dashboard_siswa.php');
                ?>
                <a href="<?= $dashboardUrl ?>" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                <h1>Tulis Artikel</h1>
            </div>
            <div style="color: white; font-size: 14px;">
                <i class="fas fa-user"></i> <?= getUserName() ?> (<?= ucfirst(getUserRole()) ?>)
            </div>
        </div>
    </div>

    <div class="sidebar">
        <h2>Tulis Artikel</h2>
        <ul>
            <li><a href="<?= $dashboardUrl ?>"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Tambah Artikel Baru</h2>
            
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
                        <input type="text" name="judul" class="form-control" placeholder="Masukkan judul artikel" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori_id" class="form-control" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach($kategori as $kat): ?>
                            <option value="<?= $kat['id'] ?>"><?= $kat['nama_kategori'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Gambar Artikel (Opsional)</label>
                        <input type="file" name="gambar" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif" style="padding: 15px;">
                        <small style="color: #999; display: block; margin-top: 8px;">
                            <i class="fas fa-info-circle"></i> Upload gambar dari komputer Anda. Format: JPG, JPEG, PNG, GIF. Maksimal 2MB
                        </small>
                        <div id="image-preview" style="margin-top: 15px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Konten Artikel</label>
                        <textarea name="konten" class="form-control" rows="10" placeholder="Tulis konten artikel di sini..." required></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="action" value="draft" class="btn" style="background: #6c757d;">
                            <i class="fas fa-save"></i> Simpan Draft
                        </button>
                        <button type="submit" name="action" value="send" class="btn" style="background: #28a745;">
                            <i class="fas fa-paper-plane"></i> Kirim Artikel
                        </button>
                        <a href="my_articles.php" class="btn" style="background: #6c757d;">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Preview gambar
    document.querySelector('input[name="gambar"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('image-preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; height: auto; border-radius: 8px; border: 2px solid #ddd;">';
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '';
        }
    });
    
    // Validasi form sebelum submit
    document.querySelector('form').addEventListener('submit', function(e) {
        const judul = document.querySelector('input[name="judul"]').value.trim();
        const konten = document.querySelector('textarea[name="konten"]').value.trim();
        const kategori = document.querySelector('select[name="kategori_id"]').value;
        
        if (!judul) {
            e.preventDefault();
            alert('Judul artikel harus diisi!');
            return;
        }
        
        if (!konten) {
            e.preventDefault();
            alert('Konten artikel harus diisi!');
            return;
        }
        
        if (!kategori) {
            e.preventDefault();
            alert('Pilih kategori!');
            return;
        }
        
        // Konfirmasi untuk kirim artikel
        if (e.submitter && e.submitter.value === 'send') {
            if (!confirm('Yakin ingin mengirim artikel untuk review? Artikel akan dikirim ke guru untuk disetujui.')) {
                e.preventDefault();
                return;
            }
        }
    });
    </script>
</body>
</html>