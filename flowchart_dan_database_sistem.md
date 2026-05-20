# Analisis Sistem & Alur Kerja (Flowchart) - Kos Berkah Malika

Dokumen ini berisi analisis mendalam mengenai arsitektur sistem, struktur database, serta pemodelan alur kerja (flowchart) untuk aplikasi **Sistem Informasi Reservasi dan Pembayaran Kos Berkah Malika**.

---

## 1. Ikhtisar Sistem (System Overview)

Aplikasi **Kos Berkah Malika** adalah platform berbasis web untuk mempermudah pengelolaan kamar kos, proses reservasi oleh penyewa, pembayaran digital terintegrasi (Midtrans), dan pelaporan keuangan terotomatisasi bagi pengelola (Admin).

### Aktor Utama & Hak Akses
1. **Penyewa (Tenant)**:
   - Melihat daftar kamar kos yang tersedia (lantai, harga bulanan/tahunan, spesifikasi, galeri foto).
   - Melakukan pendaftaran (registrasi) dan masuk (login) menggunakan username unik tanpa memerlukan email.
   - Melakukan reservasi kamar secara online dengan memilih durasi sewa (Bulan/Tahun).
   - Membayar pesanan secara real-time via payment gateway **Midtrans** (QRIS, Transfer Bank, dll).
   - Melihat riwayat transaksi pembayaran dan mengunduh kuitansi digital.
   - Mengelola profil pribadi.

2. **Administrator (Admin)**:
   - Mengelola inventaris kamar kos (tambah, edit, hapus, update foto & spesifikasi).
   - Mengelola reservasi penyewa (konfirmasi manual atau pembatalan).
   - Menambahkan reservasi secara offline langsung dari panel admin.
   - Mengelola pembayaran masuk (melihat status Midtrans & melakukan verifikasi).
   - Mengakses Dashboard statistik interaktif (grafik pendapatan 12 bulan terakhir).
   - Mengakses Laporan Keuangan lengkap dengan fitur **Riwayat Transaksi** detail (dilengkapi kolom durasi & aksi).
   - Mengekspor laporan bulanan ke format **Excel** dan **PDF**.
   - Mengelola akun pengguna (CRUD Pengguna).

---

## 2. Analisis Struktur Database (Schema Database)

Sistem ini didukung oleh database relasional MySQL dengan 7 tabel utama yang saling berelasi:

```mermaid
erDiagram
    PENGGUNA ||--o{ RESERVASI : melakukan
    KAMAR ||--o{ RESERVASI : dipesan
    RESERVASI ||--|| PEMBAYARAN : memiliki
    RESERVASI ||--|| DATA_PENYEWA : diisi
    PENGGUNA ||--o{ DATA_PENYEWA : terdaftar
    KAMAR ||--o{ DATA_PENYEWA : menempati
    PENGGUNA ||--o{ NOTIFIKASI : menerima
```

### Detail Relasi & Integritas Data
1. **`pengguna`**: Menyimpan data login & profil. Fitur email telah sepenuhnya dihapus. Identifikasi bergantung pada kolom `username` (Unique) dan `nama_lengkap`.
2. **`kamar`**: Menyimpan detail harga (`harga_per_bulan` & `harga_per_tahun`), spesifikasi fisik, lantai, status (`tersedia`, `terisi`, `perbaikan`), dan galeri multi-foto.
3. **`reservasi`**: Menyimpan transaksi sewa. Menghubungkan penyewa dengan kamar terpilih, menyimpan tanggal masuk/keluar, total harga, durasi (`durasi_sewa`), dan status (`Menunggu Pembayaran`, `Menunggu`, `Dikonfirmasi`, `Dibatalkan`, `Selesai`).
4. **`pembayaran`**: Menyimpan data detail pembayaran online yang terhubung ke Midtrans via `token_snap` dan melacak `status_transaksi` (`pending`, `settlement`, `capture`, `expire`, `cancel`, `deny`).
5. **`data_penyewa`**: Menyimpan informasi penghuni aktif yang mencakup NIK/KTP, pekerjaan, dan masa sewa aktif.
6. **`notifikasi`**: Sistem pesan log untuk memberi tahu pengguna tentang status reservasi & pembayaran.
7. **`laporan_keuangan`**: Entri rekap bulanan pendapatan kotor, jumlah kamar terisi/kosong untuk dokumentasi internal.

---

## 3. Alur Kerja Sistem (Flowcharts)

