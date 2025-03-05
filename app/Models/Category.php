<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'href', 'icon_name'];

    /**
     * Relation One-to-Many : Une catégorie a plusieurs produits.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
