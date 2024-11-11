<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gejala extends Model
{
    use HasFactory;
    protected $table = 'gejala';
    protected $guard = ["id"];
    protected $fillable = ["kode_gejala", "gejala"];

    public function fillTable()
    {


        $gejala2 = [
            [
                "kode_gejala" => "G01",
                "gejala" => "Terlihat pendek dari anak seusianya ataupun lebih kurus"
            ],
            [
                "kode_gejala" => "G02",
                "gejala" => "Gangguan dalam berbicara/sulit untuk berbicara"
            ],
            [
                "kode_gejala" => "G03",
                "gejala" => "Pertumbuhan dan perkembangan melambat"
            ],
            [
                "kode_gejala" => "G04",
                "gejala" => "Penurunan tingkat kecerdasan"
            ],
            [
                "kode_gejala" => "G05",
                "gejala" => "Sistem kekebalan tubuh lebih rendah"
            ],
            [
                "kode_gejala" => "G06",
                "gejala" => "Berat badan tidak bertambah bahkan cenderung turun"
            ],
            [
                "kode_gejala" => "G07",
                "gejala" => "Wajah terlihat lebih muda dari anak seusianya"
            ],
            [
                "kode_gejala" => "G08",
                "gejala" => "Fase pertumbuhan gigi terhambat"
            ],
            [
                "kode_gejala" => "G09",
                "gejala" => "Kemampuan fokus dan memori dalam belajar kurang baik"
            ],
            [
                "kode_gejala" => "G10",
                "gejala" => "Pertumbuhan tulang terhambat, sehingga terlihat lebih pendek"
            ],
            [
                "kode_gejala" => "G11",
                "gejala" => "Mudah terkena penyakit infeksi"
            ],
            [
                "kode_gejala" => "G12",
                "gejala" => "Sesak nafas"
            ],
            [
                "kode_gejala" => "G13",
                "gejala" => "Kulit kering"
            ],
            [
                "kode_gejala" => "G14",
                "gejala" => "Otot mengecil"
            ]
        ];

        return $gejala2;
    }
}