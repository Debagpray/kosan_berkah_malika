# Flowchart Sistem Kos Berkah Malika

## 1. Flowchart Utama Sistem (Overview)

```mermaid
flowchart TD
    A([Mulai]) --> B[Buka Website\nKos Berkah Malika]
    B --> C{Sudah Login?}
    C -- Tidak --> D{Pilih Aksi}
    D -- Daftar --> E[Halaman Registrasi]
    D -- Login User --> F[Login Penyewa]
    D -- Login Admin --> G[Login Admin]
    C -- Ya --> H{Role?}
    H -- Penyewa --> I[Halaman Beranda User]
    H -- Admin --> J[Dashboard Admin]
    E --> K[Isi Form Registrasi]
    K --> L{Validasi Data}
    L -- Gagal --> K
    L -- Berhasil --> M[Akun Dibuat\nRedirect ke Login]
    M --> F
    F --> N{Autentikasi}
    N -- Gagal --> F
    N -- Berhasil --> I
    G --> O{Autentikasi Admin}
    O -- Gagal --> G
    O -- Berhasil --> J
    I --> P[Lihat Kamar & Status]
    P --> Q{Pesan Kamar?}
    Q -- Ya --> R[Proses Pemesanan]
    Q -- Tidak --> S[Lihat Pesanan Saya]
    R --> T[Pembayaran Midtrans]
    T --> U{Status Bayar}
    U -- Sukses --> V[Reservasi Menunggu\nKonfirmasi Admin]
    U -- Gagal/Batal --> W[Reservasi Dibatalkan]
    J --> X[Kelola Sistem]
    X --> Y([Selesai])
    V --> Z[Admin Konfirmasi]
    Z --> AA[Penyewa Aktif]
    AA --> Y
```

---

## 2. Flowchart Registrasi User

```mermaid
flowchart TD
    A([Mulai]) --> B[Buka Halaman Daftar]
    B --> C[Isi Form:\nUsername, Nama Lengkap,\nNo HP, Password,\nKonfirmasi Password]
    C --> D{No HP hanya angka?}
    D -- Tidak --> E[Tampilkan Error:\nNo HP hanya angka]
    E --> C
    D -- Ya --> F{Password = Konfirmasi?}
    F -- Tidak --> G[Tampilkan Error:\nPassword tidak cocok]
    G --> C
    F -- Ya --> H{Panjang Password ≥ 6?}
    H -- Tidak --> I[Tampilkan Error:\nPassword min 6 karakter]
    I --> C
    H -- Ya --> J{Username sudah\nterdaftar?}
    J -- Ya --> K[Tampilkan Error:\nUsername sudah ada]
    K --> C
    J -- Tidak --> L[Hash Password\nBCRYPT cost 12]
    L --> M[Simpan ke DB\ntabel pengguna\nrole = penyewa]
    M --> N[Redirect ke Login\ndengan pesan sukses]
    N --> O([Selesai])
```

---

## 3. Flowchart Login

```mermaid
flowchart TD
    A([Mulai]) --> B{Pilih Tipe Login}
    B -- User/Penyewa --> C[Buka users/login.php]
    B -- Admin --> D[Buka admin/login.php]
    C --> E[Isi Username & Password]
    D --> F[Isi Username & Password]
    E --> G[AuthController::login]
    F --> G
    G --> H{Username\nditemukan di DB?}
    H -- Tidak --> I[Tampilkan Error:\nUsername/Password salah]
    I --> E
    H -- Ya --> J{Verifikasi Password\nBCRYPT}
    J -- Gagal --> I
    J -- Berhasil --> K{Cek Role}
    K -- penyewa & akses admin --> L[Tampilkan Error:\nAccess Denied]
    L --> F
    K -- admin --> M[Set Session:\nrole = admin]
    M --> N[Redirect ke\nDashboard Admin]
    K -- penyewa --> O[Set Session:\nuser_id, username, role]
    O --> P[Redirect ke\nBeranda User]
    N --> Q([Selesai])
    P --> Q
```

---

## 4. Flowchart Pemesanan Kamar (User)

