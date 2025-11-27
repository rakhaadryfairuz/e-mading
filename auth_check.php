<?php
// File untuk memeriksa autentikasi user
session_start();

function checkAuth($required_roles = null) {
    // Cek apakah user sudah login
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // Cek apakah role masih pending
    if ($_SESSION['role'] === 'pending') {
        session_destroy();
        header('Location: login.php?error=pending_role');
        exit;
    }
    
    // Cek role jika diperlukan
    if ($required_roles) {
        if (is_string($required_roles)) {
            $required_roles = [$required_roles];
        }
        
        if (!in_array($_SESSION['role'], $required_roles) && $_SESSION['role'] !== 'admin') {
            header('Location: admin_dashboard.php?error=access_denied');
            exit;
        }
    }
    
    return true;
}

function canManageUsers() {
    return $_SESSION['role'] === 'admin';
}

function canCreateArticle() {
    return in_array($_SESSION['role'], ['admin', 'siswa', 'anggota']);
}

function canApproveArticle() {
    return in_array($_SESSION['role'], ['admin', 'guru']);
}

function canPublishArticle() {
    return in_array($_SESSION['role'], ['admin', 'guru']);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getUserName() {
    return $_SESSION['nama'] ?? 'Guest';
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function redirectToDashboard() {
    $role = getUserRole();
    switch ($role) {
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
?>