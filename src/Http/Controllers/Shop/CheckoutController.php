<?php

namespace Shopmade\Shop\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Mail\OrderConfirmation;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Models\OrderItem;
use Shopmade\Shop\Models\Product;
use Shopmade\Shop\Services\Cart;
use Shopmade\Shop\Services\OutOfStock;
use Shopmade\Shop\Services\StripeCheckout;
use Throwable;

class CheckoutController extends Controller
{
    public function create(Request $request, Cart $cart, StripeCheckout $stripe): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('shop.cart')->with('status', 'Add items to your cart before checkout.');
        }

        return view('shop::shop.checkout', [
            'lines' => $cart->lines(),
            'total' => $cart->total(),
            // Only staff who manage orders may see (and pick from) the customer list.
            'customers' => $this->canLinkCustomer($request)
                ? Customer::query()->orderBy('name')->get()
                : collect(),
            'stripeEnabled' => $stripe->isConfigured(),
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('shop.index')->with('status', 'Your cart is empty.');
        }

        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            // Only staff who manage orders may link an order to an existing customer. For anyone
            // else a posted customer_id is not validated (no existence probing) and never saved.
            ...($this->canLinkCustomer($request)
                ? ['customer_id' => ['nullable', 'exists:customers,id']]
                : []),
        ]);

        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return redirect()->route('shop.index')->with('status', 'Your cart is empty.');
        }

        try {
            $order = $this->placeOrder($data, $lines, $cart->total());
        } catch (OutOfStock $exception) {
            return back()->withInput()->withErrors(['cart' => 'Not enough stock for '.$exception->getMessage().'.']);
        }

        $cart->clear();

        $this->sendConfirmationMail($order);

        return redirect()->route('shop.thank-you', [
            'order' => $order->id,
            'token' => $order->access_token,
        ]);
    }

    public function thankYou(Order $order, string $token): View
    {
        abort_unless(hash_equals($order->access_token, $token), 403);

        $order->load('items');

        return view('shop::shop.thank-you', compact('order'));
    }

    private function canLinkCustomer(Request $request): bool
    {
        return (bool) $request->user()?->canManageCoreTool('orders');
    }

    /**
     * Creates the order and takes stock in one transaction. A clash on the order number is retried.
     *
     * @param  array<string, mixed>  $data
     * @param  Collection<int, array{product: Product, qty: int, line_total: float}>  $lines
     *
     * @throws OutOfStock
     */
    private function placeOrder(array $data, Collection $lines, float $total): Order
    {
        return retry(3, fn (): Order => DB::transaction(function () use ($data, $lines, $total): Order {
            $order = new Order($data);
            $order->forceFill(['customer_id' => $data['customer_id'] ?? null, 'status' => OrderStatus::New, 'total' => $total])->save();

            foreach ($lines as $line) {
                if (! $line['product']->decrementStock($line['qty'])) {
                    throw new OutOfStock($line['product']->name);
                }

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'name' => $line['product']->name,
                    'unit_price' => $line['product']->price,
                    'qty' => $line['qty'],
                    'line_total' => $line['line_total'],
                ]);
            }

            return $order;
        }), when: fn (Throwable $exception): bool => $exception instanceof UniqueConstraintViolationException);
    }

    /**
     * The order is already saved: a mail failure is logged, never shown to the buyer as a failed checkout.
     */
    private function sendConfirmationMail(Order $order): void
    {
        try {
            Mail::to($order->guest_email)->send(new OrderConfirmation($order));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