```mermaid
flowchart TD
    A([Mulai]) --> B{User sudah login?}
    B -- Tidak --> C[Redirect ke Login\ndengan pesan]
    C --> D([Selesai])
    B -- Ya --> E[Pilih Kamar di Beranda]
    E --> F[Klik Pesan Sekarang]
    F --> G{Kamar tersedia?}
    G -- Status perbaikan --> H[Tampilkan:\nKamar Tidak Tersedia]
    G -- Ada reservasi aktif --> H
    H --> D
    G -- Tersedia --> I[Tampilkan Form Pemesanan]
    I --> J[Isi Data:\nNama, No HP, Tipe Sewa,\nDurasi, Tanggal Masuk]
    J --> K[Sistem hitung\nTanggal Keluar & Total Harga]
    K --> L[Klik Lanjut ke Pembayaran]
    L --> M{Validasi:\nTanggal keluar > masuk?}
    M -- Tidak --> N[Tampilkan Error Tanggal]
    N --> J
    M -- Ya --> O[Simpan Reservasi ke DB\nstatus = Menunggu Pembayaran]
    O --> P[Request Snap Token\nke Midtrans API]
    P --> Q{Token berhasil\ndidapat?}
    Q -- Tidak --> R[Tampilkan Error\nGagal koneksi Midtrans]
    R --> D
    Q -- Ya --> S[Simpan ke tabel pembayaran\nstatus = pending]
    S --> T[Tampilkan Popup\nMidtrans Snap]
    T --> U{User menyelesaikan\npembayaran?}
    U -- Sukses --> V[Verify Payment ke API]
    V --> W[Update status pembayaran\nReservasi = Menunggu]
    W --> X[Redirect ke Pesanan Saya]
    U -- Pending --> V
    U -- Gagal/Error --> Y[Alert Pembayaran Gagal]
    Y --> X
    U -- Tutup Popup --> Z[Alert: Belum selesai bayar]
    Z --> X
    X --> D
```

---

## 5. Flowchart Konfirmasi Reservasi (Admin)

```mermaid
flowchart TD
    A([Mulai]) --> B[Admin Login]
    B --> C[Dashboard Admin]
    C --> D[Buka Menu Reservasi]
    D --> E[Sistem Auto-Cek\nPembayaran Pending\nke Midtrans API]
    E --> F{Ada pembayaran\npending?}
    F -- Ya --> G[Cek Status ke\nMidtrans API]
    G --> H{Status Midtrans?}
    H -- capture/settlement --> I[Update pembayaran = lunas\nReservasi = Menunggu]
    H -- cancel/deny/expire --> J[Update Reservasi = Dibatalkan]
    H -- masih pending --> K[Biarkan, cek lagi nanti]
    F -- Tidak --> L[Tampilkan Daftar Reservasi]
    I --> L
    J --> L
    K --> L
    L --> M{Admin pilih aksi}
    M -- Lihat Detail --> N[Buka edit_booking.php]
    N --> O[Ubah Status Reservasi]
    O --> P{Status baru?}
    P -- Dikonfirmasi --> Q[Reservasi Aktif\nKamar Terisi]
    P -- Dibatalkan --> R[Reservasi Batal\nKamar Tersedia]
    P -- Selesai --> S[Reservasi Selesai]
    Q --> T[Simpan ke DB]
    R --> T
    S --> T
    T --> U[Redirect dengan\npesan sukses]
    M -- Hapus --> V[Konfirmasi Hapus]
    V -- Ya --> W[DELETE dari DB]
    W --> U
    V -- Tidak --> L
    M -- Tambah Offline --> X[Form Booking Offline]
    X --> Y[Isi Data Manual]
    Y --> Z[Simpan Reservasi\nMetode = Tunai]
    Z --> U
    U --> AA([Selesai])
```

---

## 6. Flowchart Pembayaran (Admin)

```mermaid
flowchart TD
    A([Mulai]) --> B[Admin buka\nMenu Pembayaran]
    B --> C[Auto-Cek Midtrans\nuntuk transaksi pending]
    C --> D[Tampilkan Statistik:\nTotal Pendapatan,\nJumlah Lunas,\nJumlah Menunggu]
    D --> E[Tampilkan Tabel\nRiwayat Transaksi]
    E --> F{Admin pilih aksi}
    F -- Update Status --> G[Pilih status baru:\nMenunggu/Lunas/Gagal/Refund]
    G --> H[Simpan ke DB]
    H --> I{Status = Lunas?}
    I -- Ya --> J[Update Reservasi\nmenjadi Menunggu]
    I -- Tidak --> K[Hanya update\ntabel pembayaran]
    J --> L[Redirect dengan\npesan sukses]
    K --> L
    F -- Hapus --> M[Konfirmasi Hapus]
    M -- Ya --> N[DELETE dari DB]
    N --> L
    M -- Tidak --> E
    L --> O([Selesai])
```

