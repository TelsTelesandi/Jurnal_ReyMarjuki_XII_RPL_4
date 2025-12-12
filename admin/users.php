<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['admin']);

$conn = connectDB();
$message = '';
$message_type = '';

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    if ($user_id == $_SESSION['user_id']) {
        $message = 'Anda tidak dapat menghapus akun Anda sendiri!';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = 'User berhasil dihapus!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus user!';
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $username = escapeString($_POST['username']);
    $nama_lengkap = escapeString($_POST['nama_lengkap']);
    $email = escapeString($_POST['email']);
    $role = escapeString($_POST['role']);
    $divisi = escapeString($_POST['divisi']);
    $no_hp = escapeString($_POST['no_hp']);
    $status = escapeString($_POST['status']);
    $password = $_POST['password'];

    if ($user_id) {
        if (!empty($password)) {
            $password_md5 = md5($password);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, nama_lengkap=?, email=?, role=?, divisi=?, no_hp=?, status=? WHERE user_id=?");
            $stmt->bind_param("ssssssssi", $username, $password_md5, $nama_lengkap, $email, $role, $divisi, $no_hp, $status, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, nama_lengkap=?, email=?, role=?, divisi=?, no_hp=?, status=? WHERE user_id=?");
            $stmt->bind_param("sssssssi", $username, $nama_lengkap, $email, $role, $divisi, $no_hp, $status, $user_id);
        }
        if ($stmt->execute()) {
            $message = 'User berhasil diupdate!';
            $message_type = 'success';
        } else {
            $message = 'Gagal update user!';
            $message_type = 'danger';
        }
    } else {
        $password_md5 = md5($password);
        $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, email, role, divisi, no_hp, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $username, $password_md5, $nama_lengkap, $email, $role, $divisi, $no_hp, $status);
        if ($stmt->execute()) {
            $message = 'User berhasil ditambahkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambah user! Username mungkin sudah digunakan.';
            $message_type = 'danger';
        }
    }
    $stmt->close();
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM users WHERE user_id = $edit_id");
    $edit_user = $result->fetch_assoc();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - PM Kayaba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }

        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            position: fixed;
            width: 250px;
            padding: 0;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-info {
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .role-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-tools me-2"></i>PM Kayaba</h4>
            <small>Admin Dashboard</small>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link active" href="index.php">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="users.php">
                <i class="fas fa-users me-2"></i>Manajemen User
            </a>
            <a class="nav-link" href="komputer.php">
                <i class="fas fa-desktop me-2"></i>Data Komputer
            </a>
            <a class="nav-link" href="jadwal_pm.php">
                <i class="fas fa-calendar-alt me-2"></i>Jadwal PM
            </a>
            <a class="nav-link" href="monitoring_pm.php">
                <i class="fas fa-clipboard-check me-2"></i>Monitoring PM
            </a>
            <a class="nav-link" href="monitoring_ticket.php">
                <i class="fas fa-ticket-alt me-2"></i>Monitoring Ticket
            </a>
            <a class="nav-link" href="laporan.php">
                <i class="fas fa-chart-bar me-2"></i>Laporan
            </a>
            <a class="nav-link" href="master_data.php">
                <i class="fas fa-database me-2"></i>Master Data
            </a>
        </nav>
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></div>
                    <small>Administrator</small>
                </div>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-users me-2"></i>Manajemen User</h2>
                <p class="text-muted">Kelola akun Admin, Siswa PKL, dan Karyawan</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Tambah User
            </button>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i
                    class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Divisi</th>
                                <th>No HP</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $user['username']; ?></strong></td>
                                        <td><?php echo $user['nama_lengkap']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td>
                                            <?php
                                            $role_badges = [
                                                'admin' => '<span class="role-badge bg-danger text-white">Admin</span>',
                                                'siswa_pkl' => '<span class="role-badge bg-success text-white">Siswa PKL</span>',
                                                'karyawan' => '<span class="role-badge bg-info text-white">Karyawan</span>'
                                            ];
                                            echo $role_badges[$user['role']];
                                            ?>
                                        </td>
                                        <td><?php echo $user['divisi'] ?: '-'; ?></td>
                                        <td><?php echo $user['no_hp'] ?: '-'; ?></td>
                                        <td>
                                            <?php if ($user['status'] == 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning"
                                                onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=1&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Yakin ingin menghapus user ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada data user</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-user-plus me-2"></i>Tambah User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="user_id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <span class="text-danger"
                                        id="passwordRequired">*</span></label>
                                <input type="password" name="password" id="password" class="form-control">
                                <small class="text-muted" id="passwordHint" style="display:none;">Kosongkan jika tidak
                                    ingin mengubah password</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No HP</label>
                                <input type="text" name="no_hp" id="no_hp" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" id="role" class="form-select" required>
                                    <option value="">Pilih Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="siswa_pkl">Siswa PKL</option>
                                    <option value="karyawan">Karyawan</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Divisi</label>
                                <input type="text" name="divisi" id="divisi" class="form-control"
                                    placeholder="IT, HRD, Production, dll">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('user_id').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('nama_lengkap').value = '';
            document.getElementById('email').value = '';
            document.getElementById('no_hp').value = '';
            document.getElementById('role').value = '';
            document.getElementById('divisi').value = '';
            document.getElementById('status').value = 'aktif';

            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Tambah User';
            document.getElementById('password').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            document.getElementById('passwordHint').style.display = 'none';
        }

        function editUser(user) {
            document.getElementById('user_id').value = user.user_id;
            document.getElementById('username').value = user.username;
            document.getElementById('nama_lengkap').value = user.nama_lengkap;
            document.getElementById('email').value = user.email || '';
            document.getElementById('no_hp').value = user.no_hp || '';
            document.getElementById('role').value = user.role;
            document.getElementById('divisi').value = user.divisi || '';
            document.getElementById('status').value = user.status;

            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Edit User';
            document.getElementById('password').required = false;
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('passwordHint').style.display = 'block';

            new bootstrap.Modal(document.getElementById('userModal')).show();
        }
    </script>
</body>

</html>