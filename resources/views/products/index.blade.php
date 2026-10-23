@extends('layouts.app')

@section('title', 'Products')
@section('breadcrumb', 'Products')

@section('content')
    <x-page-header title="Products" kicker="Sales">
        <x-slot:actions>
            @if (auth()->user()->canManageCoreTool('products'))
                <x-button href="{{ route('products.create') }}"><i data-lucide="plus" class="size-4"></i>New product</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="border-2 border-line bg-surface">
        <form method="GET">
            <x-toolbar title="Products" :count="$products->total()">
                <x-slot:right>
                    <x-input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or SKU" />
                </x-slot:right>
            </x-toolbar>
        </form>
        <x-table :columns="[['label' => 'Name'], ['label' => 'Type'], ['label' => 'Price', 'align' => 'right'], ['label' => 'Stock'], ['label' => 'Status']]">
            @foreach ($products as $product)
                <tr class="border-b border-line-soft">
                    <td class="px-4 py-3 text-control"><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                    <td class="px-4 py-3 text-control text-ink/70">{{ $product->type->label() }}</td>
                    <td class="px-4 py-3 text-right text-control tabular-nums">${{ number_format($product->price, 2) }}</td>
                    <td class="px-4 py-3 text-control text-ink/70">{{ $product->stock ?? 'Unlimited' }}</td>
                    <td class="px-4 py-3"><x-status :state="$product->active ? 'active' : 'disabled'" /></td>
                </tr>
            @endforeach
            <x-slot:empty>
                <x-empty title="No products yet" body="Add items customers can buy from the shop.">
                    @if (auth()->user()->canManageCoreTool('products'))
                        <x-slot:action>
                            <x-button variant="secondary" href="{{ route('products.create') }}">New product</x-button>
                        </x-slot:action>
                    @endif
                </x-empty>
            </x-slot:empty>
        </x-table>
        {{ $products->links() }}
    </div>
@endsection