---

## 7. Flowchart Beranda User (Status Kamar & Lihat Kamar)

```mermaid
flowchart TD
    A([Mulai]) --> B[Buka index.php]
    B --> C{User sudah login\ndan punya reservasi\ndikonfirmasi tapi\nbelum isi data penyewa?}
    C -- Ya --> D[Tampilkan Banner:\nLengkapi Profil Penyewa]
    C -- Tidak --> E[Tampilkan Beranda Normal]
    D --> E
    E --> F[Query semua kamar\nkelompok per lantai]
    F --> G[Cek status tiap kamar]
    G --> H{Status kamar?}
    H -- perbaikan --> I[Badge: Menunggu]
    H -- Ada reservasi aktif\ndikonfirmasi --> J[Badge: Terisi]
    H -- Tidak ada --> K[Badge: Tersedia]
    I --> L[Tampilkan Status Board\nper Lantai]
    J --> L
    K --> L
    L --> M[Tampilkan Kartu Kamar\ndengan foto & harga]
    M --> N{User klik?}
    N -- Lihat Detail --> O[Buka detail_kamar.php]
    N -- Pesan Sekarang --> P{Kamar tersedia?}
    P -- Ya --> Q[Buka Form Pemesanan]
    P -- Tidak --> R[Tombol Tidak Tersedia\ndisabled]
    O --> S([Selesai])
    Q --> S
    R --> S
```

---

## 8. Flowchart Pesanan Saya (User)

```mermaid
flowchart TD
    A([Mulai]) --> B{User sudah login?}
    B -- Tidak --> C[Redirect ke Login]
    C --> D([Selesai])
    B -- Ya --> E[Query semua reservasi\nmilik user]
    E --> F[Tampilkan Daftar Pesanan]
    F --> G{User pilih aksi}
    G -- Lihat Detail --> H[Tampilkan detail\nreservasi & pembayaran]
    G -- Lanjut Bayar --> I{Status = Menunggu\nPembayaran?}
    I -- Ya --> J[Buka bayar.php\ndengan Snap Token]
    J --> K[Tampilkan Popup\nMidtrans]
    K --> L{Hasil Pembayaran}
    L -- Sukses/Pending --> M[Verify & Update Status]
    M --> F
    L -- Gagal --> N[Alert Gagal]
    N --> F
    I -- Tidak --> O[Tampilkan Status\nSaat Ini]
    O --> F
    G -- Cetak Kwitansi --> P{Status = Dikonfirmasi?}
    P -- Ya --> Q[Buka cetak_kwitansi.php]
    Q --> R[Tampilkan Kwitansi\nsiap cetak]
    P -- Tidak --> S[Tidak bisa cetak]
    H --> D
    R --> D
```

---

## 9. Flowchart Kelola Kamar (Admin)

```mermaid
flowchart TD
    A([Mulai]) --> B[Admin buka\nMenu Kelola Kamar]
    B --> C[Tampilkan Daftar Kamar]
    C --> D{Admin pilih aksi}
    D -- Tambah Kamar --> E[Buka add_room.php]
    E --> F[Isi Form:\nNama, Lokasi, Lantai,\nHarga Bulan/Tahun,\nDeskripsi, Fasilitas,\nFoto Utama & Galeri]
    F --> G{Validasi & Upload Foto}
    G -- Gagal --> H[Tampilkan Error]
    H --> F
    G -- Berhasil --> I[Simpan ke DB\ntabel kamar]
    I --> J[Redirect dengan\npesan sukses]
    D -- Edit Kamar --> K[Buka edit_room.php]
    K --> L[Tampilkan Form\ndengan data existing]
    L --> M[Ubah data yang perlu]
    M --> N[Simpan perubahan ke DB]
    N --> J
    D -- Hapus Kamar --> O[Konfirmasi Hapus]
    O -- Ya --> P[DELETE dari DB\nHapus foto terkait]
    P --> J
    O -- Tidak --> C
    D -- Ubah Status --> Q[Toggle status:\ntersedia / perbaikan]
    Q --> R[Update DB]
    R --> J
    J --> C
    C --> S([Selesai])
```

