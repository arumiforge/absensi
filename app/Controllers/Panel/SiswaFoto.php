<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\Berkas\Gambar;
use App\Services\Berkas\Penyaji;
use App\Services\MasterData\FotoSiswa;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Router\Attributes\Filter;

/**
 * One student's photo (docs/09 HAL-MD-09, docs/04 FS-MD-07): the photo
 * file (docs/09 §13) and the upload form. Saving follows docs/07 ARS-53
 * through FotoSiswa; foto massal (HAL-MD-15) uses the same service.
 */
class SiswaFoto extends BaseController
{
    /**
     * The standard photo, or the small one with `?ukuran=kecil` (docs/08
     * UI-22). 404 when the student has no photo (docs/09 §13 rule 3).
     */
    #[Filter(by: 'hak', having: ['HA-MD-05'])]
    public function index(int $id): ResponseInterface
    {
        $file = (string) Siswa::siswaDalamCakupan($id, 'HA-MD-05')['foto_file'];

        if ($file === '') {
            throw PageNotFoundException::forPageNotFound();
        }
        // docs/11 VAL-13: other values are ignored, so the standard photo is sent.
        if ($this->request->getGet('ukuran') === 'kecil') {
            $file = 'foto/kecil/' . basename($file);
        }

        try {
            return (new Penyaji())->tampilkan($file);
        } catch (PageNotFoundException $e) {
            // docs/11 §7: a photo referenced by the database but missing on disk.
            log_message('error', 'Student photo file missing: {file} (siswa {id})', ['file' => $file, 'id' => $id]);

            throw $e;
        }
    }

    #[Filter(by: 'hak', having: ['HA-MD-07'])]
    public function ubah(int $id): string
    {
        helper('form');
        $siswa = Siswa::siswaDalamCakupan($id, 'HA-MD-07');

        return view('panel/siswa_foto/ubah', [
            'title'   => 'Foto siswa',
            'siswa'   => $siswa,
            'urlFoto' => ($siswa['foto_file'] ?? '') === '' ? null : url_to('panel.siswa_foto.index', $id),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-07'])]
    public function ganti(int $id): RedirectResponse
    {
        $siswa = Siswa::siswaDalamCakupan($id, 'HA-MD-07');
        $file  = $this->request->getFile('foto');
        $pesan = null;

        if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
            // docs/11 GAL-11 item 3; the content checks run in FotoSiswa::ganti().
            $pesan = $file === null ? '' : (string) (new Gambar())->periksaUnggahan($file);
            $pesan = $pesan === '' ? 'Pilih file.' : $pesan;
        } else {
            $hasil = (new FotoSiswa())->ganti($id, $file->getTempName(), $file->getClientName(), service('akunAktif')->id());
            $pesan = $hasil['galat'] ?? null;
        }

        if ($pesan !== null) {
            // docs/11 §5.2 FS-MD-07 E1.
            if ($pesan !== 'Pilih file.' && ($siswa['foto_file'] ?? '') !== '') {
                $pesan .= ' Foto lama tetap dipakai.';
            }

            return redirect()->to(url_to('panel.siswa_foto.ubah', $id), 303)
                ->with('_ci_validation_errors', ['foto' => $pesan]);
        }

        return redirect()->to(url_to('panel.siswa.lihat', $id), 303)->with('sukses', $hasil['ada_foto_lama']
            ? "Foto {$siswa['nama']} sudah diganti."
            : "Foto {$siswa['nama']} sudah disimpan.");
    }
}
