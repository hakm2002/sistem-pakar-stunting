<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KondisiUser extends Model
{
    use HasFactory;
    // protected $table = 'kondisi_users';

    public function fillTable()
    {
        $cf_user = [
            [
                'kondisi' => 'Pasti Tidak',
                'nilai' => -1,
            ],
            [
                'kondisi' => 'Hampir Pasti Tidak',
                'nilai' => -0.8,
            ],
            [
                'kondisi' => 'Kemungkinan Tidak',
                'nilai' => -0.6,
            ],
            [
                'kondisi' => 'Mungkin Tidak',
                'nilai' => -0.4,
            ],
            [
                'kondisi' => 'Tidak Tahu',
                'nilai' => -0.2,
            ],
            [
                'kondisi' => 'Mungkin',
                'nilai' => 0.4,
            ],
            [
                'kondisi' => 'Kemungkinan benar',
                'nilai' => 0.6,
            ],
            [
                'kondisi' => 'Hampir Pasti ',
                'nilai' => 0.8,
            ],
            [
                'kondisi' => 'Pasti ',
                'nilai' => 1,
            ]
        ];
        return $cf_user;
    }
}