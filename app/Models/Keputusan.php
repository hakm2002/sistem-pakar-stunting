<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keputusan extends Model
{
    use HasFactory;
    protected $table = 'keputusan';
    protected $guard = ["id"];

    public function depresi()
    {
        return $this->hasMany(TingkatDepresi::class, 'kode_depresi', 'kode_depresi');
    }

    public function gejala()
    {
        return $this->hasMany(Gejala::class, 'kode_gejala' /* tbl gejala */, 'kode_gejala');
    }

    public function fillTable()
    {
        $rule = [
            // P001 => Gangguan Mood
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G01',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G02',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G03',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G04',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G05',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G06',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G07',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G08',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G09',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G10',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G11',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G12',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G13',
                'mb' => 0.6,
                'md' => 0.2
            ],
            [
                'kode_depresi' => 'P01',
                'kode_gejala' => 'G14',
                'mb' => 0.6,
                'md' => 0.2
            ]
        ];
        return $rule;
    }
}