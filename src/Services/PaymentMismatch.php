<?php

namespace Shopmade\Shop\Services;

use RuntimeException;

/**
 * Stripe holds a card payment for the order that doesn't match it (another session, amount or currency), so it was
 * not recorded and was logged for a refund.
 */
class PaymentMismatch extends RuntimeException {}
