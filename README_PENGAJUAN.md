# PANDUAN IMPLEMENTASI SISTEM WORKFLOW PENGAJUAN

## File-File yang Dibuat

### 1. **pengajuan_staf.php** (NEW)
Halaman untuk staf membuat dan mengelola pengajuan mereka.

**Fitur:**
- Form input: Tipe pengajuan, nama, tanggal lahir, TMT, unit, keterangan
- List pengajuan dengan status
- Tombol hapus (hanya saat PENDING)
- Sidebar navigasi

**Status yang ditampilkan:**
- Menunggu Approve (PENDING)
- Disetujui Kanit/Kabid (APPROVED_KANIT)
- Sedang Diproses (PROCESSING)
- Selesai (COMPLETED)
- Ditolak (REJECTED - dengan alasan)

**Akses:** Hanya role STAF

---

### 2. **persetujuan_kanit.php** (NEW)
Halaman untuk kanit/kabid memberikan persetujuan pengajuan.

**Fitur:**
- Tab 1: Daftar pengajuan PENDING dengan:
  - Tombol Detail (modal dengan info lengkap)
  - Tombol Setujui (dengan catatan optional)
  - Tombol Tolak (dengan alasan mandatory)
- Tab 2: Riwayat persetujuan 10 terakhir
- Sidebar navigasi

**Akses:** Hanya role KANIT/KABID

---

### 3. **pengajuan_admin.php** (NEW)
Halaman untuk admin memproses dan melihat semua pengajuan.

**Fitur:**
- Statistik: Counter pending, disetujui, diproses, selesai
- Tab 1: Siap Diproses (APPROVED_KANIT)
  - Tombol Mulai Proses (status → PROCESSING)
- Tab 2: Sedang Diproses (PROCESSING)
  - Tombol Selesaikan (status → COMPLETED)
  - Modal catatan admin (mandatory)
- Tab 3: Semua Riwayat
  - DataTable lengkap semua pengajuan
  - Sorting, search, pagination
- Sidebar navigasi

**Akses:** Hanya role ADMIN

---

### 4. **pengajuan_database.sql** (NEW)
Script SQL untuk membuat tabel `pengajuan` dengan struktur lengkap.

**Kolom utama:**
- Identitas: id, created_by, created_at
- Data pengajuan: tipe, nama, tanggal_lahir, tanggal_tmt, unit, keterangan
- Tracking approval kanit: approved_by_kanit, approved_date_kanit, catatan_kanit
- Tracking rejection: rejected_by_kanit, rejected_date_kanit, rejected_reason
- Tracking processing admin: processed_by, processed_date, completed_by, completed_date, catatan_admin
- Status: pending, approved_kanit, processing, completed, rejected

---

### 5. **DOKUMENTASI_PENGAJUAN.md** (NEW)
Dokumentasi lengkap sistem workflow dengan diagram, testing, dan pengembangan.

---

## LANGKAH INSTALASI

### Step 1: Buat Tabel Database
```sql
1. Buka phpMyAdmin
2. Buka database: pegawai_mou_sk
3. Buka tab SQL
4. Copy isi dari file: pengajuan_database.sql
5. Klik Execute
6. Verifikasi tabel "pengajuan" sudah dibuat
```

### Step 2: Verifikasi Struktur Users
Pastikan tabel `users` sudah memiliki kolom:
- id
- nama
- role (staf, kanit, kabid, admin)

Jika belum:
```sql
ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'staf';
UPDATE users SET role='staf' WHERE role IS NULL;
```

### Step 3: Upload File
Pastikan file-file ini sudah ada di folder: `/xampp/htdocs/sistem-pegawai/`
- pengajuan_staf.php
- persetujuan_kanit.php
- pengajuan_admin.php
- pengajuan_database.sql
- DOKUMENTASI_PENGAJUAN.md

### Step 4: Verifikasi Session & Database Connection
Pastikan file `config/database.php` sudah terhubung dengan benar.

---

## TESTING WORKFLOW

### Test User Setup
Buat beberapa test user:

```sql
-- Test Staf
INSERT INTO users (nama, role, email, password) 
VALUES ('Budi Santoso', 'staf', 'budi@test.com', MD5('password'));

-- Test Kanit/Kabid
INSERT INTO users (nama, role, email, password) 
VALUES ('Drs. Hermawan', 'kanit', 'hermawan@test.com', MD5('password'));

INSERT INTO users (nama, role, email, password) 
VALUES ('Dra. Siti', 'kabid', 'siti@test.com', MD5('password'));

-- Test Admin (sudah ada)
-- UPDATE users SET role='admin' WHERE id=1;
```

