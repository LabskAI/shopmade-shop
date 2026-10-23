<div class="mb-6 grid grid-cols-1 gap-0.5 border-2 border-line bg-line sm:grid-cols-2">
    <a href="{{ route('orders.index', ['status' => 'new']) }}" class="block text-ink hover:text-ink"><x-stat label="New orders" :value="$newOrders" /></a>
    <a href="{{ route('orders.index', ['status' => 'paid']) }}" class="block text-ink hover:text-ink"><x-stat label="Paid, to fulfil" :value="$toFulfil" /></a>
</div>
