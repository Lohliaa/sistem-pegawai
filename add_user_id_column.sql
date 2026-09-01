-- Hubungkan data pegawai dengan akun login
ALTER TABLE pegawai ADD COLUMN user_id INT NULL AFTER id;
UPDATE pegawai p INNER JOIN users u ON p.nama = u.username SET p.user_id = u.id WHERE p.user_id IS NULL;
