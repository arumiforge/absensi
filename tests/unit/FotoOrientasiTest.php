<?php

use App\Services\Berkas\Gambar;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Student photo processing order (docs/07 ARS-53, docs/15 L03-04): a phone
 * photo stored sideways with EXIF orientation 6 comes out upright in all
 * three sizes without black areas, and a transparent PNG gets a white
 * background. Metadata is dropped (docs/12 SEC-51).
 *
 * @internal
 */
final class FotoOrientasiTest extends CIUnitTestCase
{
    private string $dir;

    /** @var list<string> `siswa.foto_file` values to remove after the test */
    private array $simpanan = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/foto-orientasi-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $gambar = new Gambar();
        array_map($gambar->hapusFoto(...), $this->simpanan);
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
        parent::tearDown();
    }

    public function testPortraitWithExifOrientation6IsTurnedUprightInEverySize(): void
    {
        // Stored landscape 1200×900: left half red, right half blue. Orientation 6
        // means "turn 90° clockwise to show", so the photo is portrait 900×1200
        // with red on top and blue below.
        $image = imagecreatetruecolor(1200, 900);
        imagefilledrectangle($image, 0, 0, 599, 899, imagecolorallocate($image, 220, 0, 0));
        imagefilledrectangle($image, 600, 0, 1199, 899, imagecolorallocate($image, 0, 0, 220));
        ob_start();
        imagejpeg($image, null, 95);
        $jpeg = $this->denganOrientasi((string) ob_get_clean(), 6);
        file_put_contents($this->dir . '/ponsel.jpg', $jpeg);
        $this->assertSame(6, exif_read_data($this->dir . '/ponsel.jpg')['Orientation'] ?? null, 'the test image must carry EXIF orientation 6');

        $relatif = $this->simpan($this->dir . '/ponsel.jpg');

        foreach (['foto/' => [600, 800], 'foto/kiosk/' => [300, 400], 'foto/kecil/' => [120, 160]] as $folder => [$lebar, $tinggi]) {
            $path = WRITEPATH . 'uploads/' . $folder . basename($relatif);
            $this->assertSame([$lebar, $tinggi, IMAGETYPE_JPEG], array_slice(getimagesize($path), 0, 3), $folder);

            $hasil = imagecreatefromjpeg($path);
            // Corners and edges of both halves: no black band from a wrong canvas size.
            foreach ([[2, 2], [$lebar - 3, 2], [(int) ($lebar / 2), (int) ($tinggi / 4)]] as [$x, $y]) {
                $this->assertWarna([220, 0, 0], $hasil, $x, $y, "{$folder} top ({$x}, {$y})");
            }
            foreach ([[2, $tinggi - 3], [$lebar - 3, $tinggi - 3], [(int) ($lebar / 2), (int) ($tinggi * 3 / 4)]] as [$x, $y]) {
                $this->assertWarna([0, 0, 220], $hasil, $x, $y, "{$folder} bottom ({$x}, {$y})");
            }

            // Re-saving drops the EXIF data (SEC-51), so no viewer turns it again.
            $this->assertArrayNotHasKey('Orientation', @exif_read_data($path) ?: [], $folder);
        }
    }

    public function testTransparentPngGetsWhiteBackground(): void
    {
        $image = imagecreatetruecolor(300, 400);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagefilledrectangle($image, 100, 100, 199, 299, imagecolorallocatealpha($image, 0, 128, 0, 0));
        imagepng($image, $this->dir . '/transparan.png');

        $relatif = $this->simpan($this->dir . '/transparan.png');

        foreach (['foto/' => 1, 'foto/kiosk/' => 1, 'foto/kecil/' => 0.4] as $folder => $skala) {
            $path = WRITEPATH . 'uploads/' . $folder . basename($relatif);
            $this->assertSame([(int) (300 * $skala), (int) (400 * $skala), IMAGETYPE_JPEG], array_slice(getimagesize($path), 0, 3), $folder);

            $hasil = imagecreatefromjpeg($path);
            $this->assertWarna([255, 255, 255], $hasil, 2, 2, "{$folder} transparent corner");
            $this->assertWarna([0, 128, 0], $hasil, (int) (150 * $skala), (int) (200 * $skala), "{$folder} opaque middle");
        }
    }

    private function simpan(string $sumber): string
    {
        $relatif = (new Gambar())->simpanFoto($sumber);
        $this->assertNotNull($relatif);
        $this->simpanan[] = $relatif;

        return $relatif;
    }

    /**
     * Inserts an APP1 Exif segment with one Orientation tag right after SOI.
     */
    private function denganOrientasi(string $jpeg, int $orientasi): string
    {
        $this->assertStringStartsWith("\xFF\xD8", $jpeg);

        // Big-endian TIFF header, IFD0 at offset 8 with one SHORT entry, no next IFD.
        $tiff = "MM\x00\x2A" . pack('N', 8)
            . pack('n', 1)
            . pack('nnN', 0x0112, 3, 1) . pack('n', $orientasi) . "\x00\x00"
            . pack('N', 0);
        $isi = "Exif\x00\x00" . $tiff;

        return "\xFF\xD8" . "\xFF\xE1" . pack('n', strlen($isi) + 2) . $isi . substr($jpeg, 2);
    }

    /**
     * @param array{int, int, int} $harapan RGB
     */
    private function assertWarna(array $harapan, GdImage $image, int $x, int $y, string $pesan): void
    {
        $warna = imagecolorsforindex($image, imagecolorat($image, $x, $y));

        foreach (['red', 'green', 'blue'] as $i => $kanal) {
            // JPEG quality 85 changes colors a little.
            $this->assertEqualsWithDelta($harapan[$i], $warna[$kanal], 30, "{$pesan}: {$kanal}");
        }
    }
}
