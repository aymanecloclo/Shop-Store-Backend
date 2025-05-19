<?php

namespace App\Models;
use App\Models\CartItem;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id', 'session_id', 'total', 'status'];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }
    public function updateTotal()
    {
        $this->total = $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        $this->save();
    }
}

?>