---

## 10. Flowchart Logout

```mermaid
flowchart TD
    A([Mulai]) --> B[User/Admin klik Logout]
    B --> C[Buka logout.php]
    C --> D[session_start]
    D --> E[session_destroy]
    E --> F[Redirect ke\nHalaman Login]
    F --> G([Selesai])
```

---

## 11. Flowchart Profil Saya (User)

> Gaya flowchart mengikuti format standar dengan diamond (keputusan) dan kotak (proses), seperti pada contoh Flowchart Beranda Pembeli.

```mermaid
flowchart TD
    A([Profil Saya]) --> B[Tampil\nHalaman Profil]

    B --> C{Sudah\nLogin?}
    C -- Tidak --> D[Redirect ke\nLogin]
    C -- Ya --> E[Ambil Data\nPengguna dari DB]

    E --> F[Tampil Info Profil:\nNama, Username,\nNo HP, Foto, Role]

    F --> G{Edit\nProfil?}
    G -- Ya --> H[Tampil Form\nEdit Profil]
    H --> I[Isi Nama Lengkap\n& No HP]
    I --> J{Upload\nFoto Baru?}
    J -- Ya --> K[Validasi Format File\njpg/jpeg/png/gif/webp]
    K --> L{Format\nValid?}
    L -- Tidak --> M[Abaikan Foto\nGunakan Foto Lama]
    L -- Ya --> N[Upload & Simpan\nFoto ke Server]
    N --> O[Update DB:\nnama, no_hp, foto]
    M --> O
    J -- Tidak --> O
    O --> P[Tampil Pesan:\nProfil Berhasil\nDiperbarui]
    P --> B

    G -- Tidak --> Q{Ubah\nPassword?}
    Q -- Ya --> R[Tampil Form\nUbah Password]
    R --> S[Isi Password Lama,\nPassword Baru,\nKonfirmasi Baru]
    S --> T{Password Lama\nBenar?}
    T -- Tidak --> U[Tampil Error:\nPassword Lama\nTidak Benar]
    U --> R
    T -- Ya --> V{Panjang Password\nBaru ≥ 6?}
    V -- Tidak --> W[Tampil Error:\nMin 6 Karakter]
    W --> R
    V -- Ya --> X{Password Baru =\nKonfirmasi?}
    X -- Tidak --> Y[Tampil Error:\nPassword Tidak Cocok]
    Y --> R
    X -- Ya --> Z[Hash Password\nBCRYPT cost 12]
    Z --> AA[Update DB:\nkata_sandi baru]
    AA --> AB[Tampil Pesan:\nPassword Berhasil\nDiubah]
    AB --> B

    Q -- Tidak --> AC{Lihat Riwayat\nPesanan?}
    AC -- Ya --> AD[Redirect ke\nPesanan Saya]
    AC -- Tidak --> AE([Selesai])
```

---

## Ringkasan Alur Sistem

| No | Modul | Aktor | Deskripsi |
|----|-------|-------|-----------|
| 1 | Registrasi | User | Daftar akun baru dengan validasi username unik & password |
| 2 | Login | User / Admin | Autentikasi berbasis role dengan BCRYPT |
| 3 | Beranda | User | Lihat status kamar real-time & daftar kamar tersedia |
| 4 | Pemesanan | User | Isi form, hitung harga otomatis, bayar via Midtrans |
| 5 | Pembayaran | User | Proses via Midtrans Snap (Transfer, E-Wallet, QRIS) |
| 6 | Pesanan Saya | User | Pantau status reservasi & cetak kwitansi |
| 7 | Dashboard | Admin | Statistik kamar, penyewa aktif, pesanan pending, pendapatan |
| 8 | Kelola Reservasi | Admin | Konfirmasi, tolak, atau hapus reservasi |
| 9 | Kelola Pembayaran | Admin | Monitor & update status transaksi |
| 10 | Kelola Kamar | Admin | CRUD kamar beserta foto & fasilitas |
| 11 | Data Penyewa | Admin | Lihat & kelola data lengkap penyewa |
| 12 | Laporan | Admin | Export laporan pendapatan & reservasi |
| 13 | Logout | User / Admin | Hapus session & redirect ke login |
