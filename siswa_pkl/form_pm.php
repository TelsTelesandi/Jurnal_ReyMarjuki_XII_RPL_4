<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

checkRole(['siswa_pkl']);

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $komputer_id = intval($_POST['komputer_id']);
    $jadwal_id = !empty($_POST['jadwal_id']) ? intval($_POST['jadwal_id']) : null;
    
    // Hardware
    $cek_cpu = escapeString($_POST['cek_cpu']);
    $cek_ram = escapeString($_POST['cek_ram']);
    $cek_harddisk = escapeString($_POST['cek_harddisk']);
    $cek_motherboard = escapeString($_POST['cek_motherboard']);
    $cek_power_supply = escapeString($_POST['cek_power_supply']);
    $cek_monitor = escapeString($_POST['cek_monitor']);
    $cek_keyboard = escapeString($_POST['cek_keyboard']);
    $cek_mouse = escapeString($_POST['cek_mouse']);
    
    // Software
    $cek_os = escapeString($_POST['cek_os']);
    $cek_antivirus = escapeString($_POST['cek_antivirus']);
    $cek_software_update = escapeString($_POST['cek_software_update']);
    
    // Kebersihan
    $cek_debu = escapeString($_POST['cek_debu']);
    $cek_kabel = escapeString($_POST['cek_kabel']);
    
    // Catatan
    $catatan = escapeString($_POST['catatan']);
    
    // OTOMATIS MENENTUKAN STATUS HASIL berdasarkan pengecekan
    $status_hasil = 'baik'; // default
    $tindakan_perbaikan = '';
    
    // Cek Hardware - jika ada yang rusak
    $hardware_checks = [$cek_cpu, $cek_ram, $cek_harddisk, $cek_motherboard, $cek_power_supply, $cek_monitor, $cek_keyboard, $cek_mouse];
    if (in_array('rusak', $hardware_checks)) {
        $status_hasil = 'rusak';
        $tindakan_perbaikan = 'Ada komponen hardware yang rusak dan perlu diganti.';
    } elseif (in_array('perlu_perbaikan', $hardware_checks)) {
        $status_hasil = 'perlu_perbaikan';
        $tindakan_perbaikan = 'Ada komponen hardware yang perlu perbaikan.';
    }
    
    // Cek Software - jika ada masalah
    if ($cek_os == 'bermasalah' || $cek_antivirus == 'tidak_aktif') {
        if ($status_hasil == 'baik') {
            $status_hasil = 'perlu_perbaikan';
            $tindakan_perbaikan = 'Perlu perbaikan software (OS atau Antivirus).';
        }
    }
    
    // Cek Kebersihan - jika sangat kotor
    if ($cek_debu == 'sangat_berdebu' || $cek_kabel == 'berantakan') {
        if ($status_hasil == 'baik') {
            $status_hasil = 'perlu_perbaikan';
            $tindakan_perbaikan = 'Perlu pembersihan dan penataan kabel.';
        }
    }
    
    // Upload Foto
    $foto_kondisi = '';
    if (isset($_FILES['foto_kondisi']) && $_FILES['foto_kondisi']['error'] == 0) {
        $upload = uploadFile($_FILES['foto_kondisi']);
        if ($upload['success']) {
            $foto_kondisi = $upload['filename'];
        }
    }
    
    // Insert PM
    if ($jadwal_id) {
        // Dengan jadwal
        $stmt = $conn->prepare("
            INSERT INTO preventive_maintenance (
                jadwal_id, komputer_id, siswa_pkl_id,
                cek_cpu, cek_ram, cek_harddisk, cek_motherboard, cek_power_supply, 
                cek_monitor, cek_keyboard, cek_mouse,
                cek_os, cek_antivirus, cek_software_update,
                cek_debu, cek_kabel,
                status_hasil, tindakan_perbaikan, catatan, foto_kondisi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iiisssssssssssssssss",
            $jadwal_id, $komputer_id, $user_id,
            $cek_cpu, $cek_ram, $cek_harddisk, $cek_motherboard, $cek_power_supply,
            $cek_monitor, $cek_keyboard, $cek_mouse,
            $cek_os, $cek_antivirus, $cek_software_update,
            $cek_debu, $cek_kabel,
            $status_hasil, $tindakan_perbaikan, $catatan, $foto_kondisi
        );
    } else {
        // Tanpa jadwal (PM manual)
        $stmt = $conn->prepare("
            INSERT INTO preventive_maintenance (
                komputer_id, siswa_pkl_id,
                cek_cpu, cek_ram, cek_harddisk, cek_motherboard, cek_power_supply, 
                cek_monitor, cek_keyboard, cek_mouse,
                cek_os, cek_antivirus, cek_software_update,
                cek_debu, cek_kabel,
                status_hasil, tindakan_perbaikan, catatan, foto_kondisi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iisssssssssssssssss",
            $komputer_id, $user_id,
            $cek_cpu, $cek_ram, $cek_harddisk, $cek_motherboard, $cek_power_supply,
            $cek_monitor, $cek_keyboard, $cek_mouse,
            $cek_os, $cek_antivirus, $cek_software_update,
            $cek_debu, $cek_kabel,
            $status_hasil, $tindakan_perbaikan, $catatan, $foto_kondisi
        );
    }
    
    if ($stmt->execute()) {
        // Update jadwal jika ada
        if ($jadwal_id) {
            $conn->query("UPDATE jadwal_pm SET status_jadwal = 'selesai' WHERE jadwal_id = $jadwal_id");
        }
        
        // Update status komputer
        $conn->query("UPDATE komputer SET status_komputer = '$status_hasil' WHERE komputer_id = $komputer_id");
        
        $message = 'Data PM berhasil disimpan! Status komputer: ' . strtoupper($status_hasil);
        $message_type = 'success';
    } else {
        $message = 'Gagal menyimpan data PM!';
        $message_type = 'danger';
    }
    $stmt->close();
}

// Get Komputer List
$komputer_list = $conn->query("SELECT * FROM komputer ORDER BY kode_komputer");

// Get Jadwal PM Pending
$jadwal_list = $conn->query("
    SELECT jp.*, k.kode_komputer, k.nama_komputer 
    FROM jadwal_pm jp
    JOIN komputer k ON jp.komputer_id = k.komputer_id
    WHERE jp.siswa_pkl_id = $user_id AND jp.status_jadwal = 'pending'
    ORDER BY jp.tanggal_jadwal ASC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form PM - PM Kayaba</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            position: fixed;
            width: 250px;
            padding: 0;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .user-info {
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        .section-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .check-group {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-tools me-2"></i>PM Kayaba</h4>
            <small>Siswa PKL Dashboard</small>
        </div>    
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="index.php">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a class="nav-link active" href="form_pm.php">
                <i class="fas fa-clipboard-check me-2"></i>Input PM
            </a>
            <a class="nav-link" href="riwayat_pm.php">
                <i class="fas fa-history me-2"></i>Riwayat PM
            </a>
            <a class="nav-link" href="tindak_lanjut.php">
                <i class="fas fa-tasks me-2"></i>Tindak Lanjut Laporan
            </a>
        </nav>  
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></div>
                    <small>Siswa PKL / Teknisi</small>
                </div>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="mb-4">
            <h2><i class="fas fa-clipboard-check me-2"></i>Form Preventive Maintenance</h2>
            <p class="text-muted">Input hasil pengecekan dan pemeliharaan komputer</p>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-desktop me-2"></i>Pilih Komputer</h5>
                    </div>               
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Jadwal PM (Opsional)</label>
                            <select name="jadwal_id" id="jadwal_id" class="form-select" onchange="loadKomputerFromJadwal(this)">
                                <option value="">Pilih Jadwal PM (jika ada)</option>
                                <?php while($jadwal = $jadwal_list->fetch_assoc()): ?>
                                    <option value="<?php echo $jadwal['jadwal_id']; ?>" data-komputer="<?php echo $jadwal['komputer_id']; ?>">
                                        <?php echo $jadwal['kode_komputer']; ?> - <?php echo $jadwal['nama_komputer']; ?>
                                        (<?php echo date('d/m/Y', strtotime($jadwal['tanggal_jadwal'])); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Pilih jika PM ini dari jadwal yang sudah ditentukan</small>
                        </div>                      
                        <div class="col-md-6">
                            <label class="form-label">Atau Pilih Komputer Manual <span class="text-danger">*</span></label>
                            <select name="komputer_id" id="komputer_id" class="form-select" required>
                                <option value="">Pilih Komputer</option>
                                <?php
                                $komputer_list->data_seek(0);
                                while($pc = $komputer_list->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $pc['komputer_id']; ?>">
                                        <?php echo $pc['kode_komputer']; ?> - <?php echo $pc['nama_komputer']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-microchip me-2"></i>Pengecekan Hardware</h5>
                    </div>                   
                    <div class="row">
                        <div class="col-md-6">
                            <div class="check-group">
                                <div class="mb-3">
                                    <label class="form-label"><strong>CPU / Processor</strong></label>
                                    <select name="cek_cpu" class="form-select" required>
                                        <option value="baik">Baik</option>
                                        <option value="perlu_perbaikan">Perlu Perbaikan</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>                               
                                <div class="mb-3">
                                    <label class="form-label"><strong>RAM</strong></label>
                                    <select name="cek_ram" class="form-select" required>
                                        <option value="baik">Baik</option>
                                        <option value="perlu_perbaikan">Perlu Perbaikan</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Harddisk / SSD</strong></label>
                                    <select name="cek_harddisk" class="form-select" required>
                                        <option value="baik">Baik</option>
                                        <option value="perlu_perbaikan">Perlu Perbaikan</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>                               
                                <div class="mb-0">
                                    <label class="form-label"><strong>Motherboard</strong></label>
                                    <select name="cek_motherboard" class="form-select" required>
                                        <option value="baik">Baik</option>
                                        <option value="perlu_perbaikan">Perlu Perbaikan</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>
                            </div>
                        </div>                       
                        <div class="col-md-6">
                            <div class="check-group">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Power Supply</strong></label>
                                    <select name="cek_power_supply" class="form-select" required>
                                        <option value="baik">Baik</option>
                                        <option value="perlu_perbaikan">Perlu Perbaikan</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>                               
                                <div class="mb-3">
                                    <label class="form-label"><strong>Monitor</strong></label>
                                    <select name="cek_monitor" class="form-select" required>
                                        <option value="baik">Baik</option>
                                        <option value="perlu_perbaikan">Perlu Perbaikan</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>                               
                                <div class="mb-3">
                                    <label class="form-label"><strong>Keyboard</strong></label>
                                    <select name="cek_keyboard" class="form-select" required>
                                        <option value="baik">Baik</option>
                                        <option value="perlu_perbaikan">Perlu Perbaikan</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>                               
                                <div class="mb-0">
                                    <label class="form-label"><strong>Mouse</strong></label>
                                    <select name="cek_mouse" class="form-select" required>
                                        <option value="baik">Baik</option>
                                        <option value="perlu_perbaikan">Perlu Perbaikan</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="section-header mt-4">
                        <h5 class="mb-0"><i class="fas fa-laptop-code me-2"></i>Pengecekan Software</h5>
                    </div>                 
                    <div class="check-group">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Operating System</strong></label>
                                    <select name="cek_os" class="form-select" required>
                                        <option value="baik">Baik</option>
                                        <option value="perlu_update">Perlu Update</option>
                                        <option value="bermasalah">Bermasalah</option>
                                    </select>
                                </div>
                            </div>                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Antivirus</strong></label>
                                    <select name="cek_antivirus" class="form-select" required>
                                        <option value="aktif">Aktif</option>
                                        <option value="tidak_aktif">Tidak Aktif</option>
                                        <option value="perlu_update">Perlu Update</option>
                                    </select>
                                </div>
                            </div>                           
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Software Update</strong></label>
                                    <select name="cek_software_update" class="form-select" required>
                                        <option value="terupdate">Terupdate</option>
                                        <option value="perlu_update">Perlu Update</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="section-header mt-4">
                        <h5 class="mb-0"><i class="fas fa-broom me-2"></i>Pengecekan Kebersihan</h5>
                    </div>                   
                    <div class="check-group">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Kondisi Debu</strong></label>
                                    <select name="cek_debu" class="form-select" required>
                                        <option value="bersih">Bersih</option>
                                        <option value="sedikit_berdebu">Sedikit Berdebu</option>
                                        <option value="sangat_berdebu">Sangat Berdebu</option>
                                    </select>
                                </div>
                            </div>                          
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Kondisi Kabel</strong></label>
                                    <select name="cek_kabel" class="form-select" required>
                                        <option value="rapi">Rapi</option>
                                        <option value="perlu_rapikan">Perlu Rapikan</option>
                                        <option value="berantakan">Berantakan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="section-header mt-4">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Catatan & Dokumentasi</h5>
                    </div>                    
                    <div class="check-group">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Status komputer akan ditentukan otomatis</strong> berdasarkan hasil pengecekan Anda.
                        </div>                      
                        <div class="mb-3">
                            <label class="form-label"><strong>Catatan Tambahan</strong></label>
                            <textarea name="catatan" class="form-control" rows="3" placeholder="Catatan tambahan mengenai kondisi komputer (opsional)"></textarea>
                        </div>                       
                        <div class="mb-3">
                            <label class="form-label"><strong>Upload Foto Kondisi</strong></label>
                            <input type="file" name="foto_kondisi" class="form-control" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF (Max 5MB)</small>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Simpan PM
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
    <script>
        function loadKomputerFromJadwal(select) {
            const option = select.options[select.selectedIndex];
            const komputerId = option.getAttribute('data-komputer');
            
            if (komputerId) {
                document.getElementById('komputer_id').value = komputerId;
            }
        }
    </script>
</body>
</html>