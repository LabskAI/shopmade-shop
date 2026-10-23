@extends('layouts.app')

@section('title', $order->number)
@section('breadcrumb', 'Orders / '.$order->number)

@section('content')
    <x-page-header title="{{ $order->number }}" kicker="Sales">
        <x-slot:actions>
            <x-status :state="$order->status->value" />
            @if (auth()->user()->canManageCoreTool('orders'))
                @if ($order->status === \Shopmade\Shop\Enums\OrderStatus::New)
                    <form method="POST" action="{{ route('orders.mark-paid', $order) }}">
                        @csrf
                        <x-button type="submit">Mark paid</x-button>
                    </form>
                @endif
                @if ($order->status->value !== 'fulfilled' && $order->status->value !== 'cancelled')
                    <form method="POST" action="{{ route('orders.mark-fulfilled', $order) }}">
                        @csrf
                        <x-button variant="secondary" type="submit">Mark fulfilled</x-button>
                    </form>
                @endif
                @if ($order->status === \Shopmade\Shop\Enums\OrderStatus::New)
                    <form method="POST" action="{{ route('orders.mark-cancelled', $order) }}">
                        @csrf
                        <x-button variant="ghost" type="submit">Cancel</x-button>
                    </form>
                @endif
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Line items">
            <table class="min-w-full text-left">
                <thead>
                    <tr class="border-b-2 border-line">
                        <th class="px-4 py-3 text-micro uppercase text-ink/70">Item</th>
                        <th class="px-4 py-3 text-micro uppercase text-ink/70">Qty</th>
                        <th class="px-4 py-3 text-micro uppercase text-ink/70">Price</th>
                        <th class="px-4 py-3 text-right text-micro uppercase text-ink/70">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr class="border-b border-line-soft">
                            <td class="px-4 py-3 text-control">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-control tabular-nums">{{ $item->qty }}</td>
                            <td class="px-4 py-3 text-control tabular-nums">{{ \Shopmade\Shop\Support\Money::format($item->unit_price) }}</td>
                            <td class="px-4 py-3 text-right text-control tabular-nums">{{ \Shopmade\Shop\Support\Money::format($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="px-4 pt-4 text-right text-control">Total</td>
                        <td class="px-4 pt-4 text-right text-card-title tabular-nums">{{ \Shopmade\Shop\Support\Money::format($order->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </x-card>
        <div class="space-y-4">
            <x-card title="Buyer">
                <dl class="space-y-2 text-body">
                    <div>
                        <dt class="text-label text-ink/70">Name</dt>
                        <dd>{{ $order->guest_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-label text-ink/70">Email</dt>
                        <dd>{{ $order->guest_email }}</dd>
                    </div>
                    <div>
                        <dt class="text-label text-ink/70">Phone</dt>
                        <dd>{{ $order->guest_phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-label text-ink/70">Address</dt>
                        <dd>{{ $order->address }}</dd>
                    </div>
                    @if ($order->notes)
                        <div>
                            <dt class="text-label text-ink/70">Notes</dt>
                            <dd>{{ $order->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>
            @if ($order->customer)
                <x-card title="Linked customer">
                    <a class="text-body" href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->name }}</a>
                </x-card>
            @endif
            <x-card title="Public order link">
                <p class="text-body text-ink/70">The buyer's private order page (also in their confirmation email):</p>
                <input type="text" readonly value="{{ $order->publicUrl() }}" class="mt-2 w-full border-2 border-line bg-bg px-3 py-2 text-label" onclick="this.select()" />
            </x-card>
        </div>
    </div>
@endsection
