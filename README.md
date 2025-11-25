# Aplikasi Manajemen Surat Menyurat

Sistem manajemen surat digital berbasis PHP untuk mencatat, mengarsipkan, dan mendistribusikan surat masuk maupun surat keluar di lingkungan organisasi pemerintahan maupun pendidikan. Aplikasi ini menyatukan pencatatan surat, disposisi, referensi, hingga backup database dalam satu dashboard Materialize yang responsif.

## Fitur Utama

- **Manajemen Surat Masuk & Keluar** – Form CRUD lengkap dengan unggah lampiran (PDF/JPG) dan cetak lembar disposisi.
- **Disposisi & Tracking** – Catat tindak lanjut surat, lampirkan catatan/disposisi, serta monitor status tindak lanjut.
- **Agenda & Galeri** – Buku agenda digital dan galeri file untuk memudahkan pencarian historis surat.
- **Klasifikasi & Referensi** – Pengelolaan kode klasifikasi dan metadata surat agar konsisten.
- **Pengaturan Instansi** – Sesuaikan identitas instansi (nama, alamat, logo) yang tampil pada dashboard dan cetakan.
- **User & Role** – Akses berbasis peran (Admin Utama, Administrator, Petugas Disposisi) dengan kontrol menu berbeda.
- **Backup & Restore** – Utilitas bawaan untuk membuat cadangan basis data atau memulihkan dari file SQL (`backup.php`, `restore.php`).

## Teknologi

- PHP 7.4+ dengan ekstensi `mysqli`
- MySQL 5.7 / MariaDB 10+
- MaterializeCSS, jQuery 2.x, Google Material Icons
- Laragon/XAMPP atau web server serupa dengan dukungan PHP-MySQL

## Struktur Proyek Singkat

```
d:\laragon\www\correspondence-management
├── asset/                # CSS, JS, font, dan aset gambar UI
├── include/              # Konfigurasi & helper (config.php, functions.php, head.php, menu.php)
├── database/             # Dump SQL (correspondence-management.sql)
├── upload/               # File yang diunggah (logo, surat masuk, surat keluar)
├── *.php                 # Modul aplikasi (admin, transaksi, agenda, galeri, pengaturan, dsb.)
└── LICENSE.txt / README.md
```

## Persyaratan

1. PHP 7.4 atau lebih baru dengan ekstensi `mysqli` aktif.
2. MySQL/MariaDB dengan akses untuk membuat database baru.
3. Composer tidak diperlukan.
4. Web server lokal (Laragon, XAMPP, WAMP, LAMP, dsb.).

## Langkah Instalasi

1. **Clone / Salin** repositori ini ke direktori web server Anda (`www` atau `htdocs`):
   ```bash
   git clone https://github.com/bintangnugrahaa/correspondence-management.git .
   ```
2. **Buat database** bernama `correspondence-management` (atau nama lain sesuai kebutuhan).
3. **Import skema dan data awal** dari `database/correspondence-management.sql`.
4. **Konfigurasi koneksi** di `include/config.php`:
   - Secara default aplikasi memilih environment berdasarkan `APP_ENV` atau hostname.
   - Sesuaikan kredensial database di array `getDatabaseConfig()` atau gunakan environment variable (`DB_HOST`, `DB_USERNAME`, dll.).
5. **Set permission** direktori `upload/` agar dapat menampung file lampiran.
6. **Jalankan aplikasi** melalui `http://localhost/correspondence-management` dan login.

### Kredensial Default

Data contoh di dump SQL menyertakan akun:

- Username: `bintangnugraha`
- Password: `admin`

Segera ganti password setelah login pertama. Hash yang digunakan masih MD5 sehingga disarankan memigrasikan ke `password_hash()` sebelum produksi.

## Penggunaan

- **Dashboard** (`admin.php`): menampilkan ringkasan jumlah surat, disposisi, klasifikasi, dan pengguna (khusus admin).
- **Transaksi Surat** (`?page=tsm` / `?page=tsk`): tambah, ubah, hapus, cetak, dan unggah lampiran surat masuk/keluar.
- **Disposisi** (`disposisi.php` dan turunannya): kelola tindak lanjut surat masuk.
- **Agenda** (`agenda_surat_masuk.php`, `agenda_surat_keluar.php`): filter berdasarkan tanggal & ekspor PDF.
- **Galeri File** (`galeri_sm.php`, `galeri_sk.php`): pratinjau arsip lampiran.
- **Referensi & Klasifikasi** (`referensi.php`, `tambah_klasifikasi.php`, dsb.): atur metadata surat.
- **Pengaturan** (`pengaturan.php`): ubah profil instansi, kelola user, backup/restore database.

## Backup & Restore

- Menu **Pengaturan → Backup Database** memanggil `backup.php` untuk menghasilkan file `.sql` pada folder `backup/`.
- Menu **Pengaturan → Restore Database** memanfaatkan `restore.php` untuk mengunggah kembali file cadangan.
- Jalankan proses ini setelah memastikan file backup tersimpan aman di luar server produksi.

## File Upload

- Lampiran surat masuk tersimpan di `upload/surat_masuk/`.
- Lampiran surat keluar tersimpan di `upload/surat_keluar/`.
- Logo/identitas instansi berada di `upload/`.

Pastikan ukuran maksimum upload di `php.ini` (mis. `upload_max_filesize`, `post_max_size`) cukup besar untuk dokumen resmi.

## Roadmap & Saran Pengembangan

- Ganti autentikasi MD5 ke `password_hash()` / `password_verify()`.
- Tambahkan CSRF token pada seluruh form transaksi.
- Migrasikan konfigurasi sensitif ke `.env` atau secrets manager.
- Buat modul notifikasi (email/WhatsApp) untuk disposisi.
- Siapkan pipeline CI linting & backup otomatis.

## Troubleshooting

- **Blank page / pesan koneksi**: cek kredensial DB di `include/config.php` dan pastikan ekstensi `mysqli` aktif.
- **Upload gagal**: pastikan folder `upload/` dapat ditulis (permission 755/775) dan batas size file mencukupi.
- **Login loop**: hapus cookie/session browser atau pastikan tabel `tbl_user` memiliki data valid.

## Lisensi

Proyek ini dirilis dengan lisensi [MIT](LICENSE.txt). Dapat digunakan, dimodifikasi, dan didistribusikan secara bebas selama mencantumkan atribusi.

## Kontribusi

1. Fork repository dan buat branch fitur (`feature/nama-fitur`).
2. Lakukan perubahan beserta deskripsi jelas.
3. Ajukan pull request dengan detail perubahan dan langkah uji.

---

Developed originally by Muhammad Bintang Nugraha (2019) dan disesuaikan kembali untuk kebutuhan instansi modern.
