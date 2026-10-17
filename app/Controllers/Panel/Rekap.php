<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Models\SiswaModel;
use App\Services\Presensi;
use App\Services\Sistem\Jam;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Rekap extends BaseController
{
    public function index(): string
    {
        [$tanggal, $kelas] = $this->filter();

        return view('panel/rekap', [
            'title'       => 'Rekap harian',
            'tanggal'     => $tanggal,
            'kelas'       => $kelas,
            'daftarKelas' => model(SiswaModel::class)->daftarKelas(),
            'rekap'       => (new Presensi())->rekap($tanggal, $kelas),
        ]);
    }

    public function ubah(): RedirectResponse
    {
        $siswa      = model(SiswaModel::class)->find((int) $this->request->getPost('siswa_id'));
        $tanggal    = (string) $this->request->getPost('tanggal');
        $status     = (string) $this->request->getPost('status');
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        $kembali    = url_to('rekap') . '?' . http_build_query(['tanggal' => $tanggal, 'kelas' => (string) $this->request->getPost('kelas')]);

        if ($siswa === null || ! self::tanggalValid($tanggal) || ($status !== '' && ! isset(Presensi::STATUS[$status]))) {
            return redirect()->to($kembali)->with('galat', 'Data belum lengkap. Muat ulang halaman, lalu coba lagi.');
        }
        if (mb_strlen($keterangan) > 255) {
            return redirect()->to($kembali)->with('galat', 'Keterangan paling panjang 255 karakter.');
        }

        (new Presensi())->ubah((int) $siswa['id'], $tanggal, $status, $keterangan);

        return redirect()->to($kembali . '#siswa-' . $siswa['id'])->with('sukses', "Status {$siswa['nama']} sudah disimpan.");
    }

    public function excel(): ResponseInterface
    {
        [$tanggal, $kelas] = $this->filter();
        $rekap             = (new Presensi())->rekap($tanggal, $kelas);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Rekap');
        $sheet->fromArray([
            ['Rekap Presensi Harian ' . config('Spensada')->namaSekolah],
            [format_date($tanggal, 'full') . ($kelas === '' ? '' : ' · Kelas ' . $kelas) . ($rekap['libur'] === null ? '' : ' · Libur: ' . $rekap['libur'])],
            [],
            ['No', 'NIS', 'Nama', 'Kelas', 'Status', 'Jam scan', 'Keterangan'],
        ]);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);

        $row = 5;
        foreach ($rekap['baris'] as $i => $siswa) {
            $sheet->fromArray([$i + 1, null, $siswa['nama'], $siswa['kelas'], Presensi::STATUS[$siswa['status']] ?? '-', format_time($siswa['jam_scan']), $siswa['keterangan']], null, 'A' . $row);
            // As text, so leading zeros stay.
            $sheet->setCellValueExplicit('B' . $row, $siswa['nis'], DataType::TYPE_STRING);
            $row++;
        }

        $row++;
        foreach ($this->ringkasan($rekap['jumlah']) as $label => $jumlah) {
            $sheet->fromArray([$label, $jumlah], null, 'C' . $row++);
        }
        foreach (range('A', 'G') as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');

        return $this->response->setBody((string) ob_get_clean())
            ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $this->namaFile($tanggal, $kelas) . '.xlsx"');
    }

    public function pdf(): ResponseInterface
    {
        [$tanggal, $kelas] = $this->filter();
        $rekap             = (new Presensi())->rekap($tanggal, $kelas);

        $mpdf = new Mpdf(['tempDir' => WRITEPATH . 'cache/mpdf', 'format' => 'A4', 'margin_top' => 12, 'margin_bottom' => 12]);
        $mpdf->SetTitle('Rekap presensi ' . $tanggal);
        $mpdf->WriteHTML(view('panel/rekap_pdf', [
            'tanggal'   => $tanggal,
            'kelas'     => $kelas,
            'rekap'     => $rekap,
            'ringkasan' => $this->ringkasan($rekap['jumlah']),
        ]));

        return $this->response->setBody($mpdf->OutputBinaryData())
            ->setContentType('application/pdf', '')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $this->namaFile($tanggal, $kelas) . '.pdf"');
    }

    public static function tanggalValid(string $tanggal): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);

        return $date !== false && $date->format('Y-m-d') === $tanggal;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function filter(): array
    {
        $tanggal = (string) $this->request->getGet('tanggal');

        return [self::tanggalValid($tanggal) ? $tanggal : (new Jam())->today(), trim((string) $this->request->getGet('kelas'))];
    }

    /**
     * @return array<string, int>
     */
    private function ringkasan(array $jumlah): array
    {
        $hasil = [];
        foreach (Presensi::STATUS as $kode => $label) {
            $hasil[$label] = $jumlah[$kode];
        }

        return $hasil + ['Belum ada data' => $jumlah['belum'], 'Jumlah siswa' => array_sum($jumlah)];
    }

    private function namaFile(string $tanggal, string $kelas): string
    {
        $kelas = preg_replace('/[^0-9A-Za-z]+/', '', $kelas);

        return 'rekap-presensi-' . str_replace('-', '', $tanggal) . ($kelas === '' ? '' : '-' . $kelas);
    }
}
