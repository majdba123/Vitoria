<?php

namespace App\Services\Admin;

use RuntimeException;

/**
 * A back-office access-control rule refused the requested change
 * (privilege escalation, protected role, last super administrator, ...).
 * Controllers render it as 403 with the translated message, or 422 when the
 * refusal is about the request's shape rather than the actor's authority.
 */
class AdminAccessException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 403)
    {
        parent::__construct($message);
    }
}
