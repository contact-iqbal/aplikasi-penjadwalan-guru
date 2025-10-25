# Changelog

## 2025-10-25 - Penghapusan Kolom jam_per_minggu

### Perubahan
- Menghapus kolom `jam_per_minggu` dari tabel `tbl_guru_mapel`
- Rentang jam per minggu tidak diperlukan lagi karena pengaturan waktu sudah diatur melalui `tbl_waktu_pelajaran`

### File yang Dimodifikasi
1. **database.sql**
   - Menghapus kolom `jam_per_minggu` dari definisi tabel `tbl_guru_mapel`

2. **guru.php**
   - Menghapus input dan validasi `jam_per_minggu` dari form penugasan mapel
   - Menghapus fungsi edit jam per minggu
   - Menghapus tampilan jam per minggu di tabel daftar guru
   - Menyederhanakan modal edit penugasan menjadi modal hapus penugasan

3. **proses_otomatis.php**
   - Menonaktifkan penjadwalan otomatis karena tidak ada lagi informasi jam per minggu
   - Sistem akan redirect ke `susun_jadwal.php` dengan pesan bahwa penjadwalan otomatis dinonaktifkan
   - User disarankan menggunakan fitur Jadwal Wajib untuk pengaturan jadwal manual

4. **jadwal_wajib.php**
   - Menghapus validasi alokasi jam per minggu
   - Menghapus pengecekan apakah jam terjadwal melebihi alokasi
   - Tetap mempertahankan validasi penugasan kelas

5. **test_penugasan.php**
   - Menghapus kolom `jam_per_minggu` dari query dan tampilan tabel

### Dampak
- Penugasan mapel ke guru menjadi lebih sederhana, hanya fokus pada relasi guru-mapel
- Pengaturan jadwal sepenuhnya bergantung pada slot waktu yang tersedia di `tbl_waktu_pelajaran`
- Penjadwalan otomatis tidak lagi tersedia, semua penjadwalan harus dilakukan manual melalui Jadwal Wajib

### Migrasi Database
Jalankan script berikut untuk mengupdate database yang sudah ada:
```sql
ALTER TABLE `tbl_guru_mapel` DROP COLUMN `jam_per_minggu`;
```

Atau gunakan file migration yang tersedia di `migrations/remove_jam_per_minggu.sql`
