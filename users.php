<?php
include 'auth_check.php';
include 'config/database.php';

// Hanya admin yang bisa akses
checkAuth('admin');

// Handle update role dan status user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ?, status = ? WHERE id = ?");
        $stmt->execute([$new_role, $new_status, $user_id]);
        $_SESSION['success'] = "User berhasil diupdate!";
        header('Location: users.php');
        exit;
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle hapus user
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $_SESSION['success'] = "User berhasil dihapus!";
        header('Location: users.php');
        exit;
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil semua users
try {
    $stmt = $pdo->query("SELECT u.*, COUNT(a.id) as total_artikel FROM users u 
                         LEFT JOIN artikel a ON u.id = a.user_id 
                         GROUP BY u.id 
                         ORDER BY 
                         CASE 
                             WHEN u.role = 'pending' THEN 1
                             WHEN u.status = 'inactive' THEN 2
                             ELSE 3
                         END,
                         u.created_at DESC");
    $users = $stmt->fetchAll();
} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - E-Mading</title>
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
        <h2>Users</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <h2 class="page-title">Manajemen User</h2>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h3><i class="fas fa-users"></i> Daftar User</h3>
                <?php if(isset($users) && count($users) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Total Artikel</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr style="<?= $user['role'] == 'pending' || $user['status'] == 'inactive' ? 'background-color: #fff3cd;' : '' ?>">
                            <td><?= $user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                            <td>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $user['status'] ?? 'active' ?>">
                                    <select name="role" onchange="this.form.submit()" style="padding: 2px 5px; border-radius: 3px; border: 1px solid #ddd;">
                                        <option value="pending" <?= $user['role'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="siswa" <?= $user['role'] == 'siswa' ? 'selected' : '' ?>>Siswa</option>
                                        <option value="guru" <?= $user['role'] == 'guru' ? 'selected' : '' ?>>Guru</option>
                                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <input type="hidden" name="update_user" value="1">
                                </form>
                                <?php else: ?>
                                <span class="badge badge-success"><?= ucfirst($user['role']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="role" value="<?= $user['role'] ?>">
                                    <select name="status" onchange="this.form.submit()" style="padding: 2px 5px; border-radius: 3px; border: 1px solid #ddd;">
                                        <option value="inactive" <?= ($user['status'] ?? 'active') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="active" <?= ($user['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Active</option>
                                    </select>
                                    <input type="hidden" name="update_user" value="1">
                                </form>
                                <?php else: ?>
                                <span class="badge badge-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $user['total_artikel'] ?> artikel</td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?hapus=<?= $user['id'] ?>" class="btn btn-sm" 
                                   style="background: #dc3545; color: white; padding: 5px 10px; font-size: 12px;"
                                   onclick="return confirm('Yakin ingin menghapus user ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                                <?php else: ?>
                                <span style="color: #666; font-size: 12px;">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>Belum Ada User</h3>
                </div>
                <?php endif; ?>
            </div>

            <!-- Role Information -->
            <div class="card">
                <h3><i class="fas fa-info-circle"></i> Informasi Role</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                        <h4 style="color: #28a745; margin-bottom: 10px;">
                            <i class="fas fa-crown"></i> Admin
                        </h4>
                        <p style="margin: 0; font-size: 14px; color: #666;">Akses penuh sistem, mengelola user, artikel, kategori, dan laporan.</p>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8;">
                        <h4 style="color: #17a2b8; margin-bottom: 10px;">
                            <i class="fas fa-chalkboard-teacher"></i> Guru
                        </h4>
                        <p style="margin: 0; font-size: 14px; color: #666;">Mereview dan memoderasi artikel dari siswa sebelum dipublikasi.</p>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <h4 style="color: #ffc107; margin-bottom: 10px;">
                            <i class="fas fa-user-graduate"></i> Siswa
                        </h4>
                        <p style="margin: 0; font-size: 14px; color: #666;">Menulis artikel dan mengirim ke guru untuk review dan persetujuan.</p>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #6c757d;">
                        <h4 style="color: #6c757d; margin-bottom: 10px;">
                            <i class="fas fa-clock"></i> Pending
                        </h4>
                        <p style="margin: 0; font-size: 14px; color: #666;">User baru yang belum ditentukan rolenya oleh admin.</p>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 8px; border: 1px solid #b3d9ff;">
                    <h4 style="color: #0066cc; margin-bottom: 10px;">
                        <i class="fas fa-info-circle"></i> Cara Mengelola User Baru
                    </h4>
                    <ol style="margin: 0; padding-left: 20px; color: #333;">
                        <li>User yang baru mendaftar akan muncul dengan role "Pending" dan status "Inactive"</li>
                        <li>Ubah role sesuai kebutuhan (Siswa/Guru/Admin)</li>
                        <li>Ubah status menjadi "Active" agar user dapat login</li>
                        <li>User akan mendapat akses sesuai role yang diberikan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>