# Diagram Alir (Flowchart) Final yang Benar - Kos Berkah Malika

Dokumen ini menyajikan berkas gambar flowchart resmi yang sudah diperbaiki secara logika dan disesuaikan secara presisi dengan tabel simbol standar pada **Gambar 4**. 

Seluruh berkas gambar di bawah ini telah disalin ke dalam direktori kerja Anda di folder **`flowchart_hasil/`** untuk kemudahan akses dan dokumentasi laporan Anda.

---

## 1. Flowchart Register (Pendaftaran)

Flowchart ini menggunakan simbol **Jajaran Genjang** untuk output tampilan formulir dan input data registrasi user, serta **Persegi Panjang** untuk proses penyimpanan database.

![Flowchart Register](/home/deo/.gemini/antigravity/brain/f2fd0036-7302-447f-aedd-249b91ee50d9/flowchart_register_1779165347312.png)

---

## 2. Flowchart Login User

Flowchart ini menggunakan simbol **Jajaran Genjang** untuk semua aksi input/output (menampilkan halaman login, input kredensial, pesan kesalahan, dan mengarahkan ke beranda) serta **Persegi Panjang** untuk proses verifikasi login.

![Flowchart Login](/home/deo/.gemini/antigravity/brain/f2fd0036-7302-447f-aedd-249b91ee50d9/flowchart_login_1779165398374.png)

---

## 3. Flowchart Beranda User

Flowchart ini mencerminkan revisi logika terbaru Anda (**Lihat Detail -> Tampil detail kamar**) dan mengganti semua simbol *Off Page Connector* serta *Proses* visual menjadi simbol **Jajaran Genjang** (Output Halaman/Data) yang benar secara akademis.

![Flowchart Beranda User](/home/deo/.gemini/antigravity/brain/f2fd0036-7302-447f-aedd-249b91ee50d9/flowchart_beranda_user_1779165420170.png)

---

## 4. Flowchart Status Kamar

Flowchart ini memetakan logika kueri status kamar secara real-time dari database, percabangan penyeleksian status (`Pending` jika perbaikan, `Occupied` jika terisi dengan hitungan sisa hari sewa, dan `Available` jika tersedia), serta aksi untuk melihat detail atau melakukan pemesanan langsung.

![Flowchart Status Kamar](/home/deo/.gemini/antigravity/brain/f2fd0036-7302-447f-aedd-249b91ee50d9/flowchart_status_kamar_1779168041670.png)

---

## 5. Flowchart Pesanan Saya (Riwayat Transaksi)

Flowchart ini memodelkan proses verifikasi transaksi otomatis (mengecek API Midtrans untuk status pembayaran *pending*), menampilkan riwayat reservasi, serta logika interaksi terhadap kartu pesanan berdasarkan statusnya (seperti tombol Bayar, Cetak Kwitansi, atau Detail Kamar).

![Flowchart Pesanan Saya](/home/deo/.gemini/antigravity/brain/f2fd0036-7302-447f-aedd-249b91ee50d9/flowchart_pesanan_saya_1779170613630.png)

---

## 6. Flowchart Data Penyewa (Form Biodata)

Flowchart ini memodelkan proses verifikasi sebelum pengisian data (penyewa harus memiliki reservasi yang 'Dikonfirmasi' terlebih dahulu). Jika valid, sistem akan mengecek apakah data penghuni sudah ada untuk menentukan apakah akan melakukan proses *Insert* (Data Baru) atau *Update* (Perbarui Data). 

![Flowchart Data Penyewa](/home/deo/.gemini/antigravity/brain/f2fd0036-7302-447f-aedd-249b91ee50d9/flowchart_data_penyewa_1779172408792.png)
