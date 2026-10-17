<?php

namespace App\Services;

use App\Models\SiswaModel;

/**
 * Student import from spreadsheet rows: column A NIS, B name, C class.
 * Existing NIS are updated. Any error rolls back the whole file.
 */
class ImportSiswa
{
    /**
     * @param list<list<mixed>> $rows
     * @param list<string>      $galat Filled with one message per bad row.
     *
     * @return array{baru: int, diperbarui: int}
     */
    public function jalankan(array $rows, array &$galat): array
    {
        $model      = model(SiswaModel::class);
        $db         = db_connect();
        $dilihat    = [];
        $baru       = 0;
        $diperbarui = 0;

        $db->transBegin();

        foreach ($rows as $i => $row) {
            $nomor              = $i + 1;
            [$nis, $nama, $kelas] = array_map(self::teks(...), array_pad(array_slice($row, 0, 3), 3, null));

            // Empty rows, and a header row (no digit in the NIS column).
            if (($nis === '' && $nama === '' && $kelas === '') || ($i === 0 && preg_match('/\d/', $nis) !== 1)) {
                continue;
            }
            if (isset($dilihat[$nis])) {
                $galat[] = "Baris {$nomor}: NIS {$nis} sudah ada di baris {$dilihat[$nis]}.";

                continue;
            }
            $dilihat[$nis] = $nomor;

            $ada  = $nis === '' ? null : $model->where('nis', $nis)->first();
            $data = ['nis' => $nis, 'nama' => $nama, 'kelas' => $kelas] + ($ada === null ? [] : ['id' => $ada['id']]);

            if (! $model->save($data)) {
                $galat[] = "Baris {$nomor}: " . implode(' ', $model->errors());

                continue;
            }
            $ada === null ? $baru++ : $diperbarui++;
        }

        if ($galat === [] && $dilihat === []) {
            $galat[] = 'File tidak berisi data siswa. Isi kolom A NIS, B Nama, C Kelas.';
        }

        $galat === [] ? $db->transCommit() : $db->transRollback();

        return ['baru' => $baru, 'diperbarui' => $diperbarui];
    }

    private static function teks(mixed $value): string
    {
        // Excel stores NIS typed as a number as a float.
        return is_float($value) && floor($value) === $value ? sprintf('%.0f', $value) : trim((string) $value);
    }
}
