# Flowchart Profil Saya - Kos Berkah Malika

## Flowchart Halaman Profil User

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

## Keterangan Alur

| Simbol | Arti |
|--------|------|
| `([...])` | Terminal (Mulai / Selesai) |
| `[...]` | Proses / Aksi |
| `{...}` | Keputusan (Decision) |

## Penjelasan Tiap Alur

### 1. Cek Login
Saat user membuka halaman `profil.php`, sistem langsung mengecek session. Jika belum login, langsung diarahkan ke halaman login.

### 2. Tampil Data Profil
Sistem mengambil data dari tabel `pengguna` berdasarkan `id_pengguna` dari session, lalu menampilkan:
- Foto profil (atau avatar otomatis jika belum ada)
- Nama lengkap & username
- Nomor HP
- Role (penyewa)
- Tanggal bergabung

### 3. Edit Profil
User dapat mengubah:
- **Nama Lengkap** — wajib diisi
- **Nomor HP** — opsional
- **Foto Profil** — upload file gambar (jpg, jpeg, png, gif, webp)

Jika foto baru diupload, sistem memvalidasi format file. Jika valid, file disimpan ke `public/uploads/profil/` dengan nama unik `profil_{id}_{timestamp}.ext`.

### 4. Ubah Password
Validasi dilakukan secara bertahap:
1. Password lama harus cocok dengan hash di DB (BCRYPT verify)
2. Password baru minimal 6 karakter
3. Konfirmasi password harus sama dengan password baru

Jika semua valid, password baru di-hash dengan BCRYPT cost 12 dan disimpan ke DB.

### 5. Riwayat Pesanan
Tombol shortcut untuk langsung menuju halaman `pesanan_saya.php`.
