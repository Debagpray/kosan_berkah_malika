<?php
$base_path = isset($is_root) && $is_root ? '' : '../';
$users_path = isset($is_root) && $is_root ? 'users/' : '';

// Hitung notifikasi belum dibaca jika pengguna login
$notif_unread = 0;
if (isset($_SESSION['user_id'])) {
    if (isset($conn)) {
        $notif_query = $conn->query("SELECT COUNT(*) as c FROM notifikasi WHERE id_pengguna = {$_SESSION['user_id']} AND status_baca = 'belum dibaca'");
        if ($notif_query) {
            $notif_unread = $notif_query->fetch_assoc()['c'];
        }
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm mx-3 mt-3 rounded-3" style="background: rgba(255, 255, 255, 0.95) !important; backdrop-filter: blur(10px);">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="<?php echo $base_path; ?>index.php">
            <i class="fas fa-home me-2"></i>Kos Berkah Malika
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">

                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $users_path; ?>status_kamar.php">
                        <i class="fas fa-door-open me-1"></i> Status Kamar
                    </a>
                </li>
                
                <?php if (isset($_SESSION['username'])): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>admin/index.php">
                                <i class="fas fa-shield-alt me-1"></i> Admin Panel
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $users_path; ?>pesanan_saya.php">
                                <i class="fas fa-calendar-alt me-1 text-success"></i> Pesanan Saya
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $users_path; ?>form_data_penyewa.php">
                                <i class="fas fa-id-card me-1 text-info"></i> Data Penyewa
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle fs-5 me-2"></i>
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <?php if ($notif_unread > 0): ?>
                                    <span class="ms-1 badge rounded-pill bg-danger" style="font-size:0.6rem"><?php echo $notif_unread; ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item py-2" href="<?php echo $users_path; ?>profil.php">
                                        <i class="fas fa-user-edit me-2 text-primary"></i>Profil Saya
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex justify-content-between align-items-center" href="<?php echo $users_path; ?>notifikasi.php">
                                        <span><i class="fas fa-bell me-2 text-warning"></i>Notifikasi</span>
                                        <?php if ($notif_unread > 0): ?>
                                            <span class="badge bg-danger rounded-pill"><?php echo $notif_unread; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2 text-danger" href="<?php echo $base_path; ?>logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Keluar
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-danger ms-2 rounded-pill px-4" href="<?php echo $base_path; ?>logout.php">Logout</a>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $users_path; ?>login.php">Masuk</a></li>
                    <li class="nav-item"><a class="btn btn-primary ms-2 rounded-pill px-4" href="<?php echo $users_path; ?>daftar.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Spacer for pages other than index to prevent content from hiding behind fixed navbar -->
<?php if (!isset($is_root) || !$is_root): ?>
    <div style="height: 85px;"></div>
<?php endif; ?>
