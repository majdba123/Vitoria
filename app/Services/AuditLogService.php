<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Immutable audit logging for sensitive actions (spec §35).
 *
 * Deliberately called from the smallest number of choke points that still
 * cover every action spec §35 names: the commerce services that already
 * take an actor and centralize a single write path (OrderStatusService,
 * OrderCancellationService, ReturnService, RefundService,
 * VendorLedgerService) call this directly; a handful of simpler admin
 * controllers with no equivalent service layer (vendor approval, coupons,
 * settings, user updates, product moderation) call it after their write
 * succeeds.
 */
class AuditLogService
{
    /**
     * Keys redacted from `old_values`/`new_values` before they are ever
     * written — never logged, no exceptions (spec §35).
     *
     * @var list<string>
     */
    private const REDACTED_KEYS = [
        'password', 'password_confirmation', 'current_password',
        'token', 'api_token', 'access_token', 'refresh_token', 'remember_token',
        'secret', 'client_secret',
        'card_number', 'cvv', 'cvc', 'card_cvc',
        'otp', 'provider_reference',
    ];

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(
        ?User $actor,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'actor_user_id' => $actor?->id,
            'actor_type' => $actor ? $this->actorTypeFor($actor) : null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues !== null ? $this->redact($oldValues) : null,
            'new_values' => $newValues !== null ? $this->redact($newValues) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? substr($request->userAgent(), 0, 255) : null,
            'request_id' => $request?->attributes->get('request_id'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        $redacted = [];

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED_KEYS, true)) {
                $redacted[$key] = '[redacted]';

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }

    private function actorTypeFor(User $user): string
    {
        return match (true) {
            $user->isAdmin() => 'admin',
            $user->isVendor() => 'vendor',
            $user->isEmployee() => 'employee',
            $user->isSyndicate() => 'syndicate',
            default => 'customer',
        };
    }
}
