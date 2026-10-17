<?php

namespace App\Models;

use CodeIgniter\Model;

class SiswaModel extends Model
{
    protected $table           = 'siswa';
    protected $allowedFields   = ['nis', 'nama', 'kelas'];
    protected $useTimestamps   = true;
    protected $validationRules = [
        'id'    => 'permit_empty|is_natural_no_zero',
        'nis'   => 'required|max_length[20]|regex_match[/^[0-9A-Za-z.\-]+$/]|is_unique[siswa.nis,id,{id}]',
        'nama'  => 'required|max_length[100]',
        'kelas' => 'required|max_length[20]',
    ];
    protected $validationMessages = [
        'nis' => [
            'required'    => 'NIS belum diisi.',
            'max_length'  => 'NIS paling panjang 20 karakter.',
            'regex_match' => 'NIS hanya boleh berisi huruf, angka, titik, atau tanda minus.',
            'is_unique'   => 'NIS ini sudah dipakai siswa lain.',
        ],
        'nama'  => ['required' => 'Nama belum diisi.', 'max_length' => 'Nama paling panjang 100 karakter.'],
        'kelas' => ['required' => 'Kelas belum diisi.', 'max_length' => 'Kelas paling panjang 20 karakter.'],
    ];

    /**
     * @return list<string>
     */
    public function daftarKelas(): array
    {
        return array_column($this->builder()->distinct()->select('kelas')->orderBy('kelas')->get()->getResultArray(), 'kelas');
    }
}
