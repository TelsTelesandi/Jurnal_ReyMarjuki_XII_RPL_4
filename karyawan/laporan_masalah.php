<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['karyawan']);

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $komputer_id = intval($_POST['komputer_id']);
    $judul_masalah = escapeString($_POST['judul_masalah']);
    $deskripsi_masalah = escapeString($_POST['deskripsi_masalah']);
    $kategori = escapeString($_POST['kategori']);
    $tingkat_urgensi = escapeString($_POST['tingkat_urgensi']);
    $foto_masalah = '';
    if (isset($_FILES['foto_masalah']) && $_FILES['foto_masalah']['error'] == 0) {
        $upload = uploadFile($_FILES['foto_masalah']);
        if ($upload['success']) $foto_masalah = $upload['filename'];
    }
    $stmt = $conn->prepare("INSERT INTO laporan_masalah (komputer_id, karyawan_id, judul_masalah, deskripsi_masalah, kategori, tingkat_urgensi, foto_masalah) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $komputer_id, $user_id, $judul_masalah, $deskripsi_masalah, $kategori, $tingkat_urgensi, $foto_masalah);
    if ($stmt->execute()) {
        $message = 'Laporan masalah berhasil dikirim! Teknisi akan segera menindaklanjuti.';
        $message_type = 'success';
        $conn->query("INSERT INTO notifikasi (user_id, judul, pesan, tipe) SELECT user_id, 'Laporan Masalah Baru', 'Ada laporan masalah baru dari karyawan', 'laporan_baru' FROM users WHERE role IN ('admin', 'siswa_pkl') AND status = 'aktif'");
    } else {
        $message = 'Gagal mengirim laporan!';
        $message_type = 'danger';
    }
    $stmt->close();
}

$komputer_saya = $conn->query("SELECT * FROM komputer WHERE user_id = $user_id ORDER BY kode_komputer");
$semua_komputer = $conn->query("SELECT * FROM komputer ORDER BY kode_komputer");
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporkan Masalah - PM Kayaba</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .form-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-tools me-2"></i>PM Kayaba</h4>
            <small>Dashboard Karyawan</small>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="index.php">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a class="nav-link active" href="laporan_masalah.php">
                <i class="fas fa-exclamation-circle me-2"></i>Laporkan Masalah
            </a>
            <a class="nav-link" href="riwayat_laporan.php">
                <i class="fas fa-history me-2"></i>Riwayat Laporan
            </a>
        </nav>
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></div>
                    <small>Karyawan</small>
                </div>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="mb-4">
            <h2><i class="fas fa-exclamation-triangle me-2"></i>Laporkan Masalah Komputer</h2>
            <p class="text-muted">Laporkan masalah yang Anda alami pada komputer</p>
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
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h5 class="mb-0"><i class="fas fa-desktop me-2"></i>Pilih Komputer</h5>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><strong>Komputer Yang Bermasalah <span
                                    class="text-danger">*</span></strong></label>
                        <select name="komputer_id" class="form-select form-select-lg" required>
                            <option value="">Pilih Komputer</option>
                            <?php if ($komputer_saya->num_rows > 0): ?>
                                <optgroup label="Komputer Saya">
                                    <?php while ($pc = $komputer_saya->fetch_assoc()): ?>
                                        <option value="<?php echo $pc['komputer_id']; ?>">
                                            <?php echo $pc['kode_komputer']; ?> - <?php echo $pc['nama_komputer']; ?>
                                            (<?php echo $pc['lokasi']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </optgroup>
                            <?php endif; ?>
                            <optgroup label="Komputer Lainnya">
                                <?php
                                $semua_komputer->data_seek(0);
                                while ($pc = $semua_komputer->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $pc['komputer_id']; ?>">
                                        <?php echo $pc['kode_komputer']; ?> - <?php echo $pc['nama_komputer']; ?>
                                        (<?php echo $pc['lokasi']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-section">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Detail Masalah</h5>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label"><strong>Judul Masalah <span
                                            class="text-danger">*</span></strong></label>
                                <input type="text" name="judul_masalah" class="form-control form-control-lg"
                                    placeholder="Contoh: Komputer tidak bisa menyala" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Kategori Masalah <span
                                            class="text-danger">*</span></strong></label>
                                <select name="kategori" class="form-select" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="hardware">Hardware (Perangkat Keras)</option>
                                    <option value="software">Software (Program/Aplikasi)</option>
                                    <option value="network">Network (Jaringan/Internet)</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                                <small class="text-muted">Pilih kategori yang paling sesuai dengan masalah</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Tingkat Urgensi <span
                                            class="text-danger">*</span></strong></label>
                                <select name="tingkat_urgensi" class="form-select" required>
                                    <option value="">Pilih Urgensi</option>
                                    <option value="rendah">🟢 Rendah - Tidak mengganggu pekerjaan</option>
                                    <option value="sedang">🟡 Sedang - Sedikit mengganggu</option>
                                    <option value="tinggi">🟠 Tinggi - Sangat mengganggu</option>
                                    <option value="kritis">🔴 Kritis - Tidak bisa bekerja sama sekali</option>
                                </select>
                                <small class="text-muted">Pilih sesuai dampak terhadap pekerjaan Anda</small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label"><strong>Deskripsi Masalah <span
                                            class="text-danger">*</span></strong></label>
                                <textarea name="deskripsi_masalah" class="form-control" rows="5"
                                    placeholder="Jelaskan masalah yang Anda alami secara detail:&#10;- Kapan masalah mulai terjadi?&#10;- Apa yang terjadi?&#10;- Apakah ada pesan error?&#10;- Sudah mencoba solusi apa?"
                                    required></textarea>
                                <small class="text-muted">Semakin detail informasi, semakin cepat teknisi bisa
                                    menangani</small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label"><strong>Upload Foto/Screenshot (Opsional)</strong></label>
                                <input type="file" name="foto_masalah" class="form-control" accept="image/*">
                                <small class="text-muted">Foto akan membantu teknisi memahami masalah dengan lebih baik
                                    (Max 5MB)</small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <strong>Tips Melaporkan Masalah:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Jelaskan masalah sejelas mungkin</li>
                                    <li>Sertakan foto/screenshot jika memungkinkan</li>
                                    <li>Pilih tingkat urgensi dengan tepat</li>
                                    <li>Pastikan komputer yang dipilih sudah benar</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Kirim Laporan
                        </button>
                        <a href="index.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>