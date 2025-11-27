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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validasi input
    if (empty($email)) {
        $error = "Email harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (empty($username)) {
        $error = "Username harus diisi!";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username hanya boleh huruf, angka, dan underscore!";
    } elseif (empty($password)) {
        $error = "Password harus diisi!";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter!";    
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $error = "Password harus mengandung huruf besar, huruf kecil, dan angka!";
    } else {
        try {
            // Cek apakah kolom email ada
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
            $emailColumnExists = $stmt->rowCount() > 0;
            
            if ($emailColumnExists) {
                // Cek username dan email sudah ada atau belum
                $stmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    if ($existing['username'] === $username) {
                        $error = "Username '$username' sudah digunakan! Silakan pilih username lain.";
                    } else {
                        $error = "Email '$email' sudah terdaftar! Silakan gunakan email lain.";
                    }
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user baru dengan email
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, nama, role, status) VALUES (?, ?, ?, ?, 'pending', 'inactive')");
                    $stmt->execute([$username, $email, $hashed_password, $username]);
                    
                    $success = "Pendaftaran berhasil! Akun Anda sedang menunggu persetujuan admin. Admin akan menentukan role Anda (Siswa/Guru) dan mengaktifkan akun. Setelah itu Anda dapat login ke dashboard sesuai role yang diberikan.";
                }
            } else {
                // Kolom email belum ada, cek username saja
                $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
                $stmt->execute([$username]);
                
                if ($stmt->fetch()) {
                    $error = "Username '$username' sudah digunakan! Silakan pilih username lain.";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user baru tanpa email (akan diupdate nanti)
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, 'pending')");
                    $stmt->execute([$username, $hashed_password, $username]);
                    
                    $success = "Pendaftaran berhasil! Silakan jalankan update_database.php untuk melengkapi sistem. Akun Anda sedang menunggu persetujuan admin.";
                }
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username atau email sudah digunakan!";
            } else {
                $error = "Gagal membuat akun. Error: " . $e->getMessage();
            }
        } catch(Exception $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - E-Magazine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/colorful-theme.css" rel="stylesheet">
    <style>
        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 50%, #e1f5fe 100%);
            padding: 20px;
        }
        
        .register-box {
            background: rgba(255,255,255,0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(79, 195, 247, 0.2);
            border: 1px solid rgba(79, 195, 247, 0.2);
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px);
        }
        
        .register-title {
            text-align: center;
            color: #0277bd;
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .toggle-form {
            text-align: center;
            margin-top: 20px;
            color: #546e7a;
        }
        
        .toggle-form a {
            color: #0277bd;
            text-decoration: none;
            font-weight: 500;
        }
        
        .toggle-form a:hover {
            color: #4fc3f7;
            text-decoration: underline;
        }
        
        .role-info {
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.1) 0%, rgba(41, 182, 246, 0.05) 100%);
            border-radius: 12px;
            border: 1px solid rgba(79, 195, 247, 0.2);
        }
        
        .role-info small {
            color: #546e7a;
            line-height: 1.6;
        }
        
        .role-info strong {
            color: #0277bd;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <h2 class="register-title">
                <i class="fas fa-user-plus"></i> Daftar Akun
            </h2>
            
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="Masukkan alamat email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" 
                           name="username" 
                           class="form-control" 
                           placeholder="Masukkan username (huruf, angka, _)" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           pattern="[a-zA-Z0-9_]+"
                           required 
                           minlength="3">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" 
                           id="password"
                           name="password" 
                           class="form-control" 
                           placeholder="Password: min 8 karakter, huruf besar, kecil, angka" 
                           required 
                           minlength="8">
                    <div id="password-strength" style="margin-top: 5px; font-size: 12px;"></div>
                    <div style="font-size: 11px; color: #999; margin-top: 3px;">
                        Contoh password kuat: <code>MyPass123</code>, <code>Secure2024</code>
                    </div>
                </div>
                
                <button type="submit" class="btn" style="width: 100%; margin-top: 20px;" id="registerBtn">
                    <span id="registerText"><i class="fas fa-user-plus"></i> Daftar Akun</span>
                    <span id="registerSpinner" style="display: none;">Memproses...</span>
                </button>
            </form>
            
            <div class="toggle-form">
                Sudah punya akun? <a href="login.php">Login di sini</a><br>
                <a href="public.php" style="color: #666; font-size: 13px; margin-top: 10px; display: inline-block;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>
            
            <div class="role-info">
                <small>
                    <strong>Informasi Pendaftaran:</strong><br>
                    <i class="fas fa-info-circle" style="color: #4fc3f7;"></i> Setelah mendaftar, akun Anda akan menunggu persetujuan admin<br>
                    <i class="fas fa-user-cog" style="color: #ff9800;"></i> Admin akan menentukan role Anda (Siswa/Guru)<br>
                    <i class="fas fa-check-circle" style="color: #4caf50;"></i> Anda dapat login setelah akun diaktifkan
                </small>
            </div>
            
            <?php
            // Cek apakah database perlu diupdate
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
                if ($stmt->rowCount() == 0) {
                    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center;">';
                    echo '<p style="color: #856404; margin: 0 0 10px 0;"><i class="fas fa-exclamation-triangle"></i> <strong>Database perlu diupdate!</strong></p>';
                    echo '<a href="update_database.php" style="background: #28a745; color: white; padding: 8px 16px; border-radius: 5px; text-decoration: none; font-size: 14px;">';
                    echo '<i class="fas fa-sync-alt"></i> Update Database Sekarang</a>';
                    echo '</div>';
                }
            } catch(Exception $e) {}
            ?>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('registerBtn');
            const text = document.getElementById('registerText');
            const spinner = document.getElementById('registerSpinner');
            
            // Validasi form
            const email = document.querySelector('input[name="email"]').value.trim();
            const username = document.querySelector('input[name="username"]').value.trim();
            const password = document.querySelector('input[name="password"]').value;
            
            if (!email || !email.includes('@')) {
                e.preventDefault();
                alert('Email harus diisi dengan format yang benar!');
                return;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username minimal 3 karakter!');
                return;
            }
            
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                e.preventDefault();
                alert('Username hanya boleh huruf, angka, dan underscore!');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password minimal 8 karakter!');
                return;
            }
            
            if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
                e.preventDefault();
                alert('Password harus mengandung huruf besar, huruf kecil, dan angka!');
                return;
            }
            
            // Show loading
            btn.disabled = true;
            text.style.display = 'none';
            spinner.style.display = 'inline';
        });
        
        // Auto redirect setelah sukses
        <?php if(!empty($success) && strpos($success, 'update_database') === false): ?>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
        <?php endif; ?>
        
        // Focus email field
        document.querySelector('input[name="email"]').focus();
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let messages = [];
            
            if (password.length >= 8) {
                strength++;
                messages.push('✅ Minimal 8 karakter');
            } else {
                messages.push('❌ Minimal 8 karakter');
            }
            
            if (/[a-z]/.test(password)) {
                strength++;
                messages.push('✅ Huruf kecil');
            } else {
                messages.push('❌ Huruf kecil');
            }
            
            if (/[A-Z]/.test(password)) {
                strength++;
                messages.push('✅ Huruf besar');
            } else {
                messages.push('❌ Huruf besar');
            }
            
            if (/\d/.test(password)) {
                strength++;
                messages.push('✅ Angka');
            } else {
                messages.push('❌ Angka');
            }
            
            let color = '#dc3545';
            let strengthText = 'Lemah';
            
            if (strength === 4) {
                color = '#28a745';
                strengthText = 'Kuat';
            } else if (strength >= 2) {
                color = '#ffc107';
                strengthText = 'Sedang';
            }
            
            strengthDiv.innerHTML = `<span style="color: ${color}; font-weight: bold;">${strengthText}</span> - ${messages.join(' | ')}`;
        });
    </script>
</body>
</html>