<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Spensada extends BaseConfig
{
    /**
     * App version, appended to asset URLs as `?v=`.
     */
    public string $versi = '1.0.0';

    /**
     * Shown on the login page, recaps, and QR cards.
     */
    public string $namaSekolah = 'SMP 1 Dawe';
}
