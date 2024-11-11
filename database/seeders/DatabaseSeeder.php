<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use App\Models\Gejala;
use App\Models\Artikel;
use App\Models\Keputusan;
use App\Models\KondisiUser;
use App\Models\CertainFactor;
use PhpParser\Node\Expr\New_;
use App\Models\TingkatDepresi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(3)->create();
        // // Artikel::factory(4)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'admin',
        //     'email' => 'admin@example.com',
        //     'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        // ]);

        User::create([
            'name' => 'Adnan Ega',
            'email' => 'adnan@gmail.com',
            'password' => Hash::make('adnan394')
        ]);

        $keputusan = new Keputusan();
        $gejala = new Gejala();
        $depresi = new TingkatDepresi();
        $kondisi = new KondisiUser();

        $artikel = new Artikel();

        Keputusan::insert($keputusan->fillTable());
        Gejala::insert($gejala->fillTable());
        TingkatDepresi::insert($depresi->fillTable());
        KondisiUser::insert($kondisi->fillTable());
        Artikel::insert($artikel->fillTabel());
    }
}