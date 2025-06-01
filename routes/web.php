<?php

use Illuminate\Support\Facades\Route;


use Stripe\Stripe;
Route::get('/test-notification', function () {
    // Get the first order with its user relationship loaded
    $order = App\Models\Order::with('user')->first();

    if (!$order) {
        return 'No orders found in database!';
    }

    if (!$order->user) {
        return 'Order has no associated user!';
    }

    try {
        $order->user->notify(new App\Notifications\OrderConfirmation($order));
        return 'Notification sent successfully to user: ' . $order->user->email;
    } catch (\Exception $e) {
        return 'Error sending notification: ' . $e->getMessage();
    }
});

?>