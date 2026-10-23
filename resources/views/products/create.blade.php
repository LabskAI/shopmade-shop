@extends('layouts.app')

@section('title', 'New product')
@section('breadcrumb', 'Products / New')

@section('content')
    <x-page-header title="New product" kicker="Sales" />
    <form method="POST" action="{{ route('products.store') }}" class="max-w-4xl">
        @csrf
        <x-card title="Details">
            @include('shop::products._form')
            <div class="mt-6 flex justify-start gap-2">
                <x-button type="submit">Save</x-button>
                <x-button variant="secondary" href="{{ route('products.index') }}">Cancel</x-button>
            </div>
        </x-card>
    </form>
@endsection