Berikut adalah pemodelan alur kerja sistem menggunakan diagram alir (flowchart) terperinci untuk setiap modul utama.

### A. Alur Pendaftaran & Autentikasi (Registrasi & Login)
Alur ini memastikan proses autentikasi aman tanpa menggunakan email, dengan memvalidasi keunikan username di database.

```mermaid
flowchart TD
    Start([Mulai]) --> Guest[Pengunjung buka Halaman Utama]
    Guest --> Action{Pilih Aksi}
    
    Action -- Registrasi --> RegForm[Isi Formulir: Username, Nama Lengkap, No HP, Sandi]
    RegForm --> ValReg{Validasi Input}
    ValReg -- Kosong/Sandi Tidak Cocok --> AlertReg[Tampilkan Error] --> RegForm
    ValReg -- Sesuai --> ChkUser{Cek Username di DB pengguna}
    ChkUser -- Sudah Ada --> AlertUser[Error: Username telah digunakan] --> RegForm
    ChkUser -- Belum Ada --> Encrypt[Enkripsi Password dengan Password_Hash]
    Encrypt --> InsUser[Simpan ke DB pengguna sebagai Peran 'penyewa']
    InsUser --> SuccessReg[Registrasi Sukses -> Arahkan ke Login] --> LoginForm
    
    Action -- Login --> LoginForm[Masukkan Username & Sandi]
    LoginForm --> QueryUser[Cari Username di DB pengguna]
    QueryUser --> UserExist{Apakah User Ditemukan?}
    UserExist -- Tidak --> ErrorLogin[Tampilkan Error: Akun tidak ditemukan] --> LoginForm
    UserExist -- Ya --> VerifyPass{Verifikasi Sandi via Password_Verify}
    VerifyPass -- Salah --> ErrorPass[Tampilkan Error: Sandi salah] --> LoginForm
    VerifyPass -- Benar --> SetSession[Set Session: id_pengguna, username, nama_lengkap, role]
    
    SetSession --> RoleCheck{Cek Peran}
    RoleCheck -- admin --> AdminDash[Arahkan ke Dashboard Admin] --> End([Selesai])
    RoleCheck -- penyewa --> UserHome[Arahkan ke Halaman Utama / Status Kamar] --> End
```

---

### B. Alur Pemesanan Kamar & Pembayaran Online (Midtrans Snap Gateway)
Proses penanganan pemesanan online oleh penyewa yang terintegrasi secara real-time dengan payment gateway Midtrans.

```mermaid
flowchart TD
    Start([Mulai]) --> LoginCheck{Penyewa Login?}
    LoginCheck -- Tidak --> LoginForm[Arahkan ke Halaman Login]
    LoginCheck -- Ya --> SelectRoom[Pilih Kamar di Halaman Utama]
    
    SelectRoom --> RoomStatus{Status Kamar 'tersedia'?}
    RoomStatus -- Tidak --> AlertRoom[Tampilkan: Kamar sudah terisi/perbaikan] --> SelectRoom
    RoomStatus -- Ya --> BookingForm[Isi Form: Tanggal Masuk, Durasi Sewa, Jumlah Tamu, Catatan]
    
    BookingForm --> CalcPrice[Hitung Total Harga Berdasarkan Durasi & Harga Kamar]
    CalcPrice --> SubmitBooking[Kirim Formulir Reservasi]
    
    SubmitBooking --> InsertRes[Insert ke DB: reservasi \n Status: 'Menunggu Pembayaran']
    InsertRes --> RequestMidtrans[Kirim Payload Transaksi ke API Midtrans Snap]
    RequestMidtrans --> MidtransToken{API Response: Sukses?}
    
    MidtransToken -- Tidak --> ErrorToken[Tampilkan Error Koneksi Pembayaran] --> End([Selesai])
    MidtransToken -- Ya --> SavePayment[Simpan ke DB: pembayaran \n Status: 'pending', snap_token, order_id]
    
    SavePayment --> ShowSnapWidget[Tampilkan Widget Midtrans Snap di Layar Penyewa]
    ShowSnapWidget --> TenantPay{Penyewa Membayar?}
    
    TenantPay -- Batalkan/Keluar --> ExpireTimer[Status tetap pending di DB, Kamar aman] --> End
    TenantPay -- Ya / Selesai Bayar --> ProcessNotification[Midtrans Mengirim Notifikasi ke Endpoint Webhook/IPN]
    
    ProcessNotification --> VerifyNotif{Verifikasi Signature Key Midtrans}
    VerifyNotif -- Tidak Valid --> IgnoreNotif[Abaikan Request / Log Error] --> End
    VerifyNotif -- Valid --> UpdatePaymentStatus[Update DB pembayaran: status_transaksi = 'settlement' / 'lunas']
    
    UpdatePaymentStatus --> UpdateResStatus[Update DB reservasi: status_reservasi = 'Dikonfirmasi']
    UpdateResStatus --> UpdateRoom[Update DB kamar: status_kamar = 'terisi']
    UpdateRoom --> CreateTenant[Otomatis Insert ke DB: data_penyewa \n Status Huni: 'aktif']
    CreateTenant --> CreateNotif[Kirim Notifikasi Keberhasilan ke Akun Penyewa]
    CreateNotif --> PrintReceipt[Penyewa dapat Mencetak Kwitansi Pembayaran] --> End
```

