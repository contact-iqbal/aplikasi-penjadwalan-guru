-- Migration: Remove jam_per_minggu column from tbl_guru_mapel
-- Reason: Jam per minggu tidak diperlukan karena sudah diatur di tbl_waktu_pelajaran
-- Date: 2025-10-25

ALTER TABLE `tbl_guru_mapel` DROP COLUMN `jam_per_minggu`;
