<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Product extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'price',
        'rating',
        'color',
        'size',
        'operatingSystem',
        'brand',
        'stock_quantity',
        'imgId',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
