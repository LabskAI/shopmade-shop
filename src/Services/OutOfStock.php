<?php

namespace Shopmade\Shop\Services;

use RuntimeException;

/**
 * Thrown inside the checkout transaction when a product runs out; the message is the product name.
 */
class OutOfStock extends RuntimeException {}
