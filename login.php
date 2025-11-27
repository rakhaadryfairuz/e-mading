<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    // Redirect ke dashboard sesuai role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin_dashboard.php');
            break;
        case 'guru':
            header('Location: dashboard_guru.php');
            break;
        case 'siswa':
            header('Location: dashboard_siswa.php');
            break;
        default:
            header('Location: public.php');
            break;
    }
    exit;
}

include 'config/database.php';

$error = '';
$success = '';

// Pesan sukses logout
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = 'Anda telah berhasil logout.';
}

// Pesan error role pending
if (isset($_GET['error']) && $_GET['error'] == 'pending_role') {
    $error = 'Role Anda masih pending. Silakan tunggu admin menentukan role Anda.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validasi input
    if (empty($username)) {
        $error = "Username harus diisi!";
    } elseif (empty($password)) {
        $error = "Password harus diisi!";
    } else {
        try {
            // Cek apakah username ada
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = "Username '$username' tidak ditemukan!";
            } elseif ($user['role'] === 'pending') {
                $error = "Akun Anda masih menunggu penentuan role oleh admin. Silakan hubungi administrator.";
            } elseif (isset($user['status']) && $user['status'] === 'inactive') {
                $error = "Akun Anda belum diaktifkan oleh admin. Silakan tunggu persetujuan admin.";
            } elseif (!password_verify($password, $user['password'])) {
                $error = "Password salah!";
            } else {
                // Login berhasil
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama'] = $user['nama'] ?? $user['username'];
                $_SESSION['email'] = $user['email'] ?? '';
                
                // Update last login
                try {
                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                } catch (Exception $e) {
                    // Ignore update error, login tetap berhasil
                }
                
                // Redirect berdasarkan role yang sudah ditentukan admin
                switch ($user['role']) {
                    case 'admin':
                        header('Location: admin_dashboard.php'); // Dashboard admin penuh
                        break;
                    case 'guru':
                        header('Location: dashboard_guru.php'); // Dashboard guru
                        break;
                    case 'siswa':
                        header('Location: dashboard_siswa.php'); // Dashboard siswa
                        break;
                    default:
                        header('Location: public.php'); // Fallback ke public
                        break;
                }
                exit;
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan database: " . $e->getMessage();
        } catch (Exception $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Magazine</title>
    <link href="assets/css/colorful-theme.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-box {
            background: rgba(255,255,255,0.95);
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        

        
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2rem;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
            color: #2c3e50;
        }
        
        .form-control:focus {
            transform: translateY(-2px);
            background: white;
        }
        
        .btn {
            transition: all 0.3s ease;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .toggle-form {
            text-align: center;
            margin-top: 25px;
            color: #555;
            font-size: 14px;
        }
        
        .toggle-form a {
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .toggle-form a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-box {
                padding: 30px 25px;
                margin: 10px;
            }
            
            .login-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2 class="login-title">E-Magazine Login</h2>
            
            <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Masukkan username"
                           required 
                           autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Masukkan password"
                           required 
                           autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn" style="width: 100%; margin-top: 20px;" id="loginBtn">
                    <span id="loginText">Login</span>
                    <span id="loginSpinner" style="display: none;">Memproses...</span>
                </button>
            </form>
            
            <div class="toggle-form">
                Belum punya akun? <a href="register.php">Daftar di sini</a><br>
                <a href="public.php" style="color: #666; font-size: 13px; margin-top: 10px; display: inline-block;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
    
    <script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('loginBtn');
        const text = document.getElementById('loginText');
        const spinner = document.getElementById('loginSpinner');
        
        btn.disabled = true;
        text.style.display = 'none';
        spinner.style.display = 'inline';
    });
    
    // Auto focus pada username field
    document.getElementById('username').focus();
    
    // Enter key navigation
    document.getElementById('username').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('password').focus();
        }
    });
    </script>
</body>
</html>