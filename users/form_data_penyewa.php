<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$success = '';

// Check if user has a confirmed reservation
$stmt = $conn->prepare("SELECT r.*, k.nama_kamar FROM reservasi r JOIN kamar k ON r.id_kamar = k.id_kamar WHERE r.id_pengguna = ? AND r.status_reservasi = 'Dikonfirmasi' ORDER BY r.dibuat_pada DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservasi = $stmt->get_result()->fetch_assoc();

$no_reservasi = !$reservasi;

// Check if data_penyewa already exists
if ($reservasi) {
    $cek = $conn->prepare("SELECT * FROM data_penyewa WHERE id_reservasi = ? AND id_pengguna = ?");
    $cek->bind_param("ii", $reservasi['id_reservasi'], $user_id);
    $cek->execute();
    $existing = $cek->get_result()->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $no_ktp = trim($_POST['no_ktp']);
        $alamat_asal = trim($_POST['alamat_asal']);
        $pekerjaan = trim($_POST['pekerjaan']);

        if (empty($no_ktp) || !ctype_digit($no_ktp) || strlen($no_ktp) !== 16) {
            $message = "Gagal menyimpan: NIK / Nomor KTP/KK wajib diisi dan harus tepat 16 digit angka!";
        } else {
            if ($existing) {
                // Update
                $upd = $conn->prepare("UPDATE data_penyewa SET no_ktp=?, alamat_asal=?, pekerjaan=? WHERE id_penyewa=?");
                $upd->bind_param("sssi", $no_ktp, $alamat_asal, $pekerjaan, $existing['id_penyewa']);
                if ($upd->execute())
                    $success = "Data penyewa berhasil diperbarui!";
                else
                    $message = "Gagal menyimpan: " . $conn->error;
            } else {
                // Insert
                $id_res = $reservasi['id_reservasi'];
                $id_kmr = $reservasi['id_kamar'];
                $tgl_mk = $reservasi['tanggal_masuk'];
                $tgl_kl = $reservasi['tanggal_keluar'];
                $ins = $conn->prepare("INSERT INTO data_penyewa (id_reservasi, id_pengguna, id_kamar, no_ktp, alamat_asal, pekerjaan, tanggal_masuk, tanggal_keluar, status_huni) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')");
                $ins->bind_param("iiisssss", $id_res, $user_id, $id_kmr, $no_ktp, $alamat_asal, $pekerjaan, $tgl_mk, $tgl_kl);
                if ($ins->execute())
                    $success = "Data penyewa berhasil disimpan!";
                else
                    $message = "Gagal menyimpan: " . $conn->error;
            }
        }

        // Refresh existing
        $cek->execute();
        $existing = $cek->get_result()->fetch_assoc();
    }
} else {
    $existing = null;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penyewa - Kos Berkah Malika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f4f6fb;
            min-height: 100vh;
        }



        /* Page Hero */
        .page-hero {
            padding: 3rem 0 2rem;
            text-align: center;
            color: #1e293b;
        }

        .page-hero .hero-icon {
            width: 72px;
            height: 72px;
            background: #ecfdf5;
            border: 2px solid #d1fae5;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #10b981;
            margin-bottom: 1.2rem;
            animation: float 3s ease-in-out infinite;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.1);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .page-hero h3 {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 0.4rem;
        }

        .page-hero p {
            color: #64748b;
            font-size: 0.95rem;
        }

        /* Cards */
        .white-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        /* Error State */
        .empty-state {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 3.5rem 2rem;
            text-align: center;
            max-width: 520px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        .empty-icon-wrapper {
            width: 100px;
            height: 100px;
            background: #fffbeb;
            border: 2px solid #fde68a;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            animation: pulse-glow 2.5s ease-in-out infinite;
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.2);
            }

            50% {
                box-shadow: 0 0 0 15px rgba(245, 158, 11, 0);
            }
        }

        .empty-icon-wrapper i {
            font-size: 2.5rem;
            color: #f59e0b;
        }

        .empty-state h5 {
            color: #1e293b;
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
        }

        .empty-state p {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .btn-goto {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white !important;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
            transition: all 0.3s;
        }

        .btn-goto:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(16, 185, 129, 0.35);
        }

        /* Info Reservasi */
        .info-reservasi {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.05);
        }

        .info-reservasi .label {
            font-size: 0.78rem;
            color: #16a34a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .info-reservasi .value {
            color: #1e293b;
            font-weight: 700;
            font-size: 0.95rem;
            margin-top: 0.2rem;
        }

        .badge-confirmed {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #15803d;
            border-radius: 50px;
            padding: 0.35rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Form */
        .form-label {
            color: #475569;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            background: #f8fafc !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 12px !important;
            color: #1e293b !important;
            padding: 0.75rem 1rem !important;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s !important;
        }

        .form-control::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1) !important;
            background: white !important;
        }

        textarea.form-control {
            resize: none;
        }

        /* Submit Button */
        .btn-simpan {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 0.9rem 2rem;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            letter-spacing: 0.3px;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            transition: all 0.3s;
        }

        .btn-simpan:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .btn-simpan:active {
            transform: translateY(0);
        }

        /* Alerts */
        .alert-custom-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 14px;
            color: #059669;
            padding: 1rem 1.2rem;
            font-weight: 500;
        }

        .alert-custom-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 14px;
            color: #dc2626;
            padding: 1rem 1.2rem;
            font-weight: 500;
        }

        /* Section divider */
        .section-label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            padding: 0.5rem 0;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 1.5rem;
        }

        /* Floating particles bg */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(16, 185, 129, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(79, 70, 229, 0.03) 0%, transparent 50%);
            pointer-events: none;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php $is_root = false;
    include '../navbar.php'; ?>

    <!-- Page Hero -->
    <div class="page-hero">
        <div class="hero-icon"><i class="fas fa-id-card"></i></div>
        <h3>Data Penyewa</h3>
        <p>Lengkapi data diri Anda sebagai penghuni kos</p>
    </div>

    <div class="container pb-5" style="max-width: 680px; position: relative; z-index: 10;">

        <?php if ($no_reservasi): ?>
            <!-- Empty / No Reservation State -->
            <div class="empty-state">
                <div class="empty-icon-wrapper">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h5>Belum Ada Reservasi Dikonfirmasi</h5>
                <p>Untuk mengisi data penyewa, Anda perlu memiliki reservasi yang sudah dikonfirmasi oleh admin terlebih
                    dahulu.</p>
                <a href="pesanan_saya.php" class="btn-goto">
                    <i class="fas fa-calendar-alt"></i>
                    Lihat Pesanan Saya
                </a>
            </div>

        <?php else: ?>

            <?php if ($success): ?>
                <div class="alert-custom-success mb-4">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="alert-custom-danger mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Info Reservasi -->
            <div class="info-reservasi mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="label"><i class="fas fa-bed me-1"></i>Reservasi Anda</div>
                        <div class="value"><?php echo htmlspecialchars($reservasi['nama_kamar']); ?></div>
                    </div>
                    <span class="badge-confirmed"><i class="fas fa-check-circle me-1"></i>Dikonfirmasi</span>
                </div>
                <div class="d-flex gap-5">
                    <div>
                        <div class="label"><i class="fas fa-calendar-check me-1"></i>Check-In</div>
                        <div class="value"><?php echo date('d M Y', strtotime($reservasi['tanggal_masuk'])); ?></div>
                    </div>
                    <div>
                        <div class="label"><i class="fas fa-calendar-times me-1"></i>Check-Out</div>
                        <div class="value"><?php echo date('d M Y', strtotime($reservasi['tanggal_keluar'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="white-card p-4 p-md-5">
                <div class="section-label">
                    <i class="fas fa-<?php echo $existing ? 'edit' : 'plus-circle'; ?> me-2 text-primary"></i>
                    <?php echo $existing ? 'Perbarui Data Penyewa' : 'Isi Data Penyewa'; ?>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">NIK / Nomor KTP</label>
                            <div class="position-relative">
                                <input type="text" id="no_ktp" name="no_ktp" class="form-control" minlength="16" maxlength="16" required
                                    inputmode="numeric" value="<?php echo htmlspecialchars($existing['no_ktp'] ?? ''); ?>"
                                    placeholder="16 digit NIK sesuai KTP/KK"
                                    oninvalid="this.setCustomValidity('Harap masukkan NIK/Nomor KK yang terdiri dari 16 digit angka')"
                                    oninput="this.setCustomValidity('')">
                                <small id="ktp_warning" class="text-danger d-none mt-1 position-absolute"><i
                                        class="fas fa-exclamation-triangle me-1"></i>Peringatan: NIK / Nomor KK hanya boleh
                                    berisi angka!</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat Asal</label>
                            <textarea name="alamat_asal" class="form-control" rows="3"
                                placeholder="Alamat lengkap asal daerah Anda"><?php echo htmlspecialchars($existing['alamat_asal'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pekerjaan</label>
                            <input type="text" name="pekerjaan" class="form-control"
                                value="<?php echo htmlspecialchars($existing['pekerjaan'] ?? ''); ?>"
                                placeholder="Contoh: Mahasiswa, Karyawan Swasta, dll.">
                        </div>
                    </div>
                    <div class="mt-5">
                        <button type="submit" class="btn-simpan">
                            <i class="fas fa-save me-2"></i>
                            <?php echo $existing ? 'Perbarui Data' : 'Simpan Data'; ?>
                        </button>
                    </div>
                </form>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const noKtpInput = document.getElementById('no_ktp');
            if (noKtpInput) {
                let warningTimeout;
                noKtpInput.addEventListener('input', function (e) {
                    const warning = document.getElementById('ktp_warning');
                    if (/[^0-9]/.test(this.value)) {
                        warning.classList.remove('d-none');
                        this.value = this.value.replace(/[^0-9]/g, '');

                        clearTimeout(warningTimeout);
                        warningTimeout = setTimeout(() => {
                            warning.classList.add('d-none');
                        }, 3000);
                    } else {
                        warning.classList.add('d-none');
                    }
                });
            }
        });
    </script>
</body>

</html>