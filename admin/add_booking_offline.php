<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once '../config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_kamar = (int)$_POST['id_kamar'];
    $nama_pemesan = $_POST['nama_pemesan'];
    $no_hp_pemesan = $_POST['no_hp_pemesan'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $duration_type = $_POST['duration_type'] ?? 'Monthly';
    $duration_value = (int)$_POST['duration_value'];
    $total_harga = $_POST['total_harga'];
    $metode_pembayaran = $_POST['metode_pembayaran']; // Tunai, Transfer

    // Hitung tanggal keluar
    $date = new DateTime($tanggal_masuk);
    if ($duration_type === 'Monthly') {
        $date->modify("+$duration_value month");
        $durasi_sewa = $duration_value . ' Bulan';
    } else {
        $date->modify("+$duration_value year");
        $durasi_sewa = $duration_value . ' Tahun';
    }
    $tanggal_keluar = $date->format('Y-m-d');

    // Check if room is available
    $chk = $conn->prepare("SELECT status_kamar FROM kamar WHERE id_kamar = ?");
    $chk->bind_param("i", $id_kamar);
    $chk->execute();
    $res = $chk->get_result()->fetch_assoc();

    if ($res && $res['status_kamar'] !== 'tersedia') {
        $message = "<div class='alert alert-danger shadow-sm' style='border-radius:12px;'><i class='fas fa-exclamation-circle me-2'></i>Kamar tidak tersedia.</div>";
    } else {
        // Insert into reservasi (Admin ID 1 as placeholder for offline)
        $stmt = $conn->prepare("INSERT INTO reservasi (id_kamar, id_pengguna, nama_pemesan, no_hp_pemesan, tanggal_masuk, tanggal_keluar, durasi_sewa, total_harga, status_reservasi, metode_pembayaran) VALUES (?, 1, ?, ?, ?, ?, ?, ?, 'Dikonfirmasi', ?)");
        $stmt->bind_param("isssssds", $id_kamar, $nama_pemesan, $no_hp_pemesan, $tanggal_masuk, $tanggal_keluar, $durasi_sewa, $total_harga, $metode_pembayaran);

        if ($stmt->execute()) {
            $id_reservasi = $stmt->insert_id;

            // Insert into pembayaran
            $kode_pesanan = "OFFLINE-" . $id_reservasi . "-" . time();
            $p_stmt = $conn->prepare("INSERT INTO pembayaran (id_reservasi, kode_pesanan, jumlah_bayar, jenis_pembayaran, status_transaksi, dibayar_pada) VALUES (?, ?, ?, ?, 'lunas', CURRENT_TIMESTAMP)");
            $p_stmt->bind_param("isds", $id_reservasi, $kode_pesanan, $total_harga, $metode_pembayaran);
            $p_stmt->execute();

            // Update room status
            $conn->query("UPDATE kamar SET status_kamar = 'terisi' WHERE id_kamar = $id_kamar");

            header("Location: bookings.php?msg=offline_added");
            exit();
        } else {
            $message = "<div class='alert alert-danger shadow-sm' style='border-radius:12px;'><i class='fas fa-exclamation-circle me-2'></i>Gagal menambahkan pemesanan: " . $conn->error . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pemesanan Offline - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #f4f6fb; }
        
        .sidebar { width: 250px; position: fixed; top: 0; left: 0; height: 100vh; background: linear-gradient(180deg, #1e293b, #0f172a); z-index: 100; overflow-y: auto; }
        .sidebar-brand { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link-sidebar { color: rgba(255,255,255,0.7); padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; border-radius: 0; transition: all 0.2s; text-decoration: none; font-size: 0.9rem; }
        .nav-link-sidebar:hover, .nav-link-sidebar.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-link-sidebar i { width: 20px; text-align: center; }

        .main-content { margin-left: 250px; padding: 2rem; }
        .content-header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        
        .card-form { background: white; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); overflow: hidden; }
        .card-form-header { background: #f1f5f9; padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0; }
        .card-form-body { padding: 2.5rem; }
        
        .form-label { font-weight: 600; font-size: 0.82rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 8px; }
        .form-control, .form-select { border-radius: 12px; padding: 0.75rem 1rem; border: 2px solid #f1f5f9; background-color: #f8fafc; transition: 0.3s; color: #334155; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-indigo); box-shadow: 0 0 0 4px rgba(79,70,229,0.1); background-color: white; }
        
        .segment-control { display: flex; background: #f1f5f9; padding: 6px; border-radius: 14px; margin-bottom: 0; }
        .segment-item { flex: 1; }
        .segment-input { display: none; }
        .segment-label { display: block; text-align: center; padding: 10px; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: #64748b; transition: 0.2s; }
        .segment-input:checked + .segment-label { background: white; color: var(--primary-indigo); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        .price-summary { background: #eff6ff; border-radius: 20px; padding: 1.5rem; border: 1px solid #dbeafe; }
        .price-label { font-size: 0.75rem; color: #3b82f6; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .price-value { font-size: 1.75rem; font-weight: 800; color: #1e3a8a; }

        .btn-save { background: linear-gradient(135deg, #4f46e5, #4338ca); color: white; border: 0; padding: 0.8rem 2rem; border-radius: 14px; font-weight: 600; transition: 0.3s; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25); }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(79, 70, 229, 0.35); color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="text-white fw-bold"><i class="fas fa-home me-2 text-indigo-400"></i>Berkah Malika</div>
            <small class="text-white-50 small">Panel Admin</small>
        </div>
        <nav class="mt-2">
            <a href="index.php" class="nav-link-sidebar"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="rooms.php" class="nav-link-sidebar"><i class="fas fa-bed"></i> Kelola Kamar</a>
            <a href="bookings.php" class="nav-link-sidebar active"><i class="fas fa-calendar-check"></i> Reservasi</a>
            <a href="penyewa.php" class="nav-link-sidebar"><i class="fas fa-users"></i> Data Penyewa</a>
            <a href="pembayaran.php" class="nav-link-sidebar"><i class="fas fa-credit-card"></i> Pembayaran</a>
            <a href="notifikasi.php" class="nav-link-sidebar"><i class="fas fa-bell"></i> Notifikasi</a>
            <a href="laporan.php" class="nav-link-sidebar"><i class="fas fa-chart-bar"></i> Laporan</a>
            <a href="users.php" class="nav-link-sidebar"><i class="fas fa-user-cog"></i> Pengguna</a>
            <hr style="border-color:rgba(255,255,255,0.1)">
            <a href="../logout.php" class="nav-link-sidebar text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="content-header">
            <div>
                <h3 class="fw-bold mb-1">Tambah Reservasi Offline</h3>
                <p class="text-muted small mb-0">Input data pemesanan yang dilakukan secara langsung/tunai</p>
            </div>
            <a href="bookings.php" class="btn btn-light rounded-pill px-4"><i class="fas fa-chevron-left me-2"></i>Kembali</a>
        </div>

        <div class="card-form shadow-sm" style="max-width: 900px;">
            <div class="card-form-body">
                <?php echo $message; ?>

                <form method="POST">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label"><i class="fas fa-door-open"></i>Pilih Kamar</label>
                            <select name="id_kamar" id="id_kamar" class="form-select" required onchange="calculateLogic()">
                                <option value="">-- Pilih Kamar Tersedia --</option>
                                <?php
                                $rooms = $conn->query("SELECT * FROM kamar WHERE status_kamar = 'tersedia'");
                                while ($r = $rooms->fetch_assoc()) {
                                    echo "<option value='{$r['id_kamar']}' data-monthly='{$r['harga_per_bulan']}' data-yearly='{$r['harga_per_tahun']}'>{$r['nama_kamar']} (Lantai {$r['lantai']}) - Rp " . number_format($r['harga_per_bulan']) . "/bln</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-user"></i>Nama Pemesan</label>
                            <input type="text" name="nama_pemesan" class="form-control" placeholder="Nama lengkap pemesan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fab fa-whatsapp"></i>Nomor WhatsApp/HP</label>
                            <input type="text" name="no_hp_pemesan" class="form-control" placeholder="08xxxxxxxxxx" required>
                        </div>

                        <div class="col-md-12">
                            <hr class="my-2 opacity-50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-clock"></i>Tipe Sewa</label>
                            <div class="segment-control">
                                <div class="segment-item">
                                    <input type="radio" name="duration_type" id="dtMonthly" value="Monthly" checked class="segment-input" onchange="handleTypeChange()">
                                    <label for="dtMonthly" class="segment-label">Bulanan</label>
                                </div>
                                 <div class="segment-item">
                                    <input type="radio" name="duration_type" id="dtYearly" value="Yearly" class="segment-input" onchange="handleTypeChange()">
                                    <label for="dtYearly" class="segment-label">Tahunan</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" id="durationLabel"><i class="fas fa-hourglass-half"></i>Durasi (Bulan)</label>
                            <select name="duration_value" id="durationValue" class="form-select" onchange="calculateLogic()">
                                <?php for($i=1; $i<=11; $i++) echo "<option value='$i'>$i Bulan</option>"; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-calendar-alt"></i>Tanggal Masuk</label>
                            <input type="date" name="tanggal_masuk" id="tanggal_masuk" class="form-control" required value="<?php echo date('Y-m-d'); ?>" onchange="calculateLogic()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-calendar-check"></i>Tanggal Keluar (Otomatis)</label>
                            <input type="date" id="tanggal_keluar" class="form-control" readonly style="background-color: #f1f5f9;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-wallet"></i>Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="form-select" required>
                                <option value="Tunai">Tunai / Cash</option>
                                <option value="Transfer">Transfer Bank</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                             <div class="price-summary d-flex flex-column justify-content-center">
                                <span class="price-label">Estimasi Total Biaya</span>
                                <div class="price-value" id="priceDisplay">Rp 0</div>
                                <input type="hidden" name="total_harga" id="total_harga" value="0">
                             </div>
                        </div>
                    </div>

                    <div class="mt-5 d-flex gap-3">
                        <button type="submit" class="btn-save"><i class="fas fa-save me-2"></i>Simpan Pemesanan</button>
                        <a href="bookings.php" class="btn btn-light rounded-pill px-4 align-self-center py-2">Batalkan</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function handleTypeChange() {
            const type = document.querySelector('input[name="duration_type"]:checked').value;
            const durationSelect = document.getElementById('durationValue');
            const durationLabel = document.getElementById('durationLabel');
            
            let options = '';
            if (type === 'Monthly') {
                durationLabel.innerHTML = '<i class="fas fa-hourglass-half"></i>Durasi (Bulan)';
                for (let i = 1; i <= 11; i++) {
                    options += `<option value="${i}">${i} Bulan</option>`;
                }
            } else {
                durationLabel.innerHTML = '<i class="fas fa-hourglass-half"></i>Durasi (Tahun)';
                for (let i = 1; i <= 5; i++) {
                    options += `<option value="${i}">${i} Tahun</option>`;
                }
            }
            durationSelect.innerHTML = options;
            calculateLogic();
        }

        function calculateLogic() {
            const select = document.getElementById('id_kamar');
            const durationType = document.querySelector('input[name="duration_type"]:checked').value;
            const durationValue = parseInt(document.getElementById('durationValue').value);
            const tglMasuk = document.getElementById('tanggal_masuk').value;
            const tglKeluarInput = document.getElementById('tanggal_keluar');
            const priceInput = document.getElementById('total_harga');
            const priceDisplay = document.getElementById('priceDisplay');

            // Update Price
            if (select.selectedIndex > 0) {
                const pricePerPeriod = durationType === 'Monthly' 
                    ? select.options[select.selectedIndex].getAttribute('data-monthly')
                    : select.options[select.selectedIndex].getAttribute('data-yearly');
                
                const total = pricePerPeriod * durationValue;
                priceInput.value = total;
                priceDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
            } else {
                priceInput.value = 0;
                priceDisplay.innerText = 'Rp 0';
            }

            // Update Date
            if (tglMasuk) {
                const dateIn = new Date(tglMasuk);
                const dateOut = new Date(dateIn);
                
                if (durationType === 'Monthly') {
                    dateOut.setMonth(dateIn.getMonth() + durationValue);
                } else {
                    dateOut.setFullYear(dateIn.getFullYear() + durationValue);
                }
                
                const y = dateOut.getFullYear();
                const m = String(dateOut.getMonth() + 1).padStart(2, '0');
                const d = String(dateOut.getDate()).padStart(2, '0');
                tglKeluarInput.value = `${y}-${m}-${d}`;
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>