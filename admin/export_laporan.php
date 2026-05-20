<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit('Akses ditolak');
}
require_once '../config/database.php';

$format = $_GET['format'] ?? 'html';
$bulan = (int)($_GET['bulan'] ?? date('m'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$tampil_semua = ($bulan === 0);

$bulan_names = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$nama_bulan = $tampil_semua ? 'Semua Bulan' : $bulan_names[$bulan - 1];

if ($tampil_semua) {
    // Pendapatan seluruh tahun
    $query_sekarang = $conn->query("SELECT SUM(total_harga) as t FROM reservasi WHERE YEAR(dibuat_pada) = '$tahun' AND status_reservasi = 'Dikonfirmasi'");
    $pendapatan_sekarang = $query_sekarang->fetch_assoc()['t'] ?? 0;
    $pendapatan_sebelumnya = 0;
    $prev_bulan = 0;
    $prev_tahun = $tahun - 1;
} else {
    // Pendapatan Sekarang
    $bulan_fmt = sprintf('%04d-%02d', $tahun, $bulan);
    $query_sekarang = $conn->query("SELECT SUM(total_harga) as t FROM reservasi WHERE DATE_FORMAT(dibuat_pada, '%Y-%m') = '$bulan_fmt' AND status_reservasi = 'Dikonfirmasi'");
    $pendapatan_sekarang = $query_sekarang->fetch_assoc()['t'] ?? 0;

    // Pendapatan Sebelumnya
    $prev_bulan = $bulan - 1;
    $prev_tahun = $tahun;
    if ($prev_bulan == 0) {
        $prev_bulan = 12;
        $prev_tahun--;
    }
    $prev_bulan_fmt = sprintf('%04d-%02d', $prev_tahun, $prev_bulan);
    $query_sebelumnya = $conn->query("SELECT SUM(total_harga) as t FROM reservasi WHERE DATE_FORMAT(dibuat_pada, '%Y-%m') = '$prev_bulan_fmt' AND status_reservasi = 'Dikonfirmasi'");
    $pendapatan_sebelumnya = $query_sebelumnya->fetch_assoc()['t'] ?? 0;
}

// Total Seluruh Pendapatan
$query_all = $conn->query("SELECT SUM(total_harga) as t FROM reservasi WHERE status_reservasi = 'Dikonfirmasi'");
$total_pendapatan_all = $query_all->fetch_assoc()['t'] ?? 0;

if ($format == 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Laporan_Keuangan_{$nama_bulan}_{$tahun}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo "LAPORAN KEUANGAN KOS BERKAH MALIKA\n";
    echo "Periode\t: {$nama_bulan} {$tahun}\n\n";
    echo "Keterangan\tTotal (Rp)\n";
    echo "Pendapatan Sebelumnya ({$bulan_names[$prev_bulan-1]} {$prev_tahun})\t" . number_format($pendapatan_sebelumnya, 0, ',', '.') . "\n";
    echo "Pendapatan Sekarang\t" . number_format($pendapatan_sekarang, 0, ',', '.') . "\n";
    echo "Total Seluruh Pendapatan\t" . number_format($total_pendapatan_all, 0, ',', '.') . "\n\n";
    
    echo "RIWAYAT TRANSAKSI {$nama_bulan} {$tahun}\n";
    echo "Tanggal\tKode Pesanan\tPemesan\tKamar\tDurasi\tMetode\tStatus\tJumlah (Rp)\n";
    
    $query_hist = $conn->prepare("SELECT pb.*, r.nama_pemesan, r.durasi_sewa, k.nama_kamar 
                                  FROM pembayaran pb 
                                  JOIN reservasi r ON pb.id_reservasi = r.id_reservasi 
                                  JOIN kamar k ON r.id_kamar = k.id_kamar 
                                  WHERE MONTH(pb.dibuat_pada) = ? AND YEAR(pb.dibuat_pada) = ? 
                                  ORDER BY pb.dibuat_pada ASC");
    $query_hist->bind_param("ii", $bulan, $tahun);
    $query_hist->execute();
    $riwayat = $query_hist->get_result();
    
    while ($r = $riwayat->fetch_assoc()) {
        $durasi = !empty($r['durasi_sewa']) ? $r['durasi_sewa'] : (($r['jumlah_bayar'] % 7000000 == 0) ? ($r['jumlah_bayar']/7000000).' Tahun' : ($r['jumlah_bayar']/700000).' Bulan');
        echo date('d/m/Y', strtotime($r['dibuat_pada'])) . "\t";
        echo $r['kode_pesanan'] . "\t";
        echo $r['nama_pemesan'] . "\t";
        echo $r['nama_kamar'] . "\t";
        echo $durasi . "\t";
        echo $r['jenis_pembayaran'] . "\t";
        echo $r['status_transaksi'] . "\t";
        echo $r['jumlah_bayar'] . "\n";
    }
    exit();
}

// FORMAT PDF
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan <?php echo $nama_bulan . ' ' . $tahun; ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; color: #333; }
        h2 { text-align: center; margin-bottom: 5px; text-transform: uppercase; }
        .subtitle { text-align: center; color: #666; margin-bottom: 40px; font-size: 1.1em; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th, td { border: 1px solid #ddd; padding: 15px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .amount { text-align: right; font-weight: bold; font-size: 1.1em; }
        .success { color: #198754; }
        .primary { color: #0d6efd; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #dc3545; color: white; border: none; cursor: pointer; border-radius: 5px; font-weight: bold;">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>
    
    <h2>Laporan Keuangan Kos Berkah Malika</h2>
    <div class="subtitle">Periode Rekapitulasi: <strong><?php echo $nama_bulan . ' ' . $tahun; ?></strong></div>

    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th style="text-align: right;">Jumlah Pemasukan (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$tampil_semua): ?>
            <tr>
                <td>Pendapatan Sebelumnya (<?php echo $bulan_names[$prev_bulan-1] . ' ' . $prev_tahun; ?>)</td>
                <td class="amount"><?php echo number_format($pendapatan_sebelumnya, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td>Pendapatan Sekarang (Bulan Ini)</td>
                <td class="amount success"><?php echo number_format($pendapatan_sekarang, 0, ',', '.'); ?></td>
            </tr>
            <?php else: ?>
            <tr>
                <td>Total Pendapatan Tahun <?php echo $tahun; ?></td>
                <td class="amount success"><?php echo number_format($pendapatan_sekarang, 0, ',', '.'); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="font-weight: bold;">Total Seluruh Pendapatan (Keseluruhan)</td>
                <td class="amount primary"><?php echo number_format($total_pendapatan_all, 0, ',', '.'); ?></td>
            </tr>
        </tbody>
    </table>

    <h3 style="margin-top: 40px; border-bottom: 2px solid #333; padding-bottom: 10px;">Riwayat Transaksi</h3>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kode Pesanan</th>
                <th>Pemesan</th>
                <th>Kamar</th>
                <th>Durasi</th>
                <th>Metode</th>
                <th>Status</th>
                <th style="text-align: right;">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($tampil_semua) {
                $query_hist = $conn->prepare("SELECT pb.*, r.nama_pemesan, r.durasi_sewa, k.nama_kamar 
                                              FROM pembayaran pb 
                                              JOIN reservasi r ON pb.id_reservasi = r.id_reservasi 
                                              JOIN kamar k ON r.id_kamar = k.id_kamar 
                                              WHERE YEAR(pb.dibuat_pada) = ? 
                                              ORDER BY pb.dibuat_pada ASC");
                $query_hist->bind_param("i", $tahun);
            } else {
                $query_hist = $conn->prepare("SELECT pb.*, r.nama_pemesan, r.durasi_sewa, k.nama_kamar 
                                              FROM pembayaran pb 
                                              JOIN reservasi r ON pb.id_reservasi = r.id_reservasi 
                                              JOIN kamar k ON r.id_kamar = k.id_kamar 
                                              WHERE MONTH(pb.dibuat_pada) = ? AND YEAR(pb.dibuat_pada) = ? 
                                              ORDER BY pb.dibuat_pada ASC");
                $query_hist->bind_param("ii", $bulan, $tahun);
            }
            $query_hist->execute();
            $riwayat = $query_hist->get_result();
            
            if ($riwayat->num_rows > 0):
                while ($r = $riwayat->fetch_assoc()): 
                    $durasi = !empty($r['durasi_sewa']) ? $r['durasi_sewa'] : (($r['jumlah_bayar'] % 7000000 == 0) ? ($r['jumlah_bayar']/7000000).' Tahun' : ($r['jumlah_bayar']/700000).' Bulan');
                ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($r['dibuat_pada'])); ?></td>
                <td><code><?php echo htmlspecialchars($r['kode_pesanan']); ?></code></td>
                <td><?php echo htmlspecialchars($r['nama_pemesan']); ?></td>
                <td><?php echo htmlspecialchars($r['nama_kamar']); ?></td>
                <td><?php echo htmlspecialchars($durasi); ?></td>
                <td><?php echo htmlspecialchars($r['jenis_pembayaran'] ?: '-'); ?></td>
                <td><small><?php echo strtoupper($r['status_transaksi']); ?></small></td>
                <td style="text-align: right; font-weight: bold;">Rp <?php echo number_format($r['jumlah_bayar'], 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7" style="text-align: center; color: #999;">Tidak ada data transaksi di bulan ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: right;">
        <p>Mataram, <?php echo date('d F Y', strtotime(date('Y-m-d'))); ?></p>
        <br><br><br>
        <p><strong>Administrator Kos</strong></p>
    </div>
    
    <script>
        // Opsional: otomatis buka pop-up print saat halaman dimuat
        // window.onload = window.print;
    </script>
</body>
</html>
