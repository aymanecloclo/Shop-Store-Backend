<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Afficher la liste des catégories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Récupérer toutes les catégories
        $categories = Category::all();

        return response()->json([
            'message' => 'Liste des catégories',
            'data' => $categories
        ]);
    }
}
