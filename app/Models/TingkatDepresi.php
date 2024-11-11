<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TingkatDepresi extends Model
{
    use HasFactory;
    protected $table = 'tingkat_depresi';
    protected $guard = ["id"];
    protected $fillable = ['kode_depresi', 'depresi'];

    public function fillTable()
    {
        $depresi = [
            [
                "kode_depresi" => "P01",
                "depresi" => "Stunting"
            ],
            [
                "kode_depresi" => "P02",
                "depresi" => "Tidak Stunting"
            ]
        ];
        return $depresi;
    }
}