<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PaymentRequirementService
{
    public const STORE_KEY = 'product_access.payment_requirement';

    public const AUDIT_ACTION = 'product_access.payment_requirement_changed';

    protected const CACHE_KEY = 'product_access.payment_requirement.v1';

    /**
     * @var int
     */
    protected const CACHE_TTL_SECONDS = 300;

    public function __construct(
        protected SystemSettingsStore $systemSettingsStore,
    ) {}

    public function requiresPayment(): bool
    {
        try {
            return Cache::remember(
                self::CACHE_KEY,
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                fn (): bool => $this->storedRequirement(),
            );
        } catch (Throwable) {
            return true;
        }
    }

    public function setRequiresPayment(bool $requiresPayment, ?User $actor = null): bool
    {
        $previousRequirement = $this->requiresPayment();

        if ($previousRequirement === $requiresPayment) {
            return false;
        }

        $this->systemSettingsStore->put(self::STORE_KEY, [
            'requires_payment' => $requiresPayment,
        ]);

        $this->forgetCachedRequirement();

        if ($this->requiresPayment() !== $requiresPayment) {
            return false;
        }

        if ($actor instanceof User) {
            app(AuditLogService::class)->record(
                self::AUDIT_ACTION,
                'system_setting',
                self::STORE_KEY,
                $actor,
                [
                    'before_requires_payment' => $previousRequirement,
                    'after_requires_payment' => $requiresPayment,
                ],
            );
        }

        return true;
    }

    public function forgetCachedRequirement(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // A cache outage must never turn paid access into free access.
        }
    }

    protected function storedRequirement(): bool
    {
        $payload = $this->systemSettingsStore->get(self::STORE_KEY);

        if (! is_array($payload) || ! array_key_exists('requires_payment', $payload)) {
            return true;
        }

        return is_bool($payload['requires_payment'])
            ? $payload['requires_payment']
            : true;
    }
}
