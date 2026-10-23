<div class="grid gap-4 sm:grid-cols-2">
    <x-field class="sm:col-span-2" label="Name" for="name" :error="$errors->first('name')">
        <x-input id="name" name="name" value="{{ old('name', $product?->name) }}" required :error="$errors->has('name')" />
    </x-field>
    <x-field label="Slug" for="slug" :error="$errors->first('slug')">
        <x-input id="slug" name="slug" value="{{ old('slug', $product?->slug) }}" placeholder="auto from name" :error="$errors->has('slug')" />
    </x-field>
    <x-field label="SKU" for="sku" :error="$errors->first('sku')">
        <x-input id="sku" name="sku" value="{{ old('sku', $product?->sku) }}" :error="$errors->has('sku')" />
    </x-field>
    <x-field label="Price" for="price" :error="$errors->first('price')">
        <x-input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product?->price) }}" required :error="$errors->has('price')" />
    </x-field>
    <x-field label="Type" for="type" :error="$errors->first('type')">
        <x-select id="type" name="type" :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])" :selected="old('type', isset($product) ? $product->type->value : 'physical')" />
    </x-field>
    <x-field label="Stock" for="stock" :error="$errors->first('stock')">
        <x-input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product?->stock) }}" placeholder="Leave blank for unlimited" :error="$errors->has('stock')" />
    </x-field>
    <x-field class="sm:col-span-2" label="Image URL" for="image_url" :error="$errors->first('image_url')">
        <x-input id="image_url" name="image_url" value="{{ old('image_url', $product?->image_url) }}" :error="$errors->has('image_url')" />
    </x-field>
    <x-field class="sm:col-span-2" label="Description" for="description" :error="$errors->first('description')">
        <x-textarea id="description" name="description" rows="4" :error="$errors->has('description')">{{ old('description', $product?->description) }}</x-textarea>
    </x-field>
    <x-field class="sm:col-span-2" label="Active" for="active">
        <x-checkbox id="active" name="active" value="1" :checked="old('active', $product?->active ?? true)" />
    </x-field>
</div>
