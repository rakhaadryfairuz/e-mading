# Perubahan Sistem Pendaftaran E-Magazine

## Ringkasan Perubahan

Sistem pendaftaran telah diubah sesuai permintaan untuk menyederhanakan proses registrasi dan memberikan kontrol penuh kepada admin dalam menentukan role user.

## Perubahan Utama

### 1. Form Pendaftaran (register.php)
- **DIHAPUS**: Field "Nama Lengkap" dan "Role"
- **DITAMBAH**: Field "Email" sebagai pengganti nama lengkap
- **TETAP**: Username dan Password
- **BARU**: Validasi email format
- **BARU**: Cek duplikasi email dan username

### 2. Sistem Role dan Status
- **Role Default**: User baru mendapat role "pending"
- **Status Default**: User baru mendapat status "inactive"
- **Admin Control**: Admin dapat mengubah role dan status melalui halaman users.php

### 3. Role yang Tersedia
- **pending**: User baru yang belum ditentukan rolenya
- **siswa**: Dapat login dan mengakses halaman public
- **guru**: Dapat moderasi artikel dan akses dashboard
- **admin**: Akses penuh sistem

### 4. Alur Login Berdasarkan Role
- **Admin**: Diarahkan ke Dashboard Admin (index.php)
- **Guru**: Diarahkan ke Dashboard Guru (dashboard_guru.php)
- **Siswa**: Diarahkan ke Dashboard Siswa (dashboard_siswa.php)
- **Pending**: Tidak dapat login, akan mendapat pesan error

### 5. Halaman Baru
- **dashboard_guru.php**: Dashboard khusus untuk guru dengan fitur moderasi
- **dashboard_siswa.php**: Dashboard khusus untuk siswa dengan fitur menulis artikel
- **public_home.php**: Halaman khusus untuk user yang belum login
- **update_database.php**: Script untuk mengupdate schema database

## Cara Menggunakan Sistem Baru

### Untuk User Baru:
1. Kunjungi halaman register.php
2. Isi email, username, dan password
3. Klik "Daftar Akun"
4. Tunggu admin mengaktifkan akun dan menentukan role

### Untuk Admin:
1. Login ke dashboard
2. Kunjungi halaman "Users" (users.php)
3. Lihat user dengan role "pending" (ditandai dengan background kuning)
4. Ubah role sesuai kebutuhan (siswa/guru/admin)
5. Ubah status menjadi "active"
6. User sekarang dapat login dengan role yang telah ditentukan

## File yang Dimodifikasi

1. **register.php** - Form pendaftaran baru
2. **login.php** - Redirect berdasarkan role ke dashboard masing-masing
3. **users.php** - Interface admin untuk mengelola user
4. **auth_check.php** - Handling role baru dan fungsi redirect
5. **index.php** - Redirect user non-admin ke dashboard mereka

## File Baru

1. **dashboard_guru.php** - Dashboard khusus untuk guru
2. **dashboard_siswa.php** - Dashboard khusus untuk siswa
3. **public_home.php** - Halaman public untuk user yang belum login
4. **update_database.php** - Script untuk update schema database
5. **test_system.php** - File untuk testing sistem
6. **PERUBAHAN_SISTEM.md** - Dokumentasi ini

## Update Database

Jalankan file `update_database.php` untuk mengupdate schema database:
- Menambah kolom `email` di tabel users
- Menambah kolom `status` di tabel users  
- Update enum `role` untuk menambah 'pending' dan 'siswa'
- Membuat tabel `likes` dan `komentar` jika belum ada

## Keamanan

- Password tetap di-hash menggunakan password_hash()
- Validasi input yang ketat
- Cek duplikasi email dan username
- Session management yang aman
- Role-based access control

## Catatan Penting

- User dengan role "pending" tidak dapat login
- Admin dapat mengubah role dan status user kapan saja
- Setiap role memiliki dashboard dan fitur yang berbeda:
  - **Admin**: Akses penuh sistem, kelola user, artikel, kategori
  - **Guru**: Moderasi artikel, review artikel siswa
  - **Siswa**: Menulis artikel, lihat artikel sendiri
- User otomatis diarahkan ke dashboard sesuai role mereka