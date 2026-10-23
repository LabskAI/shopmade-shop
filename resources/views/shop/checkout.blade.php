@extends('shop::layouts.shop')

@section('title', 'Checkout')
@section('crumb', 'Checkout')
@section('heading', 'Checkout')

@php
    $fields = [
        ['name' => 'guest_name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'autocomplete' => 'name'],
        ['name' => 'guest_email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email'],
        ['name' => 'guest_phone', 'label' => 'Phone', 'type' => 'tel', 'required' => false, 'autocomplete' => 'tel'],
    ];
@endphp

@section('shop')
    <div class="sm-shop-split">
        <form method="POST" action="{{ route('shop.checkout.store') }}" class="sm-shop-form">
            @csrf
            <fieldset class="sm-shop-panel">
                <legend class="sm-shop-h3">Contact &amp; delivery</legend>

                @foreach ($fields as $field)
                    <div class="sm-shop-field">
                        <label class="sm-shop-label" for="{{ $field['name'] }}">{{ $field['label'] }}@unless ($field['required']) <span class="sm-shop-optional">(optional)</span>@endunless</label>
                        <input class="sm-shop-input @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] }}" value="{{ old($field['name']) }}" autocomplete="{{ $field['autocomplete'] }}" @if ($field['required']) required @endif @error($field['name']) aria-invalid="true" aria-describedby="{{ $field['name'] }}-error" @enderror>
                        @error($field['name'])<p class="sm-shop-error" id="{{ $field['name'] }}-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                <div class="sm-shop-field">
                    <label class="sm-shop-label" for="address">Address</label>
                    <textarea class="sm-shop-input @error('address') is-invalid @enderror" id="address" name="address" rows="3" autocomplete="street-address" required @error('address') aria-invalid="true" aria-describedby="address-error" @enderror>{{ old('address') }}</textarea>
                    @error('address')<p class="sm-shop-error" id="address-error">{{ $message }}</p>@enderror
                </div>

                <div class="sm-shop-field">
                    <label class="sm-shop-label" for="notes">Notes <span class="sm-shop-optional">(optional)</span></label>
                    <textarea class="sm-shop-input @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2" @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror>{{ old('notes') }}</textarea>
                    @error('notes')<p class="sm-shop-error" id="notes-error">{{ $message }}</p>@enderror
                </div>

                @if ($customers->isNotEmpty())
                    <div class="sm-shop-field">
                        <label class="sm-shop-label" for="customer_id">Link to customer <span class="sm-shop-optional">(optional)</span></label>
                        <select class="sm-shop-input sm-shop-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id">
                            <option value="">None</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        @error('customer_id')<p class="sm-shop-error">{{ $message }}</p>@enderror
                    </div>
                @endif
            </fieldset>

            <div class="sm-shop-form-foot">
                <button class="sm-shop-btn" type="submit">Place order</button>
                @if ($stripeEnabled)
                    <p class="sm-shop-meta">You can pay with Stripe after placing the order.</p>
                @else
                    <p class="sm-shop-meta">Pay later — staff will mark your order paid when payment is received.</p>
                @endif
            </div>
        </form>

        <aside class="sm-shop-summary" aria-labelledby="summary-title">
            <h2 class="sm-shop-h3" id="summary-title">Order summary</h2>
            <ul class="sm-shop-sum-lines" role="list">
                @foreach ($lines as $line)
                    <li><span>{{ $line['product']->name }} × {{ $line['qty'] }}</span><span class="sm-shop-num">${{ number_format($line['line_total'], 2) }}</span></li>
                @endforeach
            </ul>
            <div class="sm-shop-total">
                <span>Total</span>
                <span class="sm-shop-price">${{ number_format($total, 2) }}</span>
            </div>
        </aside>
    </div>
@endsection
