<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
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

    foreach ($categories as $category) {
      DB::table('categories')->insert($category);
    }

    // Insertion des produits avec des références de catégories
    $productsAll = [
      [
        'id' => 1,
        'category_id' => 1, // Référence à la catégorie 'PC Portable'
        'name' => 'HP 6F817EA',
        'price' => 11499,
        'rating' => 4,
        'color' => 'Black',
        'size' => 'Medium',
        'operatingSystem' => 'Windows',
        'brand' => 'Dell',
        'imgId' => 'xzelkgv4gyv9grdqobq7',
      ],
      [
        'id' => 2,
        'category_id' => 2, // Référence à la catégorie 'PC Gamer'
        'name' => 'SG PCG ASTRO',
        'price' => 3749,
        'rating' => 5,
        'color' => 'Red',
        'size' => 'Large',
        'operatingSystem' => 'Windows',
        'brand' => 'Alienware',
        'imgId' => 'xzelkgv4gyv9grdqobq7',
      ],
      // Ajoutez d'autres produits ici
    ];

    DB::table('products')->insert($productsAll);
  }
}
