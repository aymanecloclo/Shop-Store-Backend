<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    
        $categories = [
            ['name' => 'Accessoires', 'icon_name' => 'FaHeadphones', 'href' => '/accessoires'],
            ['name' => 'PC Portable', 'icon_name' => 'FaLaptop', 'href' => '/pc-portable'],
            ['name' => 'Téléphone', 'icon_name' => 'SlScreenSmartphone', 'href' => '/telephone'],
            ['name' => 'Jeu Vidéo', 'icon_name' => 'FaGamepad', 'href' => '/jeu-video'],
            ['name' => 'PC Gamer', 'icon_name' => 'FaDesktop', 'href' => '/pc-gamer'],
        ];

        DB::table('categories')->insert($categories);
    }
}
