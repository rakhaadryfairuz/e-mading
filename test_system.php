<?php
include 'config/database.php';

echo "<h2>Test Sistem E-Magazine</h2>";

try {
    // Test koneksi database
    echo "<h3>✅ Database Connection: OK</h3>";
    
    // Test struktur tabel users
    echo "<h3>📋 Struktur Tabel Users:</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test data users
    echo "<h3>👥 Data Users:</h3>";
    $stmt = $pdo->query("SELECT id, username, email, role, status FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        foreach($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email'] ?? '-') . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . ($user['status'] ?? 'active') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Belum ada user. <a href='register.php'>Daftar user baru</a></p>";
    }
    
    // Test tabel artikel
    echo "<h3>📰 Data Artikel:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) FROM artikel");
    $total_artikel = $stmt->fetchColumn();
    echo "<p>Total artikel: <strong>$total_artikel</strong></p>";
    
    // Test tabel likes (jika ada)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM likes");
        $total_likes = $stmt->fetchColumn();
        echo "<p>Total likes: <strong>$total_likes</strong></p>";
    } catch(Exception $e) {
        echo "<p>⚠️ Tabel likes belum ada. Jalankan update_database.php</p>";
    }
    
    // Test tabel komentar (jika ada)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM komentar");
        $total_komentar = $stmt->fetchColumn();
        echo "<p>Total komentar: <strong>$total_komentar</strong></p>";
    } catch(Exception $e) {
        echo "<p>⚠️ Tabel komentar belum ada. Jalankan update_database.php</p>";
    }
    
    echo "<h3>🔗 Link Testing:</h3>";
    echo "<ul>";
    echo "<li><a href='register.php'>Test Pendaftaran Baru</a></li>";
    echo "<li><a href='login.php'>Test Login</a></li>";
    echo "<li><a href='public.php'>Test Halaman Public</a></li>";
    echo "<li><a href='index.php'>Test Dashboard Admin</a></li>";
    echo "<li><a href='dashboard_guru.php'>Test Dashboard Guru</a></li>";
    echo "<li><a href='dashboard_siswa.php'>Test Dashboard Siswa</a></li>";
    echo "<li><a href='users.php'>Test Manajemen User (admin)</a></li>";
    echo "<li><a href='update_database.php'>Update Database Schema</a></li>";
    echo "</ul>";
    
    echo "<h3>📝 Cara Test Sistem:</h3>";
    echo "<ol>";
    echo "<li>Jalankan <a href='update_database.php'>update_database.php</a> untuk memastikan database up-to-date</li>";
    echo "<li>Daftar user baru di <a href='register.php'>register.php</a> (hanya isi email, username, password)</li>";
    echo "<li>Login sebagai admin dan buka <a href='users.php'>users.php</a></li>";
    echo "<li>Ubah role user baru dari 'pending' ke 'siswa'/'guru' dan status ke 'active'</li>";
    echo "<li>Logout dan login sebagai user baru</li>";
    echo "<li>User akan diarahkan ke dashboard sesuai role:</li>";
    echo "<ul>";
    echo "<li><strong>Admin</strong> → Dashboard Admin (index.php)</li>";
    echo "<li><strong>Guru</strong> → Dashboard Guru (dashboard_guru.php)</li>";
    echo "<li><strong>Siswa</strong> → Dashboard Siswa (dashboard_siswa.php)</li>";
    echo "</ul>";
    echo "</ol>";
    
} catch(Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>