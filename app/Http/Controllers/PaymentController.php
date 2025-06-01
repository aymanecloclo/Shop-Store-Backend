<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\Payment;
// use App\Models\Notifications\OrderConfirmation;
use Illuminate\Support\Facades\DB; 




class PaymentController extends Controller
{
    // public function createCheckoutSession(Request $request)
    // {
    //     Stripe::setApiKey('sk_test_51RQvvN035Yc6VQc7s5cFX8fy5DkhaZPxRyIyne2CDtA0AOEWa5UCAOzsaE3m1JBgoxAjz0L1CDe5bZpUm63G9Sp900omHvc3Un');

    //     try {
    //         $lineItems = [];
    //         foreach ($request->items as $item) {
    //             $lineItems[] = [
    //                 'price_data' => [
    //                     'currency' => $item['price_data']['currency'] ?? 'mad',
    //                     'product_data' => [
    //                         'name' => $item['price_data']['product_data']['name'],
    //                     ],
    //                     'unit_amount' => $item['price_data']['unit_amount'],
    //                 ],
    //                 'quantity' => $item['quantity'],
    //             ];
    //         }

    //         $session = Session::create([
    //             'payment_method_types' => ['card'],
    //             'line_items' => $lineItems,
    //             'mode' => 'payment',
    //             'success_url' => 'http://localhost:5173' . '/success?session_id={CHECKOUT_SESSION_ID}',
    //             'cancel_url' => 'http://localhost:5173' . '/canceled',
    //         ]);
    //         $order = Order::create([
    //             'user_id' => $request->user_id,
    //             'total' => 'pending',
    //             'stripe_session_id	' => $session->id,
    //         ]);


    //         try {
    //             $lineItems = [];
    //             $totalAmount = 0;

    //             foreach ($request->items as $item) {
    //             OrderDetail::create([
    //                 'order_id' => $order->id,
    //                 'product_id' => $item['price_data']['product_data']['id'] ?? null, // si tu as l'id produit
    //                 'quantity' => $item['quantity'],
    //                 'unit_price' => $item['price_data']['unit_amount'] / 100, // en MAD
    //                 'total_price' => ($item['price_data']['unit_amount'] / 100) * $item['quantity'],
    //             ]);
    //         }

    //         $orderDetails = Order::create([
    //                 'order_id' =>    $currentOrder = Order::findOrfail($order->id),
    //                 'items' => $lineItems,
    //                 'total' => 'pending',
    //                 'stripe_session_id	' => $session->id,
    //             ]);
    //         }

