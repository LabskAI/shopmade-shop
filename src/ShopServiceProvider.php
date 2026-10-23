<?php

namespace Shopmade\Shop;

use App\Core\Packages\Hooks;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Mcp\OrdersListTool;
use Shopmade\Shop\Mcp\ProductsListTool;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\View\ShopLayoutComposer;

/**
 * Products, a public storefront with cart and guest checkout (Stripe optional), and staff order handling,
 * plugged into core through Hooks.
 */
class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/shop.php', 'shop');
    }

    public function boot(Hooks $hooks): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'shop');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->publishes([__DIR__.'/../config/shop.php' => config_path('shop.php')], 'shop-config');

        View::composer('shop::layouts.shop', ShopLayoutComposer::class);

        Customer::resolveRelationUsing('orders', fn (Customer $customer) => $customer->hasMany(Order::class));

        $hooks->tool([
            'slug' => 'products',
            'label' => 'Products',
            'group' => 'Sales',
            'route' => 'products.index',
            'pattern' => 'products.*',
            'view_roles' => ['super_admin', 'admin', 'user'],
            'manage_roles' => ['super_admin', 'admin'],
        ]);

        $hooks->tool([
            'slug' => 'orders',
            'label' => 'Orders',
            'group' => 'Sales',
            'route' => 'orders.index',
            'pattern' => 'orders.*',
            'view_roles' => ['super_admin', 'admin', 'user'],
            'manage_roles' => ['super_admin', 'admin'],
        ]);

        $hooks->status(OrderStatus::New->value, like: 'open', label: OrderStatus::New->label());
        $hooks->status(OrderStatus::Fulfilled->value, like: 'complete', label: OrderStatus::Fulfilled->label());

        $hooks->dashboardCard('shop::hooks.dashboard-card', fn (User $user): array => [
            'newOrders' => Order::query()->where('status', OrderStatus::New)->count(),
            'toFulfil' => Order::query()->where('status', OrderStatus::Paid)->count(),
        ]);
        $hooks->customerPanel('shop::hooks.customer-panel', fn (Customer $customer): array => [
            'orders' => $customer->orders()->latest('id')->limit(10)->get(),
        ]);

        $hooks->mcpTool('products_list', ProductsListTool::class);
        $hooks->mcpTool('orders_list', OrdersListTool::class);
    }
}
