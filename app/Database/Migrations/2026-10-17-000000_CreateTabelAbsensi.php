<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTabelAbsensi extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('admin');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nis'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'kelas'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nis');
        $this->forge->addKey(['kelas', 'nama']);
        $this->forge->createTable('siswa');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'siswa_id'   => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE'],
            'status'     => ['type' => 'ENUM', 'constraint' => ['hadir', 'terlambat', 'izin', 'sakit', 'dispensasi', 'alpa']],
            'jam_scan'   => ['type' => 'TIME', 'null' => true],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['siswa_id', 'tanggal']);
        $this->forge->addKey('tanggal');
        $this->forge->addForeignKey('siswa_id', 'siswa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('presensi');

        $this->forge->addField([
            'tanggal'    => ['type' => 'DATE'],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addPrimaryKey('tanggal');
        $this->forge->createTable('libur');
    }

    public function down(): void
    {
        $this->forge->dropTable('libur', true);
        $this->forge->dropTable('presensi', true);
        $this->forge->dropTable('siswa', true);
        $this->forge->dropTable('admin', true);
    }
}
