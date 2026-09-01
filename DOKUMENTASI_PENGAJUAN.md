# Dokumentasi Sistem Workflow Pengajuan

## Deskripsi Sistem
Sistem pengajuan ini memiliki workflow approval bertingkat dengan tracking lengkap untuk semua role (staf, kanit/kabid, dan admin).

## Flow Proses Pengajuan

```
STAF
  │
  ├─ Buat Pengajuan
  │  (Status: PENDING)
  │
  └─ Lihat Status: "Menunggu Approve"
     (Bisa dihapus jika masih PENDING)
     │
     │
KANIT/KABID
     │
     ├─ Review Pengajuan
     │
     ├─ Setujui ✓
     │ (Status: APPROVED_KANIT)
     │ Catatan: [Optional]
     │
     └─ Tolak ✗
       (Status: REJECTED)
       Alasan: [Mandatory]
       
       └─> Kembali ke STAF
          "Menunggu Approve"
          Dengan keterangan ditolak
          │
          STAF bisa buat pengajuan baru
     
     
ADMIN (Jika Disetujui)
     │
     ├─ Lihat Pengajuan yang Disetujui
     │
     ├─ Mulai Proses
     │ (Status: PROCESSING)
     │
     └─ Selesaikan
       (Status: COMPLETED)
       Catatan Admin: [Mandatory]
```

## Menu Akses per Role

### 1. STAF
- **pengajuan_staf.php** - Halaman utama
  - Form: Buat pengajuan baru
  - List: Daftar pengajuan dengan status
  - Aksi: Hapus (hanya PENDING)
  - Status yang terlihat:
    - "Menunggu Approve" (PENDING)
    - "Disetujui Kanit/Kabid" (APPROVED_KANIT)
    - "Sedang Diproses" (PROCESSING)
    - "Selesai" (COMPLETED)
    - "Ditolak" (REJECTED)

### 2. KANIT/KABID
- **persetujuan_kanit.php** - Halaman utama
  - Tab 1: Pengajuan Menunggu Persetujuan
    - List pengajuan dengan status PENDING
    - Tombol: Detail, Setujui, Tolak
    - Modal Setujui: Catatan (optional)
    - Modal Tolak: Alasan (mandatory)
  - Tab 2: Riwayat Persetujuan
    - List 10 pengajuan terakhir yang sudah diproses

### 3. ADMIN
- **pengajuan_admin.php** - Halaman utama
  - Statistik: Counter untuk setiap status
  - Tab 1: Siap Diproses (APPROVED_KANIT)
    - List pengajuan dengan info persetujuan kanit
    - Tombol: Mulai Proses
    - Modal: Konfirmasi mulai proses
  - Tab 2: Sedang Diproses (PROCESSING)
    - List pengajuan yang sedang diproses
    - Tombol: Selesaikan
    - Modal: Catatan penyelesaian (mandatory)
  - Tab 3: Semua Riwayat
    - Tabel lengkap semua pengajuan
    - DataTable dengan sorting & search
    - Info: Tanggal dibuat, persetujuan, diproses, selesai

## Tabel Database: `pengajuan`

```sql
id (PK)
tipe_pengajuan (MoU, SK, Kontrak, Lainnya)
nama
tanggal_lahir
tanggal_tmt
unit
keterangan
status (pending, approved_kanit, processing, completed, rejected)
created_by (user_id)
created_at (timestamp)
approved_by_kanit (user_id)
approved_date_kanit
catatan_kanit
rejected_by_kanit
rejected_date_kanit
rejected_reason
processed_by (user_id)
processed_date
completed_by (user_id)
completed_date
catatan_admin
```

## Status Pengajuan

| Status | Role yang Lihat | Warna Badge | Keterangan |
|--------|-----------------|------------|------------|
| pending | STAF, KANIT | Warning (Kuning) | Menunggu Persetujuan |
| approved_kanit | ADMIN | Success (Hijau) | Disetujui, Siap Diproses |
| processing | ADMIN | Info (Biru) | Sedang Diproses |
| completed | STAF, ADMIN | Success (Hijau) | Selesai |
| rejected | STAF | Danger (Merah) | Ditolak |

## Fitur Keamanan

1. **Access Control**: Setiap role hanya bisa akses halaman yang sesuai
2. **Data Protection**: 
   - STAF hanya bisa lihat pengajuan milik mereka
   - KANIT/KABID hanya bisa lihat pengajuan PENDING
   - ADMIN bisa lihat semua pengajuan
3. **Validasi Status**: Setiap action divalidasi status saat ini
4. **Edit Restriction**: Pengajuan hanya bisa dihapus saat PENDING

## Instalasi Database

1. Buka phpMyAdmin
2. Buka database `pegawai_mou_sk`
3. Paste isi dari `pengajuan_database.sql`
4. Klik Execute

## Testing

### Test Case 1: Staf Membuat Pengajuan
1. Login sebagai STAF
2. Akses `pengajuan_staf.php`
3. Isi form dan klik "Kirim Pengajuan"
4. Status harus "Menunggu Approve"

### Test Case 2: Kanit Approve
1. Login sebagai KANIT/KABID
2. Akses `persetujuan_kanit.php`
3. Tab 1 harus menampilkan pengajuan dari test case 1
4. Klik "Detail" untuk melihat lengkap
5. Klik "Setujui" dengan catatan
6. Status berubah menjadi "Disetujui Kanit/Kabid"

### Test Case 3: Admin Proses
1. Login sebagai ADMIN
2. Akses `pengajuan_admin.php`
3. Tab 1 "Siap Diproses" harus menampilkan pengajuan dari test case 2
4. Klik "Mulai Proses"
5. Status berubah menjadi "Sedang Diproses"
6. Tab 2 akan menampilkan pengajuan tersebut
7. Klik "Selesaikan" dengan catatan admin
8. Status berubah menjadi "Selesai"

### Test Case 4: Staf Lihat Status
1. Login sebagai STAF (pengaju dari test case 1)
2. Akses `pengajuan_staf.php`
3. Pengajuan harus menampilkan urutan status:
   - Pending → Disetujui → Diproses → Selesai

### Test Case 5: Kanit Tolak
1. Buat pengajuan baru dari STAF
2. Login sebagai KANIT
3. Di `persetujuan_kanit.php` klik "Tolak"
4. Masukkan alasan penolakan
5. Status berubah menjadi "Ditolak"
6. STAF akan melihat alasan penolakan

## Pengembangan Lebih Lanjut

1. **Email Notification**: Tambahkan notifikasi email saat status berubah
2. **Attachment**: Tambahkan upload dokumen pendukung
3. **Export**: Tambahkan fitur export laporan
4. **Dashboard**: Buat dashboard admin dengan grafik workflow
5. **Archive**: Buat fitur archive untuk pengajuan lama
6. **Timeline**: Visualisasi timeline proses pengajuan per pengajuan
