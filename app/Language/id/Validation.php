<?php

/**
 * Indonesian text for CI4's built-in validation rules.
 * {field} is the on-screen label of the field, never its technical name.
 * The core messages (noRuleSets, ruleNotFound, ...) are developer errors
 * and stay in English.
 */
return [
    'alpha'                 => '{field} hanya boleh berisi huruf.',
    'alpha_dash'            => '{field} hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.',
    'alpha_numeric'         => '{field} hanya boleh berisi huruf dan angka.',
    'alpha_numeric_punct'   => '{field} hanya boleh berisi huruf, angka, spasi, dan tanda ~ ! # $ % & * - _ + = | : .',
    'alpha_numeric_space'   => '{field} hanya boleh berisi huruf, angka, dan spasi.',
    'alpha_space'           => '{field} hanya boleh berisi huruf dan spasi.',
    'decimal'               => '{field} harus berupa angka.',
    'differs'               => '{field} harus berbeda dengan {param}.',
    'equals'                => '{field} harus bernilai {param}.',
    'exact_length'          => '{field} harus tepat {param} karakter.',
    'field_exists'          => '{field} wajib diisi.',
    'greater_than'          => '{field} harus lebih dari {param}.',
    'greater_than_equal_to' => '{field} paling sedikit {param}.',
    'hex'                   => '{field} hanya boleh berisi angka 0 sampai 9 dan huruf A sampai F.',
    'in_list'               => '{field} yang dipilih tidak tersedia. Pilih dari daftar.',
    'integer'               => '{field} harus berupa bilangan bulat.',
    'is_natural'            => '{field} hanya boleh berisi angka.',
    'is_natural_no_zero'    => '{field} hanya boleh berisi angka, dan lebih dari 0.',
    'is_not_unique'         => '{field} yang dipilih sudah tidak ada. Pilih lagi.',
    'is_unique'             => '{field} sudah dipakai.',
    'less_than'             => '{field} harus kurang dari {param}.',
    'less_than_equal_to'    => '{field} paling banyak {param}.',
    'matches'               => '{field} tidak sama dengan {param}.',
    'max_length'            => '{field} paling panjang {param} karakter.',
    'min_length'            => '{field} paling sedikit {param} karakter.',
    'not_equals'            => '{field} tidak boleh bernilai {param}.',
    'not_in_list'           => '{field} yang dipilih tidak dapat dipakai. Pilih yang lain.',
    'numeric'               => '{field} hanya boleh berisi angka.',
    'regex_match'           => 'Penulisan {field} belum sesuai. Periksa lagi.',
    'required'              => '{field} wajib diisi.',
    'required_with'         => '{field} wajib diisi bila {param} diisi.',
    'required_without'      => '{field} wajib diisi bila {param} tidak diisi.',
    'string'                => '{field} harus berupa teks.',
    'timezone'              => '{field} harus berupa zona waktu yang benar.',
    'valid_base64'          => '{field} tidak dapat dibaca. Muat ulang halaman, lalu coba lagi.',
    'valid_email'           => '{field} harus berupa alamat email yang benar.',
    'valid_emails'          => 'Setiap alamat di {field} harus berupa alamat email yang benar.',
    'valid_ip'              => '{field} harus berupa alamat IP yang benar.',
    'valid_url'             => '{field} harus berupa alamat web yang benar.',
    'valid_url_strict'      => '{field} harus berupa alamat web yang benar.',
    'valid_date'            => '{field} tidak valid.',
    'valid_json'            => '{field} tidak dapat dibaca. Muat ulang halaman, lalu coba lagi.',

    // Credit cards
    'valid_cc_number' => '{field} bukan nomor kartu kredit yang benar.',

    // Files
    'uploaded' => '{field} gagal diunggah. Pilih file lagi.',
    'max_size' => '{field} terlalu besar. Pilih file yang lebih kecil.',
    'is_image' => '{field} harus berupa gambar.',
    'mime_in'  => 'Format file {field} tidak diizinkan.',
    'ext_in'   => 'Format file {field} tidak diizinkan.',
    'max_dims' => '{field} bukan gambar, atau ukurannya terlalu besar.',
    'min_dims' => '{field} bukan gambar, atau ukurannya terlalu kecil.',
];
