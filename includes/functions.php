<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function checkRole($allowed_roles = []) {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../index.php");
        exit();
    }
}

function uploadFile($file, $upload_dir = '../assets/uploads/') {
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    $file_name = time() . '_' . basename($file['name']);
    $target_file = $upload_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array($file_type, $allowed_types)) return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    if ($file['size'] > 5000000) return ['success' => false, 'message' => 'Ukuran file terlalu besar'];
    if (move_uploaded_file($file['tmp_name'], $target_file)) return ['success' => true, 'filename' => $file_name];
    return ['success' => false, 'message' => 'Gagal upload file'];
}

function createNotification($user_id, $judul, $pesan, $tipe = 'laporan_baru', $link = '') {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO notifikasi (user_id, judul, pesan, tipe, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $judul, $pesan, $tipe, $link);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

function formatTanggal($date) {
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $bulan[date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

function getStatusBadge($status) {
    $badges = [
        'baik' => '<span class="badge bg-success">Baik</span>',
        'perlu_perbaikan' => '<span class="badge bg-warning">Perlu Perbaikan</span>',
        'rusak' => '<span class="badge bg-danger">Rusak</span>',
        'menunggu' => '<span class="badge bg-secondary">Menunggu</span>',
        'ditindaklanjuti' => '<span class="badge bg-info">Ditindaklanjuti</span>',
        'selesai' => '<span class="badge bg-success">Selesai</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'terlambat' => '<span class="badge bg-danger">Terlambat</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}