---

### C. Alur Laporan Keuangan & Riwayat Transaksi (Sisi Admin)
Alur administrasi pengelolaan laporan keuangan, rekap penjualan, penentuan durasi sewa secara otomatis, dan opsi ekspor dokumen.

```mermaid
flowchart TD
    Start([Mulai]) --> AdminLogin{Admin Terautentikasi?}
    AdminLogin -- Tidak --> LoginForm[Arahkan ke Halaman Login Admin]
    AdminLogin -- Ya --> OpenReport[Buka Halaman Laporan Keuangan]
    
    OpenReport --> Filter[Pilih Bulan & Tahun melalui Form Filter]
    Filter --> QueryLaporan[Ambil Data Laporan Keuangan Bulanan & Statistik Kamar]
    QueryLaporan --> QueryHist[Ambil Riwayat Transaksi Pembayaran pada Bulan Terpilih]
    
    QueryHist --> Loop[Loop baris data pembayaran]
    Loop --> CheckDuration{Apakah data durasi_sewa ada di reservasi?}
    
    CheckDuration -- Ada --> ShowDuration[Tampilkan durasi_sewa asli]
    CheckDuration -- Kosong --> CalcFallback[Hitung Durasi berdasarkan Jumlah Bayar]
    CalcFallback --> Formula{Jumlah Bayar % 7.000.000 == 0?}
    Formula -- Ya --> DivYear[Set Durasi = Jumlah Bayar / 7.000.000 + ' Tahun']
    Formula -- Tidak --> DivMonth[Set Durasi = Jumlah Bayar / 700.000 + ' Bulan']
    
    ShowDuration & DivYear & DivMonth --> ShowRow[Tampilkan Baris: Pemesan, Kamar, Durasi, Jumlah, Metode, Status Lunas, Tanggal, Tombol Detail]
    
    ShowRow --> ExportOptions{Pilih Ekspor Laporan?}
    ExportOptions -- Excel --> GenExcel[Buat Spreadsheet data transaksi & statistik bulanan] --> DownExcel[Unduh Berkas .xls/.xlsx] --> End([Selesai])
    ExportOptions -- PDF --> GenPDF[Render Halaman Laporan & Tabel Transaksi Terformat Rapi] --> PrintPDF[Cetak / Simpan PDF] --> End
    ExportOptions -- Tidak --> End
```

---

## 4. Keunggulan Desain Arsitektur Sistem saat Ini

Sistem ini telah diperbaiki dan dimodifikasi dengan standar industri modern yang andal:
1. **Pemisahan Peran yang Jelas**: Autentikasi ketat memisahkan antarmuka administrasi dan portal penyewa secara aman.
2. **Fleksibilitas Durasi**: Mampu mengalkulasi sewa bulanan dan tahunan secara dinamis dan memiliki kecerdasan buatan (*fallback logic*) untuk mendeteksi durasi sewa berdasarkan harga yang terbayar di laporan.
3. **Pembayaran yang Handal**: Menggunakan **Midtrans Snap SDK** di frontend dan validasi backend yang aman menggunakan `signature_key` untuk memblokir manipulasi status pembayaran.
4. **Peningkatan Antarmuka Premium**: Tampilan tabel riwayat transaksi pada panel laporan telah dibersihkan (menghapus kolom sekunder seperti kode order agar ramah perangkat mobile), ditambahkan kolom durasi langsung, disinkronkan warnanya, serta diposisikan secara presisi agar bebas dari tumpang tidal tata letak.
