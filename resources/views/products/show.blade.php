@extends('layouts.app')

@section('title', $product->name)
@section('breadcrumb', 'Products / '.$product->name)

@section('content')
    <x-page-header title="{{ $product->name }}" kicker="Sales">
        <x-slot:actions>
            <x-status :state="$product->active ? 'active' : 'disabled'" />
            @if (auth()->user()->canManageCoreTool('products'))
                <x-button href="{{ route('products.edit', $product) }}">Edit</x-button>
            @endif
            <x-button variant="secondary" href="{{ route('shop.show', $product) }}">View in shop</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Details">
            <dl class="space-y-3 text-body">
                <div>
                    <dt class="text-label text-ink/70">Type</dt>
                    <dd>{{ $product->type->label() }}</dd>
                </div>
                <div>
                    <dt class="text-label text-ink/70">Price</dt>
                    <dd>{{ \Shopmade\Shop\Support\Money::format($product->price) }}</dd>
                </div>
                <div>
                    <dt class="text-label text-ink/70">SKU</dt>
                    <dd>{{ $product->sku ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-label text-ink/70">Stock</dt>
                    <dd>{{ $product->stock ?? 'Unlimited' }}</dd>
                </div>
                <div>
                    <dt class="text-label text-ink/70">Description</dt>
                    <dd>{{ $product->description ?: '—' }}</dd>
                </div>
            </dl>
        </x-card>
        <x-card title="Shop link">
            <p class="text-body text-ink/70">Public product page:</p>
            <a class="mt-2 block break-all text-control underline" href="{{ route('shop.show', $product) }}">{{ route('shop.show', $product) }}</a>
        </x-card>
    </div>
@endsection
