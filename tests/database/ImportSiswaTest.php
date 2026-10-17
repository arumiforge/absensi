<?php

use App\Services\ImportSiswa;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ImportSiswaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    public function testImportsNewAndUpdatesExisting(): void
    {
        $this->db->table('siswa')->insert(['nis' => '0012', 'nama' => 'Budi', 'kelas' => '7A']);
        $galat = [];

        $hasil = (new ImportSiswa())->jalankan([
            ['NIS', 'Nama', 'Kelas'],
            ['0012', 'Budi Santoso', '8A'],
            [1234567890.0, ' Ani ', '7B'],
            [null, null, null],
        ], $galat);

        $this->assertSame([], $galat);
        $this->assertSame(['baru' => 1, 'diperbarui' => 1], $hasil);
        $this->seeInDatabase('siswa', ['nis' => '0012', 'nama' => 'Budi Santoso', 'kelas' => '8A']);
        $this->seeInDatabase('siswa', ['nis' => '1234567890', 'nama' => 'Ani', 'kelas' => '7B']);
    }

    public function testAnyBadRowRollsBackTheFile(): void
    {
        $galat = [];

        (new ImportSiswa())->jalankan([
            ['0012', 'Budi', '7A'],
            ['0013', '', '7A'],
            ['0012', 'Budi lagi', '7A'],
        ], $galat);

        $this->assertSame(['Baris 2: Nama belum diisi.', 'Baris 3: NIS 0012 sudah ada di baris 1.'], $galat);
        $this->seeNumRecords(0, 'siswa', []);
    }

    public function testEmptyFileIsReported(): void
    {
        $galat = [];
        (new ImportSiswa())->jalankan([['NIS', 'Nama', 'Kelas']], $galat);

        $this->assertCount(1, $galat);
    }
}
