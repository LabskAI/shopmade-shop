@extends('layouts.app')

@section('title', 'Orders')
@section('breadcrumb', 'Orders')

@section('content')
    <x-page-header title="Orders" kicker="Sales" />

    <div class="border-2 border-line bg-surface">
        <form method="GET">
            <x-toolbar title="Orders" :count="$orders->total()">
                <x-slot:right>
                    <x-select name="status" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" :selected="request('status')" placeholder="All statuses" onchange="this.form.submit()" />
                </x-slot:right>
            </x-toolbar>
        </form>
        <x-table :columns="[['label' => 'Order'], ['label' => 'Customer'], ['label' => 'Total', 'align' => 'right'], ['label' => 'Status'], ['label' => 'Placed']]">
            @foreach ($orders as $order)
                <tr class="border-b border-line-soft">
                    <td class="px-4 py-3 text-control"><a href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
                    <td class="px-4 py-3 text-control text-ink/70">{{ $order->customer?->name ?? $order->guest_name }}</td>
                    <td class="px-4 py-3 text-right text-control tabular-nums">{{ \Shopmade\Shop\Support\Money::format($order->total) }}</td>
                    <td class="px-4 py-3"><x-status :state="$order->status->value" /></td>
                    <td class="px-4 py-3 text-control text-ink/70">{{ $order->created_at->format('M j, Y') }}</td>
                </tr>
            @endforeach
            <x-slot:empty>
                <x-empty title="No orders yet" body="Orders from the shop will appear here." />
            </x-slot:empty>
        </x-table>
        {{ $orders->links() }}
    </div>
@endsection
