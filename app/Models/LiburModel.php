<?php

namespace App\Models;

use CodeIgniter\Model;

class LiburModel extends Model
{
    protected $table            = 'libur';
    protected $primaryKey       = 'tanggal';
    protected $useAutoIncrement = false;
    protected $allowedFields    = ['tanggal', 'keterangan'];
}
