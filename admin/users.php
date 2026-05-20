<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin_login.php");
    exit();
}
require_once '../config/database.php';

$success_msg = '';
$error_msg = '';

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Prevent deleting self
    if ($id == $_SESSION['user_id']) {
        $error_msg = "Tidak dapat menghapus akun sendiri.";
    } else {
        $check = $conn->prepare("SELECT peran FROM pengguna WHERE id_pengguna = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $res = $check->get_result();
        if($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if($user['peran'] == 'admin') {
                 $error_msg = "Tidak dapat menghapus akun Admin.";
            } else {
                $stmt = $conn->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    header("Location: users.php?msg=deleted");
                    exit();
                }
            }
        }
    }
}

// Handle Add/Edit Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengguna = isset($_POST['id_pengguna']) ? (int)$_POST['id_pengguna'] : 0;
    $username     = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $no_hp        = trim($_POST['no_hp']);
    $peran        = $_POST['peran'] ?? 'penyewa';
    $password     = $_POST['password'] ?? '';

    if ($id_pengguna > 0) {
        // Edit Mode
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd = $conn->prepare("UPDATE pengguna SET username=?, nama_lengkap=?, no_hp=?, kata_sandi=?, peran=? WHERE id_pengguna=?");
            $upd->bind_param("sssssi", $username, $nama_lengkap, $no_hp, $hashed, $peran, $id_pengguna);
        } else {
            $upd = $conn->prepare("UPDATE pengguna SET username=?, nama_lengkap=?, no_hp=?, peran=? WHERE id_pengguna=?");
            $upd->bind_param("ssssi", $username, $nama_lengkap, $no_hp, $peran, $id_pengguna);
        }
        
        if ($upd->execute()) {
            $success_msg = "Data pengguna berhasil diperbarui!";
        } else {
            $error_msg = "Gagal memperbarui data: " . $conn->error;
        }
    } else {
        // Add Mode
        $cek = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $error_msg = "Username sudah terdaftar.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $ins = $conn->prepare("INSERT INTO pengguna (username, nama_lengkap, no_hp, kata_sandi, peran) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("sssss", $username, $nama_lengkap, $no_hp, $hashed, $peran);
            if ($ins->execute()) {
                $success_msg = "Pengguna baru berhasil ditambahkan!";
            } else {
                $error_msg = "Gagal menambahkan pengguna: " . $conn->error;
            }
        }
    }
}

$msg = $_GET['msg'] ?? '';
if($msg == 'deleted') $success_msg = "Pengguna berhasil dihapus.";

// Get user count by role
$total_users = $conn->query("SELECT COUNT(*) as c FROM pengguna")->fetch_assoc()['c'] ?? 0;
$total_admin = $conn->query("SELECT COUNT(*) as c FROM pengguna WHERE peran='admin'")->fetch_assoc()['c'] ?? 0;
$total_penyewa = $conn->query("SELECT COUNT(*) as c FROM pengguna WHERE peran='penyewa'")->fetch_assoc()['c'] ?? 0;

