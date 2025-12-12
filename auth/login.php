<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
if (isLoggedIn()) {
    header("Location: ../" . $_SESSION['role'] . "/index.php");
    exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = escapeString($_POST['username']);
    $password = md5($_POST['password']);
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT user_id, username, nama_lengkap, role, status FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if ($user['status'] == 'nonaktif') {
            $error = 'Akun Anda tidak aktif. Hubungi administrator.';
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            header("Location: ../" . $user['role'] . "/index.php");
            exit();
        }
    } else {
        $error = 'Username atau password salah!';
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PM Kayaba Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-white to-gray-100 min-h-screen flex items-center justify-center">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex">
            <div class="w-1/2 bg-gradient-to-br from-purple-600 to-indigo-600 text-white p-16 flex flex-col justify-center">
                <h1 class="text-4xl font-bold mb-4"><i class="fas fa-tools mr-2"></i>PM Kayaba</h1>
                <h3 class="text-2xl mb-4">Preventive Maintenance System</h3>
                <p class="mt-3 text-purple-100">Sistem manajemen preventive maintenance komputer PT Kayaba Indonesia yang terintegrasi dan efisien.</p>
                <div class="mt-8 space-y-3">
                    <p><i class="fas fa-check-circle mr-2"></i>Monitoring Real-time</p>
                    <p><i class="fas fa-check-circle mr-2"></i>Laporan Otomatis</p>
                    <p><i class="fas fa-check-circle mr-2"></i>Notifikasi Cepat</p>
                </div>
            </div>
            <div class="w-1/2 p-16">
                <h2 class="text-3xl font-bold mb-8 text-center text-gray-800">Login</h2>
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span><?php echo $error; ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2"><i class="fas fa-user mr-2"></i>Username</label>
                        <input type="text" name="username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent" required autofocus>
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2"><i class="fas fa-lock mr-2"></i>Password</label>
                        <input type="password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent" required>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-3 rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                </form>
                <div class="mt-6 text-center">
                    <img src="../assets/img/kayaba-logo.png" alt="KYB Logo" class="inline-block max-h-20">
                </div>
            </div>
        </div>
    </div>
</body>
</html>