<?php

namespace Shopmade\Shop\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Shopmade\Shop\Models\Order;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order '.$this->order->number.' confirmed',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'shop::mail.order-confirmation',
            with: [
                'orderUrl' => $this->order->publicUrl(),
            ],
        );
    }
}
