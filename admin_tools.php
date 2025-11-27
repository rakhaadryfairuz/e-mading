<?php
include 'auth_check.php';
include 'config/database.php';

checkAuth(['admin']);

$action = $_GET['action'] ?? 'dashboard';
$message = '';

// Handle actions
if ($_POST) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'fix_status_enum':
                $pdo->exec("ALTER TABLE artikel MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'published', 'rejected') DEFAULT 'draft'");
                $message = "✅ Status enum berhasil diperbaiki";
                break;
                
            case 'fix_role_column':
                $pdo->exec("UPDATE users SET role = 'anggota' WHERE role NOT IN ('admin', 'anggota', 'guru')");
                $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'anggota', 'guru') NOT NULL");
                $message = "✅ Role column berhasil diperbaiki";
                break;
                
            case 'create_test_article':
                $stmt = $pdo->query("SELECT id FROM users WHERE role = 'anggota' LIMIT 1");
                $anggota = $stmt->fetch();
                if ($anggota) {
                    $stmt = $pdo->prepare("INSERT INTO artikel (judul, konten, user_id, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
                    $stmt->execute([
                        'Artikel Test untuk Moderasi',
                        'Ini adalah artikel test yang dibuat untuk menguji fitur moderasi.',
                        $anggota['id']
                    ]);
                    $message = "✅ Artikel test berhasil dibuat";
                } else {
                    $message = "❌ Tidak ada user anggota";
                }
                break;
                
            case 'clean_test_data':
                $pdo->exec("DELETE FROM artikel WHERE judul LIKE '%test%' OR judul LIKE '%Test%'");
                $pdo->exec("DELETE FROM users WHERE username LIKE 'test_%'");
                $message = "✅ Data test berhasil dibersihkan";
                break;
                
            case 'fix_database':
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!in_array('status', $columns)) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
                }
                if (!in_array('last_login', $columns)) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL");
                }
                $message = "✅ Database structure berhasil diperbaiki";
                break;
                
            case 'setup_multi_category':
                $pdo->exec("CREATE TABLE IF NOT EXISTS artikel_kategori (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    artikel_id INT NOT NULL,
                    kategori_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE,
                    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_artikel_kategori (artikel_id, kategori_id)
                )");
                
                $stmt = $pdo->query("SELECT id, kategori_id FROM artikel WHERE kategori_id IS NOT NULL");
                $existing_articles = $stmt->fetchAll();
                
                foreach ($existing_articles as $article) {
                    try {
                        $pdo->prepare("INSERT IGNORE INTO artikel_kategori (artikel_id, kategori_id) VALUES (?, ?)")
                            ->execute([$article['id'], $article['kategori_id']]);
                    } catch (Exception $e) {}
                }
                $message = "✅ Multi-category setup berhasil";
                break;
        }
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}

