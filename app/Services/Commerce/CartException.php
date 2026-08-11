<?php

namespace App\Services\Commerce;

use RuntimeException;

/**
 * A rejection the customer can act on (out of stock, product withdrawn,
 * coupon no longer valid). Rendered as a 422 with the message shown to the
 * user, so messages must be translated and must not leak internals.
 */
class CartException extends RuntimeException {}
