@if ($orders->isNotEmpty())
    <x-card title="Shop orders">
        @foreach ($orders as $order)
            <div class="flex items-center justify-between border-b border-line-soft py-2 last:border-0">
                <a class="text-control" href="{{ route('orders.show', $order) }}">{{ $order->number }} · ${{ number_format($order->total, 2) }}</a>
                <x-status :state="$order->status->value" />
            </div>
        @endforeach
    </x-card>
@endif
