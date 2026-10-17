<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Models\SiswaModel;
use App\Services\ImportSiswa;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class Siswa extends BaseController
{
    private const READER = ['xlsx' => 'Xlsx', 'xls' => 'Xls', 'csv' => 'Csv'];

    public function index(): string
    {
        $model = model(SiswaModel::class);
        $cari  = trim((string) $this->request->getGet('cari'));
        $kelas = trim((string) $this->request->getGet('kelas'));

        if ($cari !== '') {
            $model->groupStart()->like('nama', $cari)->orLike('nis', $cari)->groupEnd();
        }
        if ($kelas !== '') {
            $model->where('kelas', $kelas);
        }

        return view('panel/siswa/index', [
            'title'       => 'Siswa',
            'siswa'       => $model->orderBy('kelas')->orderBy('nama')->paginate(50),
            'pager'       => $model->pager,
            'cari'        => $cari,
            'kelas'       => $kelas,
            'daftarKelas' => model(SiswaModel::class)->daftarKelas(),
        ]);
    }

    public function tambah(): string
    {
        return view('panel/siswa/form', ['title' => 'Tambah siswa', 'siswa' => null]);
    }

    public function ubah(int $id): string
    {
        return view('panel/siswa/form', ['title' => 'Ubah siswa', 'siswa' => $this->cari($id)]);
    }

    public function simpan(): RedirectResponse
    {
        return $this->simpanData(null);
    }

    public function perbarui(int $id): RedirectResponse
    {
        return $this->simpanData((int) $this->cari($id)['id']);
    }

    public function hapus(int $id): RedirectResponse
    {
        $siswa = $this->cari($id);
        model(SiswaModel::class)->delete($siswa['id']);

        return redirect()->to(url_to('siswa'))->with('sukses', "{$siswa['nama']} sudah dihapus beserta data presensinya.");
    }

    public function import(): string
    {
        return view('panel/siswa/import', ['title' => 'Import siswa', 'galat' => []]);
    }

    public function prosesImport(): RedirectResponse|string
    {
        $file = $this->request->getFile('berkas');
        $ext  = $file === null ? '' : strtolower($file->getClientExtension());

        if ($file === null || ! $file->isValid() || ! isset(self::READER[$ext]) || $file->getSize() > 5 * 1024 * 1024) {
            return redirect()->to(url_to('siswa.import'))->with('galat', 'Pilih file Excel (.xlsx, .xls) atau .csv, paling besar 5 MB.');
        }

        try {
            $rows = IOFactory::createReader(self::READER[$ext])->load($file->getTempName())->getActiveSheet()->toArray(null, true, false, false);
        } catch (Throwable $e) {
            log_message('warning', 'Student import file unreadable: ' . $e->getMessage());

            return redirect()->to(url_to('siswa.import'))->with('galat', 'File tidak dapat dibaca. Simpan ulang sebagai .xlsx, lalu coba lagi.');
        }

        $galat = [];
        $hasil = (new ImportSiswa())->jalankan($rows, $galat);

        if ($galat !== []) {
            return view('panel/siswa/import', ['title' => 'Import siswa', 'galat' => $galat]);
        }

        return redirect()->to(url_to('siswa'))->with('sukses', "Import selesai: {$hasil['baru']} siswa baru, {$hasil['diperbarui']} siswa diperbarui.");
    }

    /**
     * Printable QR cards, 12 per A4 page. The QR holds the student's NIS.
     */
    public function kartu(): ResponseInterface
    {
        $kelas = trim((string) $this->request->getGet('kelas'));
        $model = model(SiswaModel::class);
        if ($kelas !== '') {
            $model->where('kelas', $kelas);
        }

        $qr    = new QRCode(new QROptions(['outputInterface' => QRGdImagePNG::class, 'scale' => 8]));
        $kartu = array_map(static fn (array $siswa): array => $siswa + ['qr' => $qr->render($siswa['nis'])], $model->orderBy('kelas')->orderBy('nama')->findAll());

        $mpdf = new Mpdf(['tempDir' => WRITEPATH . 'cache/mpdf', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
        $mpdf->SetTitle('Kartu QR siswa');
        $mpdf->WriteHTML(view('panel/siswa/kartu', ['kartu' => $kartu]));

        return $this->response->setBody($mpdf->OutputBinaryData())
            ->setContentType('application/pdf', '')
            ->setHeader('Content-Disposition', 'inline; filename="kartu-qr' . ($kelas === '' ? '' : '-' . preg_replace('/[^0-9A-Za-z]+/', '', $kelas)) . '.pdf"');
    }

    private function simpanData(?int $id): RedirectResponse
    {
        $data  = ['nis' => trim((string) $this->request->getPost('nis')), 'nama' => trim((string) $this->request->getPost('nama')), 'kelas' => trim((string) $this->request->getPost('kelas'))];
        $model = model(SiswaModel::class);

        if (! $model->save($id === null ? $data : $data + ['id' => $id])) {
            return redirect()->back()->withInput()->with('galat', implode(' ', $model->errors()));
        }

        return redirect()->to(url_to('siswa'))->with('sukses', "Data {$data['nama']} sudah disimpan.");
    }

    private function cari(int $id): array
    {
        return model(SiswaModel::class)->find($id) ?? throw PageNotFoundException::forPageNotFound();
    }
}
