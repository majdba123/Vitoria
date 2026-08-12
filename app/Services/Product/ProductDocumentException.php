<?php

namespace App\Services\Product;

use RuntimeException;

/**
 * A rejection the acting user can act on. Rendered as a 422 with the message
 * shown to the user, mirroring App\Services\Commerce\CartException.
 */
class ProductDocumentException extends RuntimeException {}
