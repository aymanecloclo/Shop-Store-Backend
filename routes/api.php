<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

use Stripe\Stripe;

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
Route::post('/stripe/verify-payment', [PaymentController::class, 'verifyPayment']);
// In routes/web.php
// routes/api.php
Route::middleware('auth:sanctum')->get('/orders', [PaymentController::class, 'getAllOrders']);


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
 
    Route::post('/stripe/checkout', [PaymentController::class, 'createCheckoutSession']);
    Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook']);

    Route::get('/payment/success', [PaymentController::class, 'handleSuccess'])->name('payment.success');
    Route::get('/payment/cancel', [PaymentController::class, 'handleCancel'])->name('payment.cancel');

//     Route::post('/stripe/checkout', function (Request $request) {
//         Stripe::setApiKey(env('STRIPE_SECRET'));
    
//         $session = \Stripe\Checkout\Session::create([
//             'payment_method_types' => ['card', 'bancontact', 'ideal'], // Méthodes de paiement
//             'line_items' => $request->items, // Produits du panier
//             'mode' => 'payment',
//             'success_url' => env('FRONTEND_URL') . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
//             'cancel_url' => env('FRONTEND_URL') . '/cart',
//             'customer_email' => $request->user()->email, // Email du client
//         ]);
    
//         return response()->json(['sessionId' => $session->id]);
//     });
//     // routes/api.php
// Route::post('/stripe/verify', function (Request $request) {
//     $session = \Stripe\Checkout\Session::retrieve($request->sessionId);
    
//     if ($session->payment_status === 'paid') {
//         // Enregistrer la commande en BDD
//         Order::create([...]);
//         return response()->json(['status' => 'success']);
//     }

//     return response()->json(['status' => 'unpaid'], 400);
// });
    // // Gestion des commandes (Order)
    // Route::get('/orders', [OrderController::class, 'index']); // Liste des commandes de l'utilisateur
    // Route::get('/orders/{id}', [OrderController::class, 'show']); // Détails d'une commande
    // Route::post('/orders/create', [OrderController::class, 'create']); // Passer une commande

    // // Paiements (Payment)
    // Route::post('/payments', [PaymentController::class, 'processPayment']); // Effectuer un paiement
    // Route::get('/payments/{order_id}', [PaymentController::class, 'getPaymentStatus']); // Statut d'un paiement
});
