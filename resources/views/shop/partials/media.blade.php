{{-- Product photo, only when the product has one. Fixed aspect ratio, so nothing moves when it loads. --}}
@if ($product->image_url)
    <img class="sm-shop-media" src="{{ $product->image_url }}" alt="{{ $product->name }}" width="800" height="600" loading="{{ ($lazy ?? false) ? 'lazy' : 'eager' }}">
@endif
