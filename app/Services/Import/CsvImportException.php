<?php

namespace App\Services\Import;

/**
 * A file-level import failure (bad/missing headers, too many rows, unreadable file).
 * Carries a user-facing message; distinct from per-row validation errors.
 */
class CsvImportException extends \RuntimeException {}