// Get users (kata_sandi TIDAK diambil agar tidak terekspos ke browser)
$sql = "SELECT id_pengguna, username, nama_lengkap, no_hp, peran, dibuat_pada FROM pengguna ORDER BY dibuat_pada DESC";
$users_list = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Berkah Malika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; box-sizing: border-box; }
        body { background: #f0f2f8; margin: 0; }

        .sidebar { width: 250px; position: fixed; top: 0; left: 0; height: 100vh; background: linear-gradient(180deg, #1e293b, #0f172a); z-index: 100; overflow-y: auto; }
        .sidebar-brand { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link-sidebar { color: rgba(255,255,255,0.7); padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; border-radius: 0; transition: all 0.2s; text-decoration: none; font-size: 0.9rem; }
        .nav-link-sidebar:hover, .nav-link-sidebar.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-link-sidebar i { width: 20px; text-align: center; }

        /* ── Main Content ────────────────── */
        .main-content { margin-left: 250px; padding: 2rem; min-height: 100vh; }

        /* ── Page Header ─────────────────── */
        .page-header {
            background: linear-gradient(135deg, #4f46e5 0%, #6d28d9 50%, #3730a3 100%);
            border-radius: 20px; padding: 2rem 2.5rem;
            color: white; margin-bottom: 2rem;
            position: relative; overflow: hidden;
            box-shadow: 0 10px 40px rgba(79,70,229,0.35);
        }
        .page-header::before {
            content: '';
            position: absolute; right: -40px; top: -40px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .page-header::after {
            content: '';
            position: absolute; right: 80px; bottom: -60px;
            width: 150px; height: 150px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .page-header h4 { font-weight: 700; font-size: 1.4rem; margin-bottom: 0.3rem; }
        .page-header p { opacity: 0.7; font-size: 0.85rem; margin: 0; }
        .btn-add-user {
            background: rgba(255,255,255,0.15);
            color: white !important;
            border: 1.5px solid rgba(255,255,255,0.3);
            border-radius: 50px; padding: 0.65rem 1.5rem;
            font-weight: 600; font-size: 0.85rem;
            backdrop-filter: blur(10px);
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem;
            transition: all 0.3s;
        }
        .btn-add-user:hover { background: rgba(255,255,255,0.25); transform: translateY(-2px); }

        /* ── Stats Row ────────────────────── */
        .stat-mini {
            background: white;
            border-radius: 16px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            display: flex; align-items: center; gap: 1rem;
            transition: transform 0.3s;
        }
        .stat-mini:hover { transform: translateY(-3px); }
        .stat-mini-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .stat-mini .num { font-size: 1.6rem; font-weight: 700; line-height: 1; color: #1e293b; }
        .stat-mini .lbl { font-size: 0.75rem; color: #94a3b8; font-weight: 500; margin-top: 2px; }

        /* ── Table Card ───────────────────── */
        .table-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .table-card-header {
            padding: 1.3rem 1.8rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
        }
        .table-card-header h6 { font-weight: 700; margin: 0; color: #1e293b; font-size: 0.95rem; }

        .table { margin: 0; }
        .table thead th {
            background: #f8fafc;
            font-weight: 600; font-size: 0.72rem;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #94a3b8; border: 0; padding: 1rem 1.2rem;
        }
        .table tbody td {
            padding: 1rem 1.2rem;
            vertical-align: middle;
            border-color: #f1f5f9;
            font-size: 0.875rem;
        }
        .table tbody tr:hover td { background: #f8fafc; }

        /* Avatar */
        .avatar-circle {
            width: 40px; height: 40px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1rem;
            flex-shrink: 0;
        }
        .avatar-admin { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
        .avatar-penyewa { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }

        /* Role Badges */
        .role-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            border-radius: 50px; padding: 0.3rem 0.9rem;
            font-size: 0.72rem; font-weight: 600;
        }
        .role-badge.admin { background: #fee2e2; color: #dc2626; }
        .role-badge.penyewa { background: #d1fae5; color: #059669; }
        .role-badge .dot { width: 6px; height: 6px; border-radius: 50%; }
        .role-badge.admin .dot { background: #dc2626; }
        .role-badge.penyewa .dot { background: #059669; }

        /* Action Buttons */
        .btn-action {
            width: 34px; height: 34px;
            border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
            border: 0; transition: all 0.2s; cursor: pointer;
            text-decoration: none;
        }
        .btn-action.edit { background: #eef2ff; color: #4f46e5; }
        .btn-action.edit:hover { background: #4f46e5; color: white; transform: scale(1.1); }
        .btn-action.del { background: #fef2f2; color: #dc2626; }
        .btn-action.del:hover { background: #dc2626; color: white; transform: scale(1.1); }

        /* Alerts */
        .alert-success-custom {
            background: #f0fdf4; border: 1px solid #bbf7d0;
            border-radius: 14px; color: #15803d;
            padding: 0.9rem 1.2rem; margin-bottom: 1.5rem;
        }
        .alert-danger-custom {
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 14px; color: #dc2626;
            padding: 0.9rem 1.2rem; margin-bottom: 1.5rem;
        }

        /* Modal */
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .modal-header-custom {
            background: linear-gradient(135deg, #4f46e5, #6d28d9);
            padding: 1.5rem 2rem;
            border: none;
        }
        .modal-header-custom .modal-title { color: white; font-weight: 700; font-size: 1.1rem; }
        .modal-header-custom .btn-close { filter: brightness(0) invert(1); opacity: 0.7; }
        .modal-body-custom { padding: 2rem; background: #fafafa; }
        .modal-footer-custom { padding: 1.2rem 2rem; border-top: 1px solid #f1f5f9; background: white; }

        .form-label { font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.4rem; }
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 0.65rem 1rem;
            font-size: 0.88rem;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
        }

        /* Input group icon */
        .input-group-text-icon {
            position: absolute; left: 0.85rem; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8; font-size: 0.85rem; pointer-events: none;
        }
        .has-icon { padding-left: 2.5rem !important; }

        .btn-save {
            background: linear-gradient(135deg, #4f46e5, #6d28d9);
            color: white; border: none;
            border-radius: 12px; padding: 0.7rem 2rem;
            font-weight: 600; font-size: 0.9rem;
            transition: all 0.3s;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(79,70,229,0.4); color: white; }

        /* Empty State */
        .empty-state-row td {
            padding: 4rem !important;
            text-align: center;
            color: #94a3b8;
        }
        .empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4; }

        /* Search */
        .search-box {
            border-radius: 50px !important;
            border: 2px solid #e2e8f0 !important;
            padding: 0.5rem 1rem 0.5rem 2.4rem !important;
            font-size: 0.85rem !important;
            width: 220px;
        }
        .search-box:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1) !important;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="text-white fw-bold"><i class="fas fa-home me-2 text-indigo-400"></i>Berkah Malika</div>
            <small class="text-white-50 small">Panel Admin</small>
        </div>
        <nav class="mt-2">
            <a href="index.php" class="nav-link-sidebar"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="rooms.php" class="nav-link-sidebar"><i class="fas fa-bed"></i> Kelola Kamar</a>
            <a href="bookings.php" class="nav-link-sidebar"><i class="fas fa-calendar-check"></i> Reservasi</a>
            <a href="penyewa.php" class="nav-link-sidebar"><i class="fas fa-users"></i> Data Penyewa</a>
            <a href="pembayaran.php" class="nav-link-sidebar"><i class="fas fa-credit-card"></i> Pembayaran</a>
            <a href="notifikasi.php" class="nav-link-sidebar"><i class="fas fa-bell"></i> Notifikasi</a>
            <a href="laporan.php" class="nav-link-sidebar"><i class="fas fa-chart-bar"></i> Laporan</a>
            <a href="users.php" class="nav-link-sidebar active"><i class="fas fa-user-cog"></i> Pengguna</a>
            <hr style="border-color:rgba(255,255,255,0.1)">
            <a href="../logout.php" class="nav-link-sidebar text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">

        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center">
            <div style="position: relative; z-index: 1;">
                <h4><i class="fas fa-user-shield me-2"></i>Manajemen Pengguna</h4>
                <p>Kelola akun admin dan penyewa yang terdaftar di sistem</p>
            </div>
            <div style="position: relative; z-index: 1;">
                <a href="#" class="btn-add-user" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                    <i class="fas fa-user-plus"></i> Tambah Pengguna
                </a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-mini">
                    <div class="stat-mini-icon" style="background: linear-gradient(135deg, #eef2ff, #e0e7ff);">
                        <i class="fas fa-users" style="color: #4f46e5;"></i>
                    </div>
                    <div>
                        <div class="num"><?php echo $total_users; ?></div>
                        <div class="lbl">Total Pengguna</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini">
                    <div class="stat-mini-icon" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                        <i class="fas fa-user-shield" style="color: #dc2626;"></i>
                    </div>
                    <div>
                        <div class="num"><?php echo $total_admin; ?></div>
                        <div class="lbl">Admin</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini">
                    <div class="stat-mini-icon" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                        <i class="fas fa-user-check" style="color: #059669;"></i>
                    </div>
                    <div>
                        <div class="num"><?php echo $total_penyewa; ?></div>
                        <div class="lbl">Penyewa</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success_msg): ?>
            <div class="alert-success-custom"><i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert-danger-custom"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-card-header">
                <h6><i class="fas fa-list me-2 text-indigo-600" style="color:#4f46e5"></i>Daftar Pengguna</h6>
                <div class="position-relative">
                    <i class="fas fa-search position-absolute" style="left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem;"></i>
                    <input type="text" id="searchInput" class="search-box form-control" placeholder="Cari pengguna...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="usersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Pengguna</th>
                            <th>Kontak</th>
                            <th>Peran</th>
                            <th>Terdaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($users_list->num_rows > 0): 
                            while($row = $users_list->fetch_assoc()): 
                                $isAdmin = $row['peran'] == 'admin';
                        ?>
                        <tr>
                            <td><small class="text-muted fw-bold"><?php echo $no++; ?></small></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle <?php echo $isAdmin ? 'avatar-admin' : 'avatar-penyewa'; ?>">
                                        <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size:0.9rem"><?php echo htmlspecialchars($row['nama_lengkap']); ?></div>
                                        <div class="text-muted" style="font-size:0.78rem">@<?php echo htmlspecialchars($row['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-muted" style="font-size:0.78rem"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($row['no_hp'] ?: '-'); ?></div>
                            </td>
                            <td>
                                <span class="role-badge <?php echo $isAdmin ? 'admin' : 'penyewa'; ?>">
                                    <span class="dot"></span>
                                    <?php echo ucfirst($row['peran']); ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo date('d M Y', strtotime($row['dibuat_pada'])); ?></small>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button class="btn-action edit"
                                        title="Edit"
                                        onclick='editUser(<?php echo json_encode([
                                            "id_pengguna" => $row["id_pengguna"],
                                            "username"    => $row["username"],
                                            "nama_lengkap"=> $row["nama_lengkap"],
                                            "no_hp"       => $row["no_hp"],
                                            "peran"       => $row["peran"]
                                        ]); ?>)'>
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <?php if ($row['id_pengguna'] != $_SESSION['user_id'] && $row['peran'] != 'admin'): ?>
                                    <a href="users.php?action=delete&id=<?php echo $row['id_pengguna']; ?>"
                                        class="btn-action del" title="Hapus"
                                        onclick="return confirm('Yakin ingin menghapus user ini?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="btn-action" style="background:#f1f5f9;color:#cbd5e1;cursor:default;" title="Tidak bisa dihapus">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr class="empty-state-row">
                            <td colspan="6">
                                <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
                                <div class="fw-bold" style="color:#475569">Belum ada data pengguna</div>
                                <div class="small mt-1" style="color:#94a3b8">Tambahkan pengguna baru dengan klik tombol di atas</div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit User -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="modal-title mb-0" id="modalTitle">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1); opacity: 0.7;"></button>
                </div>
                <form method="POST">
                    <div class="modal-body-custom">
                        <input type="hidden" name="id_pengguna" id="uid">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-at me-1"></i>Username</label>
                                <div class="position-relative">
                                    <input type="text" name="username" id="u_username" class="form-control has-icon" required placeholder="username123">
                                    <i class="fas fa-user input-group-text-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-tag me-1"></i>Peran</label>
                                <select name="peran" id="u_peran" class="form-select" required>
                                    <option value="penyewa">🏠 Penyewa</option>
                                    <option value="admin">🛡️ Admin</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label"><i class="fas fa-id-badge me-1"></i>Nama Lengkap</label>
                                <div class="position-relative">
                                    <input type="text" name="nama_lengkap" id="u_nama" class="form-control has-icon" required placeholder="Nama Lengkap">
                                    <i class="fas fa-signature input-group-text-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-phone me-1"></i>No HP</label>
                                <div class="position-relative">
                                    <input type="text" name="no_hp" id="u_nohp" class="form-control has-icon" placeholder="08xxxxxxxxxx">
                                    <i class="fas fa-phone input-group-text-icon"></i>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label"><i class="fas fa-lock me-1"></i>Password</label>
                                <div class="position-relative">
                                    <input type="password" name="password" id="u_pass" class="form-control has-icon" placeholder="Masukkan password baru" style="padding-right: 2.5rem;" autocomplete="new-password">
                                    <i class="fas fa-lock input-group-text-icon"></i>
                                    <i class="fas fa-eye position-absolute" id="toggleShowPassword" style="right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; z-index: 10;"></i>
                                </div>
                                <small class="text-muted" id="passNote" style="font-size:0.75rem">
                                    <i class="fas fa-info-circle me-1"></i>Wajib diisi untuk tambah pengguna baru.
                                </small>
                                <div id="secureNote" class="mt-1 d-none" style="font-size:0.7rem; color: #059669;">
                                    <i class="fas fa-shield-alt me-1"></i> Password tersimpan & terenkripsi (keamanan data)
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer-custom d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-save px-4 rounded-pill">
                            <i class="fas fa-save me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('toggleShowPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('u_pass');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });

        function resetForm() {
            document.getElementById('modalTitle').innerText = 'Tambah Pengguna Baru';
            document.getElementById('uid').value = '';
            document.getElementById('u_username').value = '';
            document.getElementById('u_nama').value = '';
            document.getElementById('u_nohp').value = '';
            document.getElementById('u_peran').value = 'penyewa';
            document.getElementById('u_pass').required = true;
            document.getElementById('u_pass').setAttribute('type', 'password');
            const toggleIcon = document.getElementById('toggleShowPassword');
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
            document.getElementById('passNote').innerHTML = '<i class="fas fa-info-circle me-1"></i>Wajib diisi untuk tambah pengguna baru.';
            document.getElementById('secureNote').classList.add('d-none');
        }

        function editUser(data) {
            document.getElementById('modalTitle').innerText = 'Edit Pengguna';
            document.getElementById('uid').value = data.id_pengguna;
            document.getElementById('u_username').value = data.username;
            document.getElementById('u_nama').value = data.nama_lengkap;
            document.getElementById('u_nohp').value = data.no_hp;
            document.getElementById('u_peran').value = data.peran;
            document.getElementById('u_pass').value = '';
            document.getElementById('u_pass').setAttribute('type', 'password');
            const toggleIcon = document.getElementById('toggleShowPassword');
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
            document.getElementById('u_pass').required = false;
            document.getElementById('passNote').innerHTML = '<i class="fas fa-info-circle me-1"></i>Kosongkan jika tidak ingin mengubah password.';
            document.getElementById('secureNote').classList.remove('d-none');
            
            var modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }

        // Search filter
        document.getElementById('searchInput').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr:not(.empty-state-row)');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });

        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert-success-custom, .alert-danger-custom').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 4000);
    </script>
</body>
</html>
