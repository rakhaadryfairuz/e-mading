<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Sistem - E-Magazine</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 50%, #e1f5fe 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(79, 195, 247, 0.2);
            border: 1px solid rgba(79, 195, 247, 0.2);
            backdrop-filter: blur(10px);
        }
        .status-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        .status-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .status-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .status-item.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .icon {
            margin-right: 15px;
            font-size: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 50%, #03a9f4 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin: 10px 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 195, 247, 0.4);
        }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-info { background: #17a2b8; }
        h1 { color: #0277bd; text-align: center; margin-bottom: 30px; font-weight: 600; }
        h2 { color: #0277bd; border-bottom: 3px solid #4fc3f7; padding-bottom: 15px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Status Sistem E-Magazine</h1>
        
        <h2>📋 Checklist Sistem</h2>
        
        <?php
        include 'config/database.php';
        
        $checks = [];
        
        // Cek koneksi database
        try {
            $pdo->query("SELECT 1");
            $checks[] = ['status' => 'success', 'icon' => '✅', 'message' => 'Koneksi database berhasil'];
        } catch (Exception $e) {
            $checks[] = ['status' => 'error', 'icon' => '❌', 'message' => 'Koneksi database gagal: ' . $e->getMessage()];
        }
        
        // Cek tabel users
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                $checks[] = ['status' => 'success', 'icon' => '✅', 'message' => 'Tabel users tersedia'];
                
                // Cek user admin
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
                $stmt->execute();
                if ($stmt->fetch()) {
                    $checks[] = ['status' => 'success', 'icon' => '✅', 'message' => 'User admin tersedia'];
                } else {
                    $checks[] = ['status' => 'error', 'icon' => '❌', 'message' => 'User admin tidak ditemukan'];
                }
            } else {
                $checks[] = ['status' => 'error', 'icon' => '❌', 'message' => 'Tabel users tidak ditemukan'];
            }
        } catch (Exception $e) {
            $checks[] = ['status' => 'error', 'icon' => '❌', 'message' => 'Error cek tabel: ' . $e->getMessage()];
        }
        
        // Cek file penting
        $files = [
            'login.php' => 'Halaman login',
            'index.php' => 'Dashboard',
            'auth_check.php' => 'Sistem autentikasi',
            'config/database.php' => 'Konfigurasi database',
            'assets/css/colorful-theme.css' => 'File CSS'
        ];
        
        foreach ($files as $file => $desc) {
            if (file_exists($file)) {
                $checks[] = ['status' => 'success', 'icon' => '✅', 'message' => "$desc tersedia"];
            } else {
                $checks[] = ['status' => 'error', 'icon' => '❌', 'message' => "$desc tidak ditemukan"];
            }
        }
        
        // Cek session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $checks[] = ['status' => 'success', 'icon' => '✅', 'message' => 'PHP Session berfungsi'];
        
        // Cek password hashing
        if (function_exists('password_hash')) {
            $checks[] = ['status' => 'success', 'icon' => '✅', 'message' => 'Password hashing tersedia'];
        } else {
            $checks[] = ['status' => 'error', 'icon' => '❌', 'message' => 'Password hashing tidak tersedia'];
        }
        
        // Tampilkan hasil
        foreach ($checks as $check) {
            echo "<div class='status-item {$check['status']}'>";
            echo "<span class='icon'>{$check['icon']}</span>";
            echo "<span>{$check['message']}</span>";
            echo "</div>";
        }
        ?>
        
        <h2>🔧 Aksi Cepat</h2>
        <div style="text-align: center;">
            <a href="setup.php" class="btn btn-warning">🔧 Setup Database</a>
            <a href="test_login.php" class="btn btn-info">🧪 Test Login</a>
            <a href="login.php" class="btn btn-success">🔐 Login</a>
            <a href="index.php" class="btn">📊 Dashboard</a>
            <a href="public.php" class="btn btn-info" target="_blank">👁️ Lihat Public</a>
        </div>
        
        <h2>📝 Informasi Login</h2>
        <div style="background: linear-gradient(135deg, rgba(79, 195, 247, 0.1) 0%, rgba(41, 182, 246, 0.05) 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #4fc3f7;">
            <strong>Akun Demo:</strong><br>
            <strong>Admin:</strong> <code>admin</code> / <code>password</code> (Kelola semua data)<br>
            <strong>Anggota:</strong> <code>anggota1</code> / <code>password</code> (Tulis artikel)<br>
            <strong>Guru:</strong> <code>guru1</code> / <code>password</code> (Review artikel)<br>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 14px;">
            <p>E-Magazine System v1.0 | Dibuat dengan ❤️</p>
        </div>
    </div>
</body>
</html>