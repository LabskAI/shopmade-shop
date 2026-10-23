<?php

namespace Shopmade\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Shopmade\Shop\Services\StripeCheckout;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeCheckout $stripe): Response
    {
        $signed = filled(config('shop.stripe.webhook_secret'));

        if ($signed && ! $stripe->hasValidSignature($request->getContent(), $request->header('Stripe-Signature'))) {
            return response('Invalid signature', 400);
        }

        try {
            $handled = $stripe->handleWebhookPayload($request->json()->all(), $signed);
        } catch (HttpClientException $exception) {
            report($exception);

            return response('Stripe lookup failed', 500);
        }

        if ($handled) {
            return response('OK', 200);
        }

        return response('Ignored', 200);
    }
}