    //         return response()->json(['sessionId' => $session->id]);
    //     } catch (\Exception $e) {
    //         Log::error('Stripe checkout error: ' . $e->getMessage());
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }


    public function createCheckoutSession(Request $request)
    {
        Stripe::setApiKey('sk_test_51RQvvN035Yc6VQc7s5cFX8fy5DkhaZPxRyIyne2CDtA0AOEWa5UCAOzsaE3m1JBgoxAjz0L1CDe5bZpUm63G9Sp900omHvc3Un');

        DB::beginTransaction();
        try {
            // Validate request data
            $request->validate([
                'items' => 'required|array',
                'user_id' => 'required|exists:users,id',
                'items.*.price_data.product_data.name' => 'required',
                'items.*.price_data.unit_amount' => 'required|numeric',
                'items.*.quantity' => 'required|integer|min:1',
            ]);
         
            // Calculate total amount
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += ($item['price_data']['unit_amount'] * $item['quantity']);
            }

            // Create the order first
            $order = Order::create([
                'user_id' => $request->user_id,
                'total' => $totalAmount / 100, // Convert from cents to MAD
                'status' => 'pending',
                'stripe_session_id' => null, // Will be updated after Stripe session creation
            ]);
       
            // Create order items
            $lineItems = [];
            foreach ($request->items as $item) {
                $productData = $item['price_data']['product_data'];
                $product = Product::findOrFail($item['product_id']);
                // Vérification du stock
                // if ($product->stock < $item['quantity']) {
                //     // Retourner une réponse 422 (Unprocessable Entity) au lieu d'une 500
                //     return response()->json([
                //         'error' => 'stock_insufficient',
                //         'message' => 'Stock insuffisant pour le produit: ' . $product->name,
                //         'product_id' => $product->id,
                //         'available_stock' => $product->stock,
                //         'requested_quantity' => $item['quantity']
                //     ], 422);
                // }

                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price_data']['unit_amount'] / 100,
                    'total_price' => ($item['price_data']['unit_amount'] / 100) * $item['quantity'],
                ]);

                // Prepare Stripe line items
                $lineItems[] = [
                    'price_data' => [
                        'currency' => $item['price_data']['currency'] ?? 'mad',
                        'product_data' => [
                            'name' => $productData['name'],
                            'metadata' => [
                                'product_id' => $productData['product_id'] ?? null,
                            ],
                        ],
                        'unit_amount' => $item['price_data']['unit_amount'],
                    ],
                    'quantity' => $item['quantity'],
                ];
            }

            // Create Stripe checkout session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => 'http://localhost:5173/success?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order->id,
                'cancel_url' => 'http://localhost:5173/canceled',
                'metadata' => ['order_id' => $order->id] // Add metadata
            ]);

            // Update order with Stripe session ID
            $order->update([
                'stripe_session_id' => $session->id
            ]);

            DB::commit();
            // Après création de la session Stripe:
            $payment = Payment::create([
                'order_id' => $order->id,
                'stripe_session_id' => $session->id,
                'amount' => $totalAmount / 100,
                'currency' => 'mad',
                'status' => 'pending',
                'metadata' => [
                    'products' => $request->items,
                    'user_id' => $request->user_id
                ]
            ]);

            return response()->json([
                'sessionId' => $session->id,
                'paymentId' => $payment->id,
                'orderId' => $order->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stripe checkout error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            Stripe::setApiKey('sk_test_51RQvvN035Yc6VQc7s5cFX8fy5DkhaZPxRyIyne2CDtA0AOEWa5UCAOzsaE3m1JBgoxAjz0L1CDe5bZpUm63G9Sp900omHvc3Un');

            $order = Order::with('payment')->findOrFail($request->order_id);

            if ($order->payment->stripe_session_id !== $request->session_id) {
                throw new \Exception("Session ID mismatch");
            }
            // 2. Vérifier que l'ID de session correspond
            if ($order->stripe_session_id !== $request->session_id) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Session ID does not match order record'
                ], 400);
            }
            // // 3. Récupérer les infos de session depuis Stripe
            // $session = Session::retrieve($order->stripe_session_id);

            $session = Session::retrieve($order->payment->stripe_session_id);
            // $paymentIntent = PaymentIntent::retrieve($session->payment_intent);
            // Mise à jour du paiement
            $order->payment->update([
                'stripe_payment_intent_id' => $session->payment_intent,
                'status' => $session->payment_status,
                'payment_method' => $paymentIntent->payment_method_types[0] ?? null,
                'paid_at' => $session->payment_status === 'paid' ? now() : null,
            ]);

            if ($session->payment_status === 'paid') {
                // Mettre à jour la commande
                $order->update([
                    'status' => 'paid',
                    'payment_intent' => $session->payment_intent,
                    'payment_method' => $session->payment_method_types[0] ?? 'card',
                    'paid_at' => now(),
                ]);
                if ($session->payment_status === 'paid') {
                    $order->update(['status' => 'paid']);
                    // Mettre à jour le stock des produits
                    foreach ($order->orderDetails as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->decrement('stock_quantity', $item->quantity);
                            $product->save();
                        }
                    }

                    // Mise à jour du stock...
                }

      
                // // Envoyer une notification
                // $order->user->notify(new OrderConfirmation($order));

                return response()->json([
                    'status' => $session->payment_status,
                    'payment' => $order->payment,
                    'order' => $order
                ]);
            }

            return response()->json(['status' => 'failed'], 400);
        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response('Webhook Error', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session);
                break;
            case 'payment_intent.succeeded':
                // Gérer d'autres événements si nécessaire
                break;
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        // Récupérer l'order_id depuis les metadata
        $orderId = $session->metadata->order_id ?? null;

        if ($orderId && $session->payment_status === 'paid') {
            $order = Order::find($orderId);

            if ($order && $order->status !== 'paid') {
                $order->update([
                    'status' => 'paid',
                    'payment_intent' => $session->payment_intent,
                    'paid_at' => now(),
                ]);

                // Log ou notification
                Log::info("Order {$order->id} marked as paid via webhook");
            }
        }
    }
    public function getAllOrders(Request $request)
    {
        $user = Auth::guard('sanctum')->user(); // Explicitly use Sanctum guard

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $orders = $user->orders()->with('products')->get();
            $payments = Payment::whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
                ->with(['order' => function ($query) {
                    $query->select('id', 'total', 'status');
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['orders' => $orders,
        'payments'=> $payments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
// class PaymentController extends Controller
// {
//     public function createCheckoutSession(Request $request)
//     {
//         Stripe::setApiKey('sk_test_51RQvvN035Yc6VQc7s5cFX8fy5DkhaZPxRyIyne2CDtA0AOEWa5UCAOzsaE3m1JBgoxAjz0L1CDe5bZpUm63G9Sp900omHvc3Un');

//         try {
//             $lineItems = [];
//             foreach ($request->items as $item) {
//                 $lineItems[] = [
//                     'price_data' => [
//                         'currency' => $item['price_data']['currency'] ?? 'mad',
//                         'product_data' => [
//                             'name' => $item['price_data']['product_data']['name'],
//                         ],
//                         'unit_amount' => $item['price_data']['unit_amount'],
//                     ],
//                     'quantity' => $item['quantity'],
//                 ];
//             }

//             $session = Session::create([
//                 'payment_method_types' => ['card'],
//                 'line_items' => $lineItems,
//                 'mode' => 'payment',
//                 'success_url' => 'http://localhost:5173' . '/success?session_id={CHECKOUT_SESSION_ID}',
//                 'cancel_url' => 'http://localhost:5173'. '/canceled',
//             ]);

//             return response()->json(['sessionId' => $session->id]);
//         } catch (\Exception $e) {
//             Log::error('Stripe checkout error: ' . $e->getMessage());
//             return response()->json(['error' => $e->getMessage()], 500);
//         }
//     }

//     public function handleWebhook(Request $request)
//     {
//         $payload = $request->getContent();
//         $sigHeader = $request->header('Stripe-Signature');

//         try {
//             $event = \Stripe\Webhook::constructEvent(
//                 $payload,
//                 $sigHeader,
//                 'sk_test_51RQvvN035Yc6VQc7s5cFX8fy5DkhaZPxRyIyne2CDtA0AOEWa5UCAOzsaE3m1JBgoxAjz0L1CDe5bZpUm63G9Sp900omHvc3Un'
//             );
//         } catch (\Exception $e) {
//             return response('Webhook Error', 400);
//         }

//         // Handle checkout.session.completed event
//         if ($event->type === 'checkout.session.completed') {
//             $session = $event->data->object;

//             // Here you would typically:
//             // 1. Get the order details from the session
//             // 2. Update your database to mark the order as paid
//             // 3. Send confirmation email, etc.

//             Log::info('Payment succeeded for session: ' . $session->id);
//         }

//         return response()->json(['status' => 'success']);
//     }
//     public function verifyPayment(Request $request)
//     {
//         $sessionId = $request->input('session_id');
//         $orderId = $request->input('order_id');

//         try {
//             \Stripe\Stripe::setApiKey('sk_test_51RQvvN035Yc6VQc7s5cFX8fy5DkhaZPxRyIyne2CDtA0AOEWa5UCAOzsaE3m1JBgoxAjz0L1CDe5bZpUm63G9Sp900omHvc3Un');

//             $session = \Stripe\Checkout\Session::retrieve($sessionId);

//             if ($session->payment_status === 'paid') {
//                 // ✅ Mettre à jour la commande
//                 $order = Order::findOrFail($orderId);
//                 $order->status = 'paid';
//                 $order->payment_intent = $session->payment_intent;
//                 $order->payment_method = $session->payment_method_types[0];
//                 $order->paid_at = now();
//                 $order->save();

//                 return response()->json(['status' => 'success']);
//             } else {
//                 return response()->json(['status' => 'failed'], 400);
//             }
//         } catch (\Exception $e) {
//             return response()->json(['error' => $e->getMessage()], 500);
//         }
//     }
// }
