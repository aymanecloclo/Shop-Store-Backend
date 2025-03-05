<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Afficher la liste des produits.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Récupérer tous les produits
        $products = Product::all();

        return response()->json([
            'message' => 'Liste des produits',
            'data' => $products
        ]);
    }

    /**
     * Afficher un produit spécifique.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Trouver le produit
        $product = Product::find($id);

        // Vérifier si le produit existe
        if (!$product) {
            return response()->json(['error' => 'Produit non trouvé'], 404);
        }

        return response()->json([
            'message' => 'Détails du produit',
            'data' => $product
        ]);
    }
}
