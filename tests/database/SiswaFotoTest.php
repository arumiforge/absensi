<?php

use App\Services\Akun\DiLuarHak;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * One student's photo (docs/04 FS-MD-07, AC-MD-07-01, AC-MD-07-02, E1, E2,
 * docs/09 HAL-MD-09, §13, docs/11 VAL-28, docs/12 SEC-52, SEC-81).
 *
 * @internal
 */
final class SiswaFotoTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const FOLDER = ['foto/', 'foto/kiosk/', 'foto/kecil/'];

    private const XHR = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

    /** @var array<string, mixed> */
    private array $admin;

    /** @var array<string, mixed> */
    private array $wali;

    /** @var array<string, mixed> Student of 7A (the wali kelas' rombel) */
    private array $g;

    /** @var array<string, mixed> Student of 7B */
    private array $siswa7b;

    /** @var list<string> Photo files that existed before the test */
    private array $fotoAwal;

    private string $dir;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');
        $this->admin   = $this->buatAkun(['username' => 'admin.tu', 'nama' => 'Admin Tata Usaha'], ['admin']);
        $this->wali    = $this->buatAkun(['username' => 'wali.7a', 'nama' => 'Wali Tujuh A']);
        $ta            = $this->buatTahunAjaran();
        $rombel7a      = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7A', 'wali_kelas_id' => $this->wali['id']]);
        $rombel7b      = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7B']);
        $this->g       = $this->buatSiswa(['nama' => 'Gita', 'rombel_id' => $rombel7a['id']]);
        $this->siswa7b = $this->buatSiswa(['nama' => 'Galih', 'rombel_id' => $rombel7b['id']]);

        foreach (self::FOLDER as $folder) {
            is_dir(WRITEPATH . 'uploads/' . $folder) || mkdir(WRITEPATH . 'uploads/' . $folder, 0755, true);
        }
        $this->fotoAwal = $this->daftarFoto();
        $this->dir      = sys_get_temp_dir() . '/siswa-foto-test-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        service('superglobals')->setFilesArray([]);
        array_map('unlink', array_diff($this->daftarFoto(), $this->fotoAwal));
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
        Time::setTestNow();
        service('akunAktif')->clear();
        parent::tearDown();
    }

    public function testWaliKelasReplacesPhotoOfOwnStudent(): void
    {
        // AC-MD-07-01, docs/07 ARS-53, FR-MD-09
        $lama = $this->fotoLama($this->g);

        $form = $this->kirim($this->wali, 'GET', "panel/siswa/{$this->g['id']}/foto/ubah");
        $form->assertOK();
        $form->assertSee('enctype="multipart/form-data"', null);
        $form->assertSee('name="_method" value="PUT"', null);
        $form->assertSee('accept="image/jpeg,image/png,image/webp"', null);
        $form->assertSee('data-pratinjau-foto="pratinjau-foto"', null);
        $form->assertSee('aset/js/pratinjau-foto.js');
        $form->assertSee("src=\"https://example.com/panel/siswa/{$this->g['id']}/foto\"", null);
        $form->assertSee('Simpan foto');
        $this->assertStringNotContainsString('<script>', $form->response()->getBody());

        Time::setTestNow('2026-10-13 09:15:00', 'Asia/Jakarta');
        $result = $this->unggah($this->wali, $this->g, 'IMG_2026.jpg', $this->jpeg(1200, 1600));

        $result->assertRedirectTo("https://example.com/panel/siswa/{$this->g['id']}");
        $this->assertSame('Foto Gita sudah diganti.', session('sukses'));

        $siswa = $this->db->table('siswa')->where('id', $this->g['id'])->get()->getRowArray();
        $this->assertMatchesRegularExpression('#^foto/[0-9a-f]{32}\.jpg$#', $siswa['foto_file']);
        $this->assertNotSame($lama, $siswa['foto_file']);
        $this->assertSame('2026-10-13 09:15:00', $siswa['foto_diganti_at']);
        foreach (['foto/' => [600, 800], 'foto/kiosk/' => [300, 400], 'foto/kecil/' => [120, 160]] as $folder => $ukuran) {
            $this->assertSame([...$ukuran, IMAGETYPE_JPEG], array_slice(getimagesize(WRITEPATH . 'uploads/' . $folder . basename($siswa['foto_file'])), 0, 3), $folder);
            $this->assertFileDoesNotExist(WRITEPATH . 'uploads/' . $folder . basename($lama), $folder);
        }

        $log = $this->db->table('log_data_siswa')->where('siswa_id', $this->g['id'])->get()->getResultArray();
        $this->assertCount(1, $log);
        $this->assertSame(['foto_diganti', (string) $this->wali['id'], '2026-10-13 09:15:00'], [$log[0]['jenis'], (string) $log[0]['pelaku_id'], $log[0]['created_at']]);
    }

    public function testFirstPhotoSavesWithUploadMessage(): void
    {
        // FS-MD-07 item 1, E3: a student without a photo gets one; PNG and WebP become JPEG.
        $form = $this->kirim($this->admin, 'GET', "panel/siswa/{$this->siswa7b['id']}/foto/ubah");
        $form->assertSee('Belum ada foto.');
        $form->assertSee('Galih, belum ada foto');

        $png = $this->dir . '/foto.png';
        imagepng(imagecreatetruecolor(300, 400), $png);
        $result = $this->unggah($this->admin, $this->siswa7b, 'galih.png', $png);

        $result->assertRedirectTo("https://example.com/panel/siswa/{$this->siswa7b['id']}");
        $this->assertSame('Foto Galih sudah disimpan.', session('sukses'));
        $file = $this->db->table('siswa')->where('id', $this->siswa7b['id'])->get()->getRow()->foto_file;
        $this->assertSame([300, 400, IMAGETYPE_JPEG], array_slice(getimagesize(WRITEPATH . 'uploads/' . $file), 0, 3));
    }

    public function testPdfIsRejectedAndOldPhotoKept(): void
    {
        // AC-MD-07-02, E1, docs/11 §5.2 FS-MD-07 E1
        $lama = $this->fotoLama($this->g);
        $pdf  = $this->dir . '/rapor';
        file_put_contents($pdf, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");

        $result = $this->unggah($this->admin, $this->g, 'rapor.pdf', $pdf);

        $result->assertRedirectTo("https://example.com/panel/siswa/{$this->g['id']}/foto/ubah");
        $this->assertSame(['foto' => 'File harus berupa gambar JPG, PNG, atau WebP. Foto lama tetap dipakai.'], session('_ci_validation_errors'));
        $this->assertNull(session('sukses'));
        $this->seeInDatabase('siswa', ['id' => $this->g['id'], 'foto_file' => $lama, 'foto_diganti_at' => null]);
        $this->assertSame($this->fotoAwal, array_values(array_diff($this->daftarFoto(), $this->fileFoto($lama))));
        foreach ($this->fileFoto($lama) as $path) {
            $this->assertFileExists($path);
        }
        $this->dontSeeInDatabase('log_data_siswa', ['siswa_id' => $this->g['id']]);

        // The form shows the message under the field and in the summary (VAL-07).
        $form = $this->kirim($this->admin, 'GET', "panel/siswa/{$this->g['id']}/foto/ubah", [], [], ['_ci_validation_errors' => session('_ci_validation_errors')]);
        $form->assertSee('Periksa 1 isian yang ditandai.');
        $form->assertSee('File harus berupa gambar JPG, PNG, atau WebP. Foto lama tetap dipakai.');
        $form->assertSee('aria-invalid="true"', null);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function berkasBerbahaya(): iterable
    {
        $format = 'File harus berupa gambar JPG, PNG, atau WebP.';

        yield 'PHP renamed .png' => ['foto.png', "<?php system(\$_GET['c']);", $format];
        yield 'PHP renamed .jpg' => ['foto.jpg', "<?php echo 'x';", $format];
        yield 'SVG' => ['foto.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', $format];
        yield 'PNG named .jpg' => ['foto.jpg', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true), $format];
        yield 'damaged JPEG' => ['foto.jpg', "\xFF\xD8\xFF\xE0" . str_repeat('x', 100), 'File foto.jpg tidak dapat dibaca sebagai gambar.'];
        yield 'too large' => ['foto.jpg', str_repeat('x', 11 * 1024 * 1024), 'Ukuran file foto.jpg 11,0 MB. Paling besar 10 MB.'];
    }

    /**
     * @dataProvider berkasBerbahaya
     */
    public function testDangerousFilesAreRejected(string $nama, string $isi, string $pesan): void
    {
        // docs/12 SEC-50, SEC-81, docs/11 VAL-28. No photo yet, so no "Foto lama" sentence.
        file_put_contents($this->dir . '/unggah', $isi);

        $result = $this->unggah($this->admin, $this->g, $nama, $this->dir . '/unggah');

        $result->assertRedirectTo("https://example.com/panel/siswa/{$this->g['id']}/foto/ubah");
        $this->assertSame(['foto' => $pesan], session('_ci_validation_errors'));
        $this->seeInDatabase('siswa', ['id' => $this->g['id'], 'foto_file' => null]);
        $this->assertSame($this->fotoAwal, $this->daftarFoto());
        $this->dontSeeInDatabase('log_data_siswa', ['siswa_id' => $this->g['id']]);
    }

    public function testMissingFileAndPhpUploadErrors(): void
    {
        // docs/11 GAL-11 item 3: UPLOAD_ERR_NO_FILE on a required field, and PHP errors.
        $this->fotoLama($this->g);
        $form = "https://example.com/panel/siswa/{$this->g['id']}/foto/ubah";

        service('superglobals')->setFilesArray([]);
        $this->kirim($this->admin, 'POST', "panel/siswa/{$this->g['id']}/foto", ['_method' => 'PUT'])->assertRedirectTo($form);
        $this->assertSame(['foto' => 'Pilih file.'], session('_ci_validation_errors'));

        $this->unggah($this->admin, $this->g, 'foto.jpg', '', UPLOAD_ERR_INI_SIZE)->assertRedirectTo($form);
        $this->assertSame(['foto' => 'Ukuran file foto.jpg melebihi batas. Foto lama tetap dipakai.'], session('_ci_validation_errors'));

        $this->unggah($this->admin, $this->g, 'foto.jpg', '', UPLOAD_ERR_PARTIAL);
        $this->assertSame(['foto' => 'Unggahan foto.jpg terputus. Coba lagi. Foto lama tetap dipakai.'], session('_ci_validation_errors'));

        $this->dontSeeInDatabase('log_data_siswa', ['siswa_id' => $this->g['id']]);
    }

    public function testWaliKelasOfAnotherRombelIsRefused(): void
    {
        // E2, HA-MD-07 and HA-MD-05 with rombel scope (docs/04 §4.1).
        $this->fotoLama($this->siswa7b);
        $jpeg = $this->jpeg(60, 80);
        $id   = $this->siswa7b['id'];

        foreach ([['GET', "panel/siswa/{$id}/foto/ubah"], ['GET', "panel/siswa/{$id}/foto"], ['GET', "panel/siswa/{$id}/foto?ukuran=kecil"], ['PUT', "panel/siswa/{$id}/foto"]] as [$method, $uri]) {
            try {
                if ($method === 'PUT') {
                    $this->unggah($this->wali, $this->siswa7b, 'foto.jpg', $jpeg);
                } else {
                    $this->kirim($this->wali, $method, $uri);
                }
                $this->fail("{$method} {$uri} should be refused");
            } catch (DiLuarHak) {
                $this->addToAssertionCount(1);
            }
        }
        $this->dontSeeInDatabase('log_data_siswa', ['siswa_id' => $id]);

        // An ID that does not exist is 403 too for a limited scope (docs/09 RT-11).
        $this->expectException(DiLuarHak::class);
        $this->kirim($this->wali, 'GET', 'panel/siswa/99999/foto');
    }

    public function testStaffWithoutHaMd07SeeButCannotChange(): void
    {
        // HA-MD-05 (semua) serves the photo; HA-MD-07 is admin and wali kelas only.
        $lama  = $this->fotoLama($this->g);
        $piket = $this->buatAkun(['username' => 'piket'], ['guru_piket']);

        $this->kirim($piket, 'GET', "panel/siswa/{$this->g['id']}/foto")->assertOK();
        $this->kirim($piket, 'GET', "panel/siswa/{$this->g['id']}/foto/ubah", [], self::XHR)->assertStatus(403);
        $this->unggah($piket, $this->g, 'foto.jpg', $this->jpeg(60, 80), UPLOAD_ERR_OK, self::XHR)->assertStatus(403);
        $this->seeInDatabase('siswa', ['id' => $this->g['id'], 'foto_file' => $lama]);

        $profil = $this->kirim($piket, 'GET', "panel/siswa/{$this->g['id']}");
        $profil->assertOK();
        $profil->assertSee("src=\"https://example.com/panel/siswa/{$this->g['id']}/foto\"", null);
        $profil->assertDontSee('Ganti foto');
    }

    public function testPhotoIsServedWithFileHeadersAndSmallSize(): void
    {
        // docs/09 §13, docs/12 SEC-52, docs/08 UI-22
        $lama = $this->fotoLama($this->g);

        $standar = $this->kirim($this->wali, 'GET', "panel/siswa/{$this->g['id']}/foto");
        $standar->assertOK();
        $standar->assertHeader('Content-Type', 'image/jpeg');
        $standar->assertHeader('Content-Disposition', 'inline');
        $standar->assertHeader('Cache-Control', 'no-store');
        $standar->assertHeader('Content-Security-Policy', "sandbox; default-src 'none'; frame-ancestors 'none'");
        $this->assertSame(file_get_contents(WRITEPATH . 'uploads/' . $lama), $standar->response()->getBody());

        $kecil = $this->kirim($this->wali, 'GET', "panel/siswa/{$this->g['id']}/foto?ukuran=kecil");
        $kecil->assertOK();
        $kecil->assertHeader('Cache-Control', 'no-store');
        $kecil->assertHeader('Content-Security-Policy', "sandbox; default-src 'none'; frame-ancestors 'none'");
        $this->assertSame(file_get_contents(WRITEPATH . 'uploads/foto/kecil/' . basename($lama)), $kecil->response()->getBody());
        $this->assertSame([12, 16], array_slice(getimagesizefromstring($kecil->response()->getBody()), 0, 2));

        // VAL-13: an unknown size is ignored.
        $lain = $this->kirim($this->wali, 'GET', "panel/siswa/{$this->g['id']}/foto?ukuran=besar");
        $this->assertSame(file_get_contents(WRITEPATH . 'uploads/' . $lama), $lain->response()->getBody());
    }

    public function testNoPhotoOrMissingFileIs404(): void
    {
        // docs/09 §13 rule 3, docs/11 §7
        $this->kirim($this->admin, 'GET', "panel/siswa/{$this->g['id']}/foto")->assertStatus(404);
        $this->kirim($this->admin, 'GET', "panel/siswa/{$this->g['id']}/foto?ukuran=kecil")->assertStatus(404);
        $this->kirim($this->admin, 'GET', 'panel/siswa/99999/foto')->assertStatus(404);

        $lama = $this->fotoLama($this->g);
        unlink(WRITEPATH . 'uploads/foto/kecil/' . basename($lama));
        $this->kirim($this->admin, 'GET', "panel/siswa/{$this->g['id']}/foto?ukuran=kecil")->assertStatus(404);
        $this->kirim($this->admin, 'GET', "panel/siswa/{$this->g['id']}/foto")->assertOK();
    }

    public function testListSearchAndProfileShowRealPhotos(): void
    {
        // docs/08 UI-22 to UI-25, docs/09 HAL-MD-04, HAL-MD-06, EP-MD-01
        $this->fotoLama($this->g);
        $kecilG = "src=\"https://example.com/panel/siswa/{$this->g['id']}/foto?ukuran=kecil\"";

        $list = $this->kirim($this->admin, 'GET', 'panel/siswa');
        $list->assertOK();
        $list->assertSee($kecilG, null);
        $list->assertSee('loading="lazy"', null);
        $list->assertDontSee("panel/siswa/{$this->siswa7b['id']}/foto", null);

        $cari = $this->kirim($this->admin, 'GET', 'panel/siswa/cari?cari=Gi&untuk=profil', [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html']);
        $cari->assertOK();
        $cari->assertSee($kecilG, null);

        // The wali kelas sees "Ganti foto" for their student; the other student has none.
        $profil = $this->kirim($this->wali, 'GET', "panel/siswa/{$this->g['id']}");
        $profil->assertSee("src=\"https://example.com/panel/siswa/{$this->g['id']}/foto\"", null);
        $profil->assertSee('alt="Gita"', null);
        $profil->assertSee("href=\"https://example.com/panel/siswa/{$this->g['id']}/foto/ubah\"", null);
        $profil->assertSee('Ganti foto');

        $tanpa = $this->kirim($this->admin, 'GET', "panel/siswa/{$this->siswa7b['id']}");
        $tanpa->assertSee('Galih, belum ada foto');
        $tanpa->assertSee('Unggah foto');
        $tanpa->assertDontSee("panel/siswa/{$this->siswa7b['id']}/foto\"", null);
    }

    /**
     * Gives the student an existing photo in all three sizes (tiny JPEGs
     * with the real folder layout); returns `siswa.foto_file`.
     *
     * @param array<string, mixed> $siswa
     */
    private function fotoLama(array $siswa): string
    {
        $nama = bin2hex(random_bytes(16)) . '.jpg';
        foreach (['foto/' => [30, 40], 'foto/kiosk/' => [24, 32], 'foto/kecil/' => [12, 16]] as $folder => [$w, $h]) {
            imagejpeg(imagecreatetruecolor($w, $h), WRITEPATH . 'uploads/' . $folder . $nama);
        }
        $this->db->table('siswa')->where('id', $siswa['id'])->update(['foto_file' => 'foto/' . $nama]);

        return 'foto/' . $nama;
    }

    /**
     * @return list<string>
     */
    private function fileFoto(string $relatif): array
    {
        return array_map(static fn (string $folder): string => WRITEPATH . 'uploads/' . $folder . basename($relatif), self::FOLDER);
    }

    /**
     * @return list<string> Every file in the three photo folders, sorted
     */
    private function daftarFoto(): array
    {
        $files = [];
        foreach (self::FOLDER as $folder) {
            $files = [...$files, ...(glob(WRITEPATH . 'uploads/' . $folder . '*.jpg') ?: [])];
        }
        sort($files);

        return $files;
    }

    private function jpeg(int $w, int $h): string
    {
        $path = $this->dir . '/' . bin2hex(random_bytes(4));
        imagejpeg(imagecreatetruecolor($w, $h), $path);

        return $path;
    }

    /**
     * PUT /panel/siswa/{id}/foto as POST + _method with one file.
     *
     * @param array<string, mixed>  $akun
     * @param array<string, mixed>  $siswa
     * @param array<string, string> $headers
     */
    private function unggah(array $akun, array $siswa, string $nama, string $path, int $error = UPLOAD_ERR_OK, array $headers = []): TestResponse
    {
        service('superglobals')->setFilesArray(['foto' => [
            'name'     => $nama,
            'type'     => 'image/jpeg',
            'tmp_name' => $path,
            'error'    => $error,
            'size'     => $path === '' ? 0 : filesize($path),
        ]]);

        return $this->kirim($akun, 'POST', "panel/siswa/{$siswa['id']}/foto", ['_method' => 'PUT'], $headers);
    }

    /**
     * @param array<string, mixed>  $akun
     * @param array<string, mixed>  $isian
     * @param array<string, string> $headers
     * @param array<string, mixed>  $sesi    Extra session data, e.g. flashed errors
     */
    private function kirim(array $akun, string $method, string $uri, array $isian = [], array $headers = [], array $sesi = []): TestResponse
    {
        foreach (['router', 'response', 'request'] as $service) {
            Services::resetSingle($service);
        }
        service('akunAktif')->clear();
        service('throttler')->remove('cari-siswa-' . $akun['id']);

        return $this->withSession($sesi + $this->sesiAkun($akun))
            ->withHeaders($headers + ['X-CSRF-TOKEN' => service('security')->getHash()])
            ->call($method, $uri, $isian);
    }
}