### Full Test Scenario
1. **Login sebagai STAF**
   - Buka: `http://localhost/sistem-pegawai/pengajuan_staf.php`
   - Isi form dan klik "Kirim Pengajuan"
   - Catat ID pengajuan
   - Status harus "Menunggu Approve"

2. **Login sebagai KANIT/KABID**
   - Buka: `http://localhost/sistem-pegawai/persetujuan_kanit.php`
   - Lihat pengajuan dari STAF di Tab 1
   - Klik "Detail" untuk review
   - Pilih "Setujui" dengan catatan

3. **Login sebagai ADMIN**
   - Buka: `http://localhost/sistem-pegawai/pengajuan_admin.php`
   - Lihat pengajuan di Tab 1 "Siap Diproses"
   - Klik "Mulai Proses"
   - Lihat di Tab 2 "Sedang Diproses"
   - Klik "Selesaikan" dengan catatan admin
   - Lihat status berubah di Tab 3 "Semua Riwayat"

4. **Login kembali sebagai STAF**
   - Buka: `http://localhost/sistem-pegawai/pengajuan_staf.php`
   - Verifikasi status berubah menjadi "Selesai"

---

## INTEGRASI DENGAN MENU SIDEBAR

Untuk menambahkan menu "Pengajuan" di sidebar existing, update file-file:

### File: index.php
```php
<!-- Tambahkan sebelum "Ajuan MoU" -->
<?php if ($_SESSION['role'] == 'staf' || $_SESSION['role'] == 'admin'): ?>
<a href="pengajuan_staf.php" class="text-white" style="...">
    <i class="bi bi-file-earmark-text"></i> Pengajuan
</a>
<?php elseif ($_SESSION['role'] == 'kanit' || $_SESSION['role'] == 'kabid'): ?>
<a href="persetujuan_kanit.php" class="text-white" style="...">
    <i class="bi bi-check-circle"></i> Persetujuan Pengajuan
</a>
<?php endif; ?>
```

### File: ajuan_mou.php, ajuan_sk.php, approval.php
Tambahkan menu yang sama seperti di index.php

---

## FITUR BONUS

### Menambahkan Email Notification (Optional)
```php
// Tambahkan di pengajuan_staf.php setelah INSERT
$email_kanit = "kanit@example.com";
mail($email_kanit, "Pengajuan Baru", "Ada pengajuan baru dari " . $row['nama']);
```

### Menambahkan Attachment
Extend form dengan:
```html
<input type="file" name="dokumen" accept=".pdf,.doc,.docx">
```

### Menambahkan Export to Excel
```php
// Di pengajuan_admin.php Tab 3
echo '<a href="export_pengajuan.php" class="btn btn-primary">
    <i class="bi bi-download"></i> Export Excel
</a>';
```

---

## TROUBLESHOOTING

### Masalah: Data tidak muncul di Tab
**Solusi:** Check status di database:
```sql
SELECT * FROM pengajuan WHERE id=XXX;
```

### Masalah: Button tidak bekerja
**Solusi:** Pastikan form `method="POST"` dan `name` attribute sesuai

### Masalah: Redirect error
**Solusi:** Verifikasi session role di awal file:
```php
echo "Current role: " . $_SESSION['role'];
```

### Masalah: Tabel tidak ditemukan
**Solusi:** Jalankan SQL create table:
```sql
SHOW TABLES LIKE 'pengajuan';
```

---

## CHECKLIST IMPLEMENTASI

- [ ] Database table `pengajuan` sudah dibuat
- [ ] File pengajuan_staf.php sudah upload
- [ ] File persetujuan_kanit.php sudah upload
- [ ] File pengajuan_admin.php sudah upload
- [ ] Test user sudah dibuat (staf, kanit, admin)
- [ ] Login test sebagai staf - berhasil
- [ ] Login test sebagai kanit - berhasil
- [ ] Login test sebagai admin - berhasil
- [ ] Buat pengajuan dari staf - berhasil
- [ ] Setujui dari kanit - berhasil
- [ ] Proses dari admin - berhasil
- [ ] Verifikasi status di semua role - konsisten
- [ ] Menu sidebar sudah terintegrasi

---

## DUKUNGAN & BANTUAN

Jika ada masalah, check:
1. Error message di browser
2. Log di database (SELECT * FROM pengajuan)
3. Session role: echo $_SESSION['role']
4. Database connection: ping ke database

---

**Selamat menggunakan Sistem Workflow Pengajuan! 🎉**
