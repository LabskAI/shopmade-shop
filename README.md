# shopmade-shop

A small shop for [Shopmade core](https://github.com/LabskAI/shopmade-core): products, a public storefront with cart and guest checkout, optional Stripe Checkout, and staff order handling. MIT licensed.

## What it adds

- **Storefront** at `/shop`: product grid, product page, cart, checkout, thank-you page and a private order link for the buyer, plus a confirmation email. It renders inside the public site's own layout (theme tokens, header, footer), so it matches the site. Shop pages are `noindex`.
- **Staff:** Products and Orders in the sidebar (Sales group). Mark orders paid, fulfilled or cancelled. Writes follow each tool's `manage_roles` (admins).
- **Dashboard:** new orders and paid orders waiting to be fulfilled.
- **Customer page:** the customer's orders, when an order was linked to one.
- **MCP:** `products_list` and `orders_list` (read-only).

## Install

```bash
composer require labskai/shopmade-shop
php artisan migrate
npm run build
```

## Stripe (optional)

Without keys, checkout saves the order for pay-later and staff mark it paid. To take card payments:

```dotenv
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...   # endpoint: https://your-site/stripe/webhook, events checkout.session.completed and checkout.session.async_payment_succeeded
STRIPE_CURRENCY=usd
```

With `STRIPE_WEBHOOK_SECRET` set, webhooks must carry a valid `Stripe-Signature`. Without it, each event is confirmed by looking the session up at Stripe before an order is marked paid.

## Tests

```bash
cd ../shopmade-core
composer config repositories.shop '{"type":"path","url":"../shopmade-shop"}'
composer require "labskai/shopmade-shop:*@dev"
vendor/bin/pest vendor/labskai/shopmade-shop/tests
```
