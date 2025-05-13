<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Récupère le panier de l'utilisateur
     */
    public function index()
    {
        $cartItems = Auth::user()
            ->cartItems()
            ->with('product') // Charge les relations produit
            ->get();

        return response()->json([
            'success' => true,
            'cart' => $cartItems->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'name' => $item->product->name,
                    'image' => $item->product->image_url,
                    // Ajoutez d'autres champs nécessaires
                ];
            })
        ]);
    }

    /**
     * Synchronise le panier complet
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cartItems' => 'required|array',
            'cartItems.*.product_id' => 'required|exists:products,id',
            'cartItems.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Supprime les anciens items
        Auth::user()->cartItems()->delete();

        // Ajoute les nouveaux items
        foreach ($request->cartItems as $item) {
            Auth::user()->cartItems()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart synchronized successfully'
        ]);
    }

    /**
     * Ajoute un produit au panier
     */
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $cartItem = Auth::user()->cartItems()
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            // Met à jour la quantité si le produit existe déjà
            $cartItem->increment('quantity', $request->quantity);
        } else {
            // Crée un nouvel item
            Auth::user()->cartItems()->create([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart'
        ]);
    }

    /**
     * Met à jour la quantité d'un produit
     */
    public function update(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updated = Auth::user()->cartItems()
            ->where('product_id', $productId)
            ->update(['quantity' => $request->quantity]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in cart'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully'
        ]);
    }

    /**
     * Supprime un produit du panier
     */
    public function remove($productId)
    {
        $deleted = Auth::user()->cartItems()
            ->where('product_id', $productId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in cart'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product removed from cart'
        ]);
    }

    /**
     * Vide complètement le panier
     */
    public function clear()
    {
        Auth::user()->cartItems()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ]);
    }
}
