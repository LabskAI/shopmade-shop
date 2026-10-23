<x-mail::message>
# Order confirmed

Thanks for your order **{{ $order->number }}**.

View your order status anytime:

<x-mail::button :url="$orderUrl">
View order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
