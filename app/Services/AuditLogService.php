<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Append-only audit logging for sensitive actions (spec §35).
 *
 * Called from the write path of each audited action — services where one
 * exists, otherwise the admin controller — and, wherever the write runs in a
 * transaction, inside that same transaction so the audit row commits or
 * rolls back with the change it describes. Order, return and shipment state
 * keep their dedicated history tables (decision D20); money-moving refund
 * transitions and RBAC changes are recorded here as well.
 *
 * Values are redacted (credentials, tokens, card data) and personal
 * identifiers masked before they are ever written.
 */
class AuditLogService
{
    /**
     * Set on the current request once any domain audit row has been written,
     * so AuditPrivilegedMutations only adds a generic row for writes that
     * produced no domain-specific record.
     */
    public const REQUEST_RECORDED_FLAG = 'audit.recorded';

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
        'otp', 'otp_code', 'pin', 'provider_reference',
    ];

    /**
     * Any key containing one of these fragments is redacted too, so variants
     * such as `new_password`, `password_hash`, `stripe_secret_key`,
     * `accessToken` or `Authorization` can never slip through an exact-match
     * list.
     *
     * @var list<string>
     */
    private const REDACTED_KEY_FRAGMENTS = [
        'password', 'passwd', 'secret', 'token', 'api_key', 'apikey',
        'authorization', 'bearer', 'cookie', 'session', 'private_key',
        'card_number', 'cardnumber', 'cvv', 'cvc',
    ];

    /**
     * Personal identifiers kept only as a masked tail: enough to tell which
     * value changed, never the full identifier.
     *
     * @var list<string>
     */
    private const MASKED_KEYS = ['national_id', 'phone_number', 'phone'];

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
        bool $isSystemAction = false,
    ): AuditLog {
        $request = request();
        $request?->attributes->set(self::REQUEST_RECORDED_FLAG, true);

        return AuditLog::create([
            'actor_user_id' => $actor?->id,
            'actor_type' => $actor ? $this->actorTypeFor($actor) : ($isSystemAction ? 'system' : null),
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
    public function redact(array $values): array
    {
        $redacted = [];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->isSecretKey($normalizedKey) && ! is_bool($value)) {
                $redacted[$key] = '[redacted]';

                continue;
            }

            if (in_array($normalizedKey, self::MASKED_KEYS, true) && is_scalar($value) && $value !== '') {
                $redacted[$key] = $this->mask((string) $value);

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }

    private function isSecretKey(string $key): bool
    {
        if (in_array($key, self::REDACTED_KEYS, true)) {
            return true;
        }

        foreach (self::REDACTED_KEY_FRAGMENTS as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function mask(string $value): string
    {
        $visible = mb_substr($value, -3);

        return str_repeat('•', max(mb_strlen($value) - 3, 3)).$visible;
    }

    private function actorTypeFor(User $user): string
    {
        return match (true) {
            $user->isAdmin() => 'admin',
            $user->isVendor() => 'vendor',
            $user->isEmployee() => 'employee',
            $user->isSyndicate() => 'syndicate',
            $user->isMinistry() => 'ministry',
            default => 'customer',
        };
    }
}