// Get system status
$status = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $status['users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM artikel");
    $status['articles'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status = 'pending'");
    $status['pending'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status = 'published'");
    $status['published'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $status['error'] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tools - E-Magazine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h1>Admin Tools</h1>
            <div style="color: white; font-size: 14px;">
                <i class="fas fa-user"></i> <?= getUserName() ?> (<?= ucfirst(getUserRole()) ?>)
            </div>
        </div>
    </div>

    <div class="sidebar">
        <h2>Tools</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a></li>
            <li><a href="?action=dashboard" class="<?= $action == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Status Sistem</a></li>
            <li><a href="?action=database" class="<?= $action == 'database' ? 'active' : '' ?>"><i class="fas fa-database"></i> Database Tools</a></li>
            <li><a href="?action=users" class="<?= $action == 'users' ? 'active' : '' ?>"><i class="fas fa-users"></i> User Management</a></li>
            <li><a href="?action=articles" class="<?= $action == 'articles' ? 'active' : '' ?>"><i class="fas fa-newspaper"></i> Check Articles</a></li>
            <li><a href="?action=workflow" class="<?= $action == 'workflow' ? 'active' : '' ?>"><i class="fas fa-sitemap"></i> Workflow Guide</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content-area">
            <?php if ($message): ?>
            <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= $message ?>
            </div>
            <?php endif; ?>

            <?php if ($action == 'dashboard'): ?>
            <h2 class="page-title">Status Sistem</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= $status['users'] ?? 0 ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <h3><?= $status['articles'] ?? 0 ?></h3>
                    <p>Total Artikel</p>
                </div>
                <div class="stat-card">
                    <h3><?= $status['pending'] ?? 0 ?></h3>
                    <p>Pending Review</p>
                </div>
                <div class="stat-card">
                    <h3><?= $status['published'] ?? 0 ?></h3>
                    <p>Published</p>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-tools"></i> Quick Actions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_test_article">
                        <button type="submit" class="btn" style="width: 100%;">
                            <i class="fas fa-plus"></i> Buat Test Artikel
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="clean_test_data">
                        <button type="submit" class="btn" style="width: 100%; background: #dc3545;">
                            <i class="fas fa-trash"></i> Bersihkan Test Data
                        </button>
                    </form>
                    <a href="update_database.php" class="btn" style="width: 100%; display: flex; align-items: center; justify-content: center; text-decoration: none; background: #28a745;">
                        <i class="fas fa-sync-alt"></i> Update Database
                    </a>
                </div>
            </div>

            <?php elseif ($action == 'database'): ?>
            <h2 class="page-title">Database Tools</h2>
            
            <div class="card">
                <h3><i class="fas fa-wrench"></i> Database Fixes</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <form method="POST">
                        <input type="hidden" name="action" value="fix_status_enum">
                        <button type="submit" class="btn" style="width: 100%;">
                            <i class="fas fa-cog"></i> Fix Status Enum
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="fix_role_column">
                        <button type="submit" class="btn" style="width: 100%;">
                            <i class="fas fa-user-cog"></i> Fix Role Column
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="fix_database">
                        <button type="submit" class="btn" style="width: 100%;">
                            <i class="fas fa-database"></i> Fix Database
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="setup_multi_category">
                        <button type="submit" class="btn" style="width: 100%;">
                            <i class="fas fa-tags"></i> Multi Category
                        </button>
                    </form>
                    <a href="update_database.php" class="btn" style="width: 100%; display: flex; align-items: center; justify-content: center; text-decoration: none; background: #28a745;">
                        <i class="fas fa-sync-alt"></i> Update Database Schema
                    </a>
                </div>
            </div>
            
            <div class="card">
                <h3><i class="fas fa-info-circle"></i> Informasi Database Update</h3>
                <p>Gunakan "Update Database Schema" untuk:</p>
                <ul>
                    <li>Menambah kolom email di tabel users</li>
                    <li>Menambah kolom status di tabel users</li>
                    <li>Update enum role (admin, guru, siswa, anggota, pending)</li>
                    <li>Membuat tabel likes dan komentar</li>
                </ul>
            </div>

            <?php elseif ($action == 'users'): ?>
            <h2 class="page-title">User Management</h2>
            
            <div class="card">
                <h3><i class="fas fa-users"></i> Daftar Users</h3>
                <?php
                $stmt = $pdo->query("SELECT id, username, nama, role, status, created_at FROM users ORDER BY created_at DESC");
                $users = $stmt->fetchAll();
                ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['nama']) ?></td>
                            <td>
                                <span class="badge <?= $user['role'] == 'admin' ? 'badge-success' : ($user['role'] == 'guru' ? 'badge-info' : 'badge-warning') ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td><?= ucfirst($user['status']) ?></td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php elseif ($action == 'articles'): ?>
            <h2 class="page-title">Check Articles</h2>
            
            <div class="card">
                <h3><i class="fas fa-newspaper"></i> Status Artikel</h3>
                <?php
                $stmt = $pdo->query("SELECT a.id, a.judul, a.status, u.nama as penulis, u.role 
                                     FROM artikel a 
                                     LEFT JOIN users u ON a.user_id = u.id 
                                     ORDER BY a.created_at DESC");
                $articles = $stmt->fetchAll();
                ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Judul</th>
                            <th>Penulis</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                        <tr>
                            <td><?= $article['id'] ?></td>
                            <td><?= htmlspecialchars($article['judul']) ?></td>
                            <td><?= htmlspecialchars($article['penulis']) ?></td>
                            <td><?= ucfirst($article['role']) ?></td>
                            <td>
                                <span class="badge <?= $article['status'] == 'published' ? 'badge-success' : ($article['status'] == 'pending' ? 'badge-warning' : 'badge-info') ?>">
                                    <?= ucfirst($article['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php elseif ($action == 'workflow'): ?>
            <h2 class="page-title">Workflow Guide</h2>
            
            <div class="card">
                <h3><i class="fas fa-users"></i> Role & Permissions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <h4>📝 Anggota</h4>
                        <ul>
                            <li>Menulis artikel baru</li>
                            <li>Edit artikel sendiri</li>
                            <li>Submit artikel untuk review</li>
                        </ul>
                        <strong>Login:</strong> anggota1 / password
                    </div>
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8;">
                        <h4>👨🏫 Guru</h4>
                        <ul>
                            <li>Review artikel pending</li>
                            <li>Setujui atau tolak artikel</li>
                            <li>Tidak bisa publish</li>
                        </ul>
                        <strong>Login:</strong> guru1 / password
                    </div>
                    <div style="background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
                        <h4>👑 Admin</h4>
                        <ul>
                            <li>Akses penuh sistem</li>
                            <li>Publikasikan artikel</li>
                            <li>Kelola users & data</li>
                        </ul>
                        <strong>Login:</strong> admin / password
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3><i class="fas fa-sitemap"></i> Alur Kerja Artikel</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #6c757d;">
                        <div style="background: #6c757d; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">1</div>
                        <div><strong>DRAFT</strong> - Anggota menulis artikel</div>
                    </div>
                    <div style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <div style="background: #ffc107; color: black; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">2</div>
                        <div><strong>PENDING</strong> - Submit ke guru untuk review</div>
                    </div>
                    <div style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #17a2b8;">
                        <div style="background: #17a2b8; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">3</div>
                        <div><strong>APPROVED</strong> - Guru setujui artikel</div>
                    </div>http://localhost/E-Magazine/laporan.php
                    <div style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #28a745;">
                        <div style="background: #28a745; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">4</div>
                        <div><strong>PUBLISHED</strong> - Admin publikasikan ke public</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>