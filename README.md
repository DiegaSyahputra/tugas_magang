# SkillSet Academy Platform - Automated Testing & Integration Guide

Aplikasi sistem manajemen pembelajaran dan presensi terpadu (**SkillSet Academy**) berbasis Laravel 12. Repositori ini telah dilengkapi dengan rangkaian pengujian otomatis (*Unit*, *Feature*, dan *Integration Test*) yang komprehensif berstandar industri dengan cakupan keamanan (*security hardening*), integritas data presensi, dan skenario *End-to-End*.

---

## 🚀 Ringkasan Hasil Pengujian

- **Total Test**: **126 test cases**
- **Total Assertions**: **546 assertions**
- **Status**: **100% Passed (0 Failure, 0 Warning)**
- **Waktu Eksekusi**: ~48 detik

---

## 🛠️ Prasyarat & Konfigurasi Pengujian

Lingkungan pengujian menggunakan database MySQL terisolasi untuk menghindari modifikasi data pada basis data pengembangan (`project_magang_attendance`).

### 1. Buat Database Pengujian
Pastikan MySQL di Laragon / Localhost sedang aktif, kemudian buat basis data khusus pengujian:
```sql
CREATE DATABASE IF NOT EXISTS project_magang_attendance_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Konfigurasi Environment Testing
Pengujian membaca konfigurasi dari file `.env.testing` dan `phpunit.xml`:
```ini
APP_ENV=testing
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_DATABASE=project_magang_attendance_test
DB_USERNAME=root
DB_PASSWORD=
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

---

## 🧪 Menjalankan Rangkaian Pengujian

Jalankan perintah berikut pada terminal root proyek:

### Menjalankan Seluruh Pengujian (Unit + Feature + Integration)
```bash
php artisan test
```

### Menjalankan Berdasarkan Test Suite
```bash
# Menjalankan seluruh Integration Tests
php artisan test tests/Integration

# Menjalankan seluruh Feature Tests
php artisan test tests/Feature

# Menjalankan seluruh Unit Tests
php artisan test tests/Unit
```

### Menjalankan Berkas Pengujian Tertentu
```bash
# Matriks Otorisasi Peran (Guest, Student, Teacher, Admin)
php artisan test tests/Integration/AuthorizationMatrixIntegrationTest.php

# Pengujian End-to-End (4 Alur Siklus Hidup Lengkap)
php artisan test tests/Integration/EndToEndFlowTest.php

# Pengujian Celah Keamanan & Kasus Ekstrem
php artisan test tests/Integration/SecurityAndEdgeCaseIntegrationTest.php

# Pengujian Presensi Mandiri (Self Check-In, Idempotensi, Batas Waktu)
php artisan test tests/Integration/SelfCheckInIntegrationTest.php

# Pengujian Batas Ambang & Cakupan Modul Lengkap
php artisan test tests/Integration/ModuleCoverageGapsTest.php
```

---

## 📂 Struktur Rangkaian Pengujian

```
tests/
├── Unit/
│   └── ExampleTest.php
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php        # Otentikasi login, logout, invalid credentials
│   │   └── RegistrationTest.php          # Registrasi siswa, guru, validasi peran, atomic rollback
│   └── ExampleTest.php
└── Integration/
    ├── AuthorizationMatrixIntegrationTest.php  # Matriks otorisasi seluruh rute mutasi data (POST, PUT, DELETE)
    ├── CourseEnrollmentIntegrationTest.php     # Pendaftaran dan pelepasan siswa ke kelas
    ├── CourseIntegrationTest.php               # CRUD course dan validasi kode unik
    ├── CourseSessionAndAttendanceIntegrationTest.php # Sesi kelas, presensi manual guru, kalkulasi otomatis
    ├── EndToEndFlowTest.php                    # 4 Alur E2E (Akademik, Live Class, Keuangan, Perpustakaan)
    ├── LibraryIntegrationTest.php              # Upload & download modul materi digital
    ├── LiveClassIntegrationTest.php            # Jadwal meeting, video call check-in/out, durasi, realtime chat
    ├── ModuleCoverageGapsTest.php              # Ambang regular 69% vs 70%, IDOR payment, cascading, rendering views
    ├── PaymentIntegrationTest.php              # Tagihan, unggah bukti bayar, unduh invoice resmi
    ├── ReportIntegrationTest.php               # Ekspor laporan akademik (PDF/HTML) & presensi (CSV/Excel)
    ├── RoleAuthorizationIntegrationTest.php    # Isolasi hak akses hak istimewa tiap peran
    ├── SecurityAndEdgeCaseIntegrationTest.php  # Verifikasi 11 celah keamanan dan regresi
    ├── SelfCheckInIntegrationTest.php          # Self check-in toleransi 15 vs 16 menit, anti-downgrade status
    ├── StudentIntegrationTest.php              # CRUD Master Siswa dan sinkronisasi akun User
    └── TeacherIntegrationTest.php              # CRUD Master Guru dan sinkronisasi akun User
```

---

## 🛡️ Catatan Perbaikan Keamanan & Integritas Data

1. **Sinkronisasi Timezone `Asia/Jakarta` (WIB)**:
   - Jam sesi diinput dalam waktu lokal Indonesia (WIB). Mengatur `APP_TIMEZONE=Asia/Jakarta` memastikan perbandingan waktu check-in, tenggat keterlambatan, dan validasi tanggal sesi dihitung akurat.
2. **Proteksi IDOR (*Insecure Direct Object References*)**:
   - `PaymentController::uploadProof` dan `exportInvoice` memvalidasi kepemilikan pembayaran: siswa hanya dapat mengunggah bukti dan mengunduh invoice miliknya sendiri.
   - `PaymentController::store` mengunci `student_id` sesuai akun siswa yang sedang login.
3. **Pemberantasan *Stored XSS***:
   - Seluruh variabel dinamis pada pembuatan berkas invoice HTML (`PaymentController`) dan laporan PDF eksekutif (`ReportController`) telah dibungkus dengan `e()` / `htmlspecialchars()`.
4. **Idempotensi & Proteksi Status Presensi**:
   - Menekan tombol presensi mandiri berulang kali tidak mendowngrade status yang sudah `present` menjadi `late`.
   - Siswa yang bergabung di video call Live Class secara otomatis mengangkat status `absent` menjadi kehadiran aktif.
5. **Integritas Transaksional (*Atomic Database Transactions*)**:
   - Penghapusan data master siswa/guru otomatis menghapus akun `User` terkait di dalam `DB::transaction` untuk mencegah akun yatim (*orphan users*).
   - Penomoran `student_code` (`STU-xxxxxx`) menghitung angka tertinggi berbasis data yang ada (`max`) untuk menjamin keunikan kode bahkan setelah penghapusan siswa.
