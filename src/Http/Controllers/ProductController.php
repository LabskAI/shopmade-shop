<?php

namespace Shopmade\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Shopmade\Shop\Enums\ProductType;
use Shopmade\Shop\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->when($request->string('q')->toString(), function ($query, string $search) {
                $like = '%'.strtolower($search).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->whereRaw('lower(name) like ?', [$like])
                        ->orWhereRaw('lower(coalesce(sku, \'\')) like ?', [$like]);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('shop::products.index', compact('products'));
    }

    public function create(): View
    {
        return view('shop::products.create', [
            'product' => null,
            'types' => ProductType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::query()->create($this->validated($request));

        return redirect()->route('products.show', $product)->with('status', 'Product created.');
    }

    public function show(Product $product): View
    {
        return view('shop::products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        return view('shop::products.edit', [
            'product' => $product,
            'types' => ProductType::cases(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('products.show', $product)->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Product deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $request->merge(['slug' => Str::slug((string) ($request->filled('slug') ? $request->input('slug') : $request->input('name')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($product),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'stock' => ['nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $data['active'] = $request->boolean('active');

        if ($data['type'] !== ProductType::Physical->value) {
            $data['stock'] = null;
        }

        return $data;
    }
}
