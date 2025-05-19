<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;


/**
 * Routes publiques (Accessibles sans authentification)
 */ 

Route::get('/products', [ProductController::class, 'index']); // Liste des produits
Route::get('/products/{id}', [ProductController::class, 'show']); // Détails d'un produit
Route::get('/categories', [CategoryController::class, 'index']); // Liste des catégories
Route::get('/categories/{id}', [CategoryController::class, 'show']); // Détails d'une catégorie

// Authentification
Route::post('/login', [AuthController::class, 'login']);  // Connexion
Route::post('/register', [AuthController::class, 'register']);  // Inscription



/**
 * Routes protégées (Requiert une authentification avec Sanctum)
 */
Route::middleware('auth:sanctum')->group(function () {
    // Utilisateur connecté
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);  // Déconnexion

    // // Gestion du panier (Cart)
    // Route::get('/cart', [CartController::class, 'index']); // Voir le panier
    // Route::post('/cart/sync', [CartController::class, 'sync']); // Ajouter au panier
    // Route::put('/cart/update/{id}', [CartController::class, 'update']); // Mettre à jour le panier
    // Route::delete('/cart/remove/{id}', [CartController::class, 'remove']); // Supprimer un produit du panier
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/cart/sync', [CartController::class, 'sync']);
    Route::post('/cart/merge', [CartController::class, 'mergeCarts']);
    Route::post('/create-checkout-session', [PaymentController::class, 'createCheckoutSession']);
    Route::get('/check-payment-status', [PaymentController::class, 'checkPaymentStatus']);
    // // Gestion des commandes (Order)
    // Route::get('/orders', [OrderController::class, 'index']); // Liste des commandes de l'utilisateur
    // Route::get('/orders/{id}', [OrderController::class, 'show']); // Détails d'une commande
    // Route::post('/orders/create', [OrderController::class, 'create']); // Passer une commande

    // // Paiements (Payment)
    // Route::post('/payments', [PaymentController::class, 'processPayment']); // Effectuer un paiement
    // Route::get('/payments/{order_id}', [PaymentController::class, 'getPaymentStatus']); // Statut d'un paiement
});
