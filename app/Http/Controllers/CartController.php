<?php
namespace App\Http\Controllers;



namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\user;
use App\Models\Payment;

use App\Models\Order;

class CartController extends Controller
{
    public function getCart()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $cart = Cart::with(['items.product'])
                ->firstOrCreate(
                    ['user_id' => $user->id],
                    ['total' => 0, 'status' => 'active']
                );

            return response()->json([
                'success' => true,
                'cart' => [
                    'items' => $cart->items->map(function ($item) {
                        return [
                            'id' => $item->product_id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'product' => $item->product ? [
                                'name' => $item->product->name,
                                'imgId' => $item->product->imgId,
                                // Add other product fields as needed
                            ] : null
                        ];
                    }),
                    'total' => $cart->total
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sync(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'items' => '|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.price' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            $cart->items()->delete();
            
            $total = 0;
            $syncedItems = [];
            
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    throw new \Exception("Product {$item['product_id']} not found");
                }
                
                $cartItem = $cart->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
                
                $total += $item['price'] * $item['quantity'];
                
                $syncedItems[] = [
                    'id' => $cartItem->product_id,
                    'product_id' => $cartItem->product_id,
                     'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'name' => $product->name,
                    'imgId' => $product->imgId,
               
                ];
            }
            
            $cart->total = $total;
            $cart->save();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cart synced successfully',
                'cart' => [
                    'items' => $syncedItems,
                    'total' => $total
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cart sync failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function mergeCarts(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'localItems' => 'required|array',
            'localItems.*.id' => 'required|exists:products,id',
            'localItems.*.quantity' => 'required|integer|min:1|max:100',
            'localItems.*.price' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            $currentProductIds = $cart->items->pluck('product_id')->toArray();
            
            $total = $cart->total;
            $mergedItems = $cart->items->toArray();
            
            foreach ($request->localItems as $item) {
                if (!in_array($item['id'], $currentProductIds)) {
                    $product = Product::find($item['id']);
                    
                    if (!$product) {
                        continue;
                    }
                    
                    $cartItem = $cart->items()->create([
                        'product_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price']
                    ]);
                    
                    $total += $item['price'] * $item['quantity'];
                    
                    $mergedItems[] = [
                        'id' => $cartItem->product_id,
                        'product_id' => $cartItem->product_id,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                        'product' => [
                            'name' => $product->name,
                            'imgId' => $product->imgId,
                            // Add other product fields as needed
                        ]
                    ];
                }
            }
            
            $cart->total = $total;
            $cart->save();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Carts merged successfully',
                'cart' => [
                    'items' => $mergedItems,
                    'total' => $total
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cart merge failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to merge carts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   
}