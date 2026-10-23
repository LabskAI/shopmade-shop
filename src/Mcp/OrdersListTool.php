<?php

namespace Shopmade\Shop\Mcp;

use App\Core\Mcp\McpContext;
use App\Core\Mcp\Tools\Tool;
use App\Core\Mcp\Tools\ToolResult;
use App\Enums\ConnectorScope;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Models\Order;

class OrdersListTool extends Tool
{
    public function name(): string
    {
        return 'orders_list';
    }

    public function title(): string
    {
        return 'List shop orders';
    }

    public function description(): string
    {
        return 'Shop orders, newest first, with number, buyer, total, status and items. Filter by status.';
    }

    public function scope(): ConnectorScope
    {
        return ConnectorScope::Operator;
    }

    public function inputSchema(): array
    {
        return $this->objectSchema([
            'status' => ['type' => 'string', 'enum' => array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::cases())],
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 25],
        ]);
    }

    public function handle(array $arguments, McpContext $context): ToolResult
    {
        $orders = Order::query()
            ->with('items')
            ->when($arguments['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->limit($arguments['limit'] ?? 25)
            ->get();

        return new ToolResult([
            'orders' => $orders->map(fn (Order $order): array => [
                'number' => $order->number,
                'buyer' => $order->guest_name,
                'email' => $order->guest_email,
                'total' => $order->total,
                'status' => $order->status->value,
                'created_at' => $order->created_at?->toIso8601String(),
                'items' => $order->items->map(fn ($item): array => ['name' => $item->name, 'qty' => $item->qty])->all(),
                'url' => route('orders.show', $order),
            ])->all(),
        ]);
    }
}
