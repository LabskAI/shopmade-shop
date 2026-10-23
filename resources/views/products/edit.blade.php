@extends('layouts.app')

@section('title', 'Edit product')
@section('breadcrumb', 'Products / Edit')

@section('content')
    <x-page-header title="Edit {{ $product->name }}" kicker="Sales" />
    <form method="POST" action="{{ route('products.update', $product) }}" class="max-w-4xl">
        @csrf
        @method('PUT')
        <x-card title="Details">
            @include('shop::products._form')
            <div class="mt-6 flex justify-start gap-2">
                <x-button type="submit">Save</x-button>
                <x-button variant="secondary" href="{{ route('products.show', $product) }}">Cancel</x-button>
            </div>
        </x-card>
    </form>
@endsection
