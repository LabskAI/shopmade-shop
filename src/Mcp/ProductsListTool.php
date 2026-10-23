<?php

namespace Shopmade\Shop\Mcp;

use App\Core\Mcp\McpContext;
use App\Core\Mcp\Tools\Tool;
use App\Core\Mcp\Tools\ToolResult;
use App\Enums\ConnectorScope;
use Shopmade\Shop\Models\Product;

class ProductsListTool extends Tool
{
    public function name(): string
    {
        return 'products_list';
    }

    public function title(): string
    {
        return 'List shop products';
    }

    public function description(): string
    {
        return 'Products in the shop with price, type, stock, whether they are live, and their public URL. Use it to link products from website pages.';
    }

    public function scope(): ConnectorScope
    {
        return ConnectorScope::Operator;
    }

    public function inputSchema(): array
    {
        return $this->objectSchema([
            'active_only' => ['type' => 'boolean', 'default' => false],
        ]);
    }

    public function handle(array $arguments, McpContext $context): ToolResult
    {
        $products = Product::query()
            ->when($arguments['active_only'] ?? false, fn ($query) => $query->active())
            ->orderBy('name')
            ->get();

        return new ToolResult([
            'products' => $products->map(fn (Product $product): array => [
                'slug' => $product->slug,
                'name' => $product->name,
                'price' => $product->price,
                'type' => $product->type->value,
                'stock' => $product->stock,
                'active' => $product->active,
                'url' => route('shop.show', $product),
            ])->all(),
        ]);
    }
}
