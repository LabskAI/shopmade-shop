<?php

namespace Shopmade\Shop\Services;

use RuntimeException;

/**
 * Stripe confirmed the Checkout Session is still open (or in a state that isn't closed), so it may still be paid.
 */
class SessionStillOpen extends RuntimeException {}
