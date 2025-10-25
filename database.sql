DROP TABLE IF EXISTS 
  `tbl_jadwal_wajib`, 
  `tbl_jadwal`, 
  `tbl_penugasan_kelas`, 
  `tbl_guru_mapel`, 
  `tbl_guru`, 
  `tbl_mata_pelajaran`, 
  `tbl_kelas`, 
  `tbl_waktu_pelajaran`;

CREATE TABLE `tbl_guru` (
  `id_guru` int(11) NOT NULL AUTO_INCREMENT,
  `nama_guru` varchar(100) NOT NULL,
  PRIMARY KEY (`id_guru`),
  UNIQUE KEY `nama_guru` (`nama_guru`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tbl_mata_pelajaran` (
  `id_mapel` int(11) NOT NULL AUTO_INCREMENT,
  `nama_mapel` varchar(100) NOT NULL,
  PRIMARY KEY (`id_mapel`),
  UNIQUE KEY `nama_mapel` (`nama_mapel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tbl_kelas` (
  `id_kelas` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) NOT NULL,
  `jumlah_jam_per_hari` int(2) NOT NULL,
  PRIMARY KEY (`id_kelas`),
  UNIQUE KEY `nama_kelas` (`nama_kelas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tbl_waktu_pelajaran` (
  `id_waktu` int(11) NOT NULL AUTO_INCREMENT,
  `hari` varchar(20) NOT NULL,
  `jam_ke` int(2) NOT NULL,
  `range_waktu` varchar(50) NOT NULL,
  PRIMARY KEY (`id_waktu`),
  UNIQUE KEY `hari_jam` (`hari`,`jam_ke`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tbl_guru_mapel` (
  `id_guru_mapel` int(11) NOT NULL AUTO_INCREMENT,
  `id_guru` int(11) NOT NULL,
  `id_mapel` int(11) NOT NULL,
  PRIMARY KEY (`id_guru_mapel`),
  UNIQUE KEY `guru_mapel_unik` (`id_guru`,`id_mapel`),
  CONSTRAINT `fk_gm_guru` FOREIGN KEY (`id_guru`) REFERENCES `tbl_guru` (`id_guru`) ON DELETE CASCADE,
  CONSTRAINT `fk_gm_mapel` FOREIGN KEY (`id_mapel`) REFERENCES `tbl_mata_pelajaran` (`id_mapel`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tbl_penugasan_kelas` (
  `id_penugasan` int(11) NOT NULL AUTO_INCREMENT,
  `id_guru_mapel` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  PRIMARY KEY (`id_penugasan`),
  UNIQUE KEY `guru_mapel_kelas_unik` (`id_guru_mapel`,`id_kelas`),
  CONSTRAINT `fk_penugasan_gm` FOREIGN KEY (`id_guru_mapel`) REFERENCES `tbl_guru_mapel` (`id_guru_mapel`) ON DELETE CASCADE,
  CONSTRAINT `fk_penugasan_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `tbl_kelas` (`id_kelas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tbl_jadwal` (
  `id_jadwal` int(11) NOT NULL AUTO_INCREMENT,
  `id_waktu` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `id_guru_mapel` int(11) NOT NULL,
  PRIMARY KEY (`id_jadwal`),
  UNIQUE KEY `slot_kelas_unik` (`id_waktu`,`id_kelas`),
  CONSTRAINT `fk_jadwal_gm` FOREIGN KEY (`id_guru_mapel`) REFERENCES `tbl_guru_mapel` (`id_guru_mapel`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tbl_jadwal_wajib` (
  `id_wajib` int(11) NOT NULL AUTO_INCREMENT,
  `id_waktu` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `id_guru_mapel` int(11) NOT NULL,
  PRIMARY KEY (`id_wajib`),
  UNIQUE KEY `slot_wajib_unik` (`id_waktu`,`id_kelas`),
  CONSTRAINT `fk_wajib_gm` FOREIGN KEY (`id_guru_mapel`) REFERENCES `tbl_guru_mapel` (`id_guru_mapel`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$
CREATE TRIGGER `trg_cek_bentrok_guru_insert` 
BEFORE INSERT ON `tbl_jadwal`
FOR EACH ROW 
BEGIN
    DECLARE guru_id_baru INT;
    DECLARE guru_bentrok INT;

    SELECT id_guru 
    INTO guru_id_baru 
    FROM tbl_guru_mapel 
    WHERE id_guru_mapel = NEW.id_guru_mapel;

    SELECT COUNT(*) 
    INTO guru_bentrok
    FROM tbl_jadwal j
    JOIN tbl_guru_mapel gm ON j.id_guru_mapel = gm.id_guru_mapel
    WHERE j.id_waktu = NEW.id_waktu 
      AND gm.id_guru = guru_id_baru;

    IF guru_bentrok > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Bentrok: Guru ini sudah mengajar di kelas lain pada waktu yang sama.';
    END IF;
END$$
DELIMITER ;
