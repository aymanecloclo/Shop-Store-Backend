<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order;

class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // Send via both email and save to database
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Confirmation de votre commande #' . $this->order->id)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Merci pour votre commande sur notre site.')
            ->line('Numéro de commande: #' . $this->order->id)
            ->line('Montant total: ' . number_format($this->order->total, 2) . ' MAD')
            ->action('Voir votre commande', url('/orders/' . $this->order->id))
            ->line('Nous vous contacterons lorsque votre commande sera expédiée.');
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'amount' => $this->order->total,
            'message' => 'Votre commande #' . $this->order->id . ' a été confirmée.'
        ];
    }
}
