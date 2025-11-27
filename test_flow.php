<?php
echo "<h2>Test Flow Website E-Magazine</h2>";
echo "<h3>Struktur Baru:</h3>";
echo "<ol>";
echo "<li><strong>Landing Page:</strong> <a href='index.php' target='_blank'>index.php</a> → Redirect ke public.php</li>";
echo "<li><strong>Halaman Public:</strong> <a href='public.php' target='_blank'>public.php</a> → Landing page utama dengan navbar login</li>";
echo "<li><strong>Login:</strong> <a href='login.php' target='_blank'>login.php</a> → Form login dengan link kembali ke beranda</li>";
echo "<li><strong>Register:</strong> <a href='register.php' target='_blank'>register.php</a> → Form registrasi dengan link kembali ke beranda</li>";
echo "<li><strong>Dashboard Admin:</strong> <a href='admin_dashboard.php' target='_blank'>admin_dashboard.php</a> → Dashboard khusus admin</li>";
echo "<li><strong>Dashboard Guru:</strong> <a href='dashboard_guru.php' target='_blank'>dashboard_guru.php</a> → Dashboard khusus guru</li>";
echo "<li><strong>Dashboard Siswa:</strong> <a href='dashboard_siswa.php' target='_blank'>dashboard_siswa.php</a> → Dashboard khusus siswa</li>";
echo "</ol>";

echo "<h3>Flow User:</h3>";
echo "<ol>";
echo "<li>User mengakses website → Langsung ke <strong>public.php</strong> (landing page)</li>";
echo "<li>Di navbar public.php ada tombol <strong>Login</strong> dan <strong>Daftar</strong></li>";
echo "<li>User klik Login → Masuk ke form login</li>";
echo "<li>Setelah login berhasil → Redirect ke dashboard sesuai role:</li>";
echo "<ul>";
echo "<li>Admin → admin_dashboard.php</li>";
echo "<li>Guru → dashboard_guru.php</li>";
echo "<li>Siswa → dashboard_siswa.php</li>";
echo "</ul>";
echo "</ol>";

echo "<h3>Fitur Navbar Public:</h3>";
echo "<ul>";
echo "<li>✅ Tombol Login yang mengarah ke login.php</li>";
echo "<li>✅ Tombol Daftar yang mengarah ke register.php</li>";
echo "<li>✅ Link kembali ke beranda di halaman login & register</li>";
echo "</ul>";

echo "<p><a href='public.php' style='background: #4fc3f7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Test Landing Page</a></p>";
?>