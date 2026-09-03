<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        string $entityType,
        string|int $entityId,
        ?User $actor = null,
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= app()->bound('request') ? request() : null;

        return AuditLog::query()->create([
            'actor_user_id' => $actor?->getKey(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'request_id' => $this->requestId($request),
            'ip_address' => $this->ipAddress($request),
            'user_agent' => $this->userAgent($request),
            'metadata' => $this->sanitizeMetadata($metadata),
            'created_at' => now(),
        ]);
    }

    protected function requestId(?Request $request): ?string
    {
        $requestId = $request?->attributes->get('request_id');

        return $requestId !== null ? Str::limit((string) $requestId, 120, '') : null;
    }

    protected function ipAddress(?Request $request): ?string
    {
        $ip = $request?->ip();

        return filled($ip) ? Str::limit((string) $ip, 45, '') : null;
    }

    protected function userAgent(?Request $request): ?string
    {
        $userAgent = $request?->userAgent();

        return filled($userAgent) ? Str::limit((string) $userAgent, 1000, '') : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    protected function sanitizeMetadata(array $metadata): array
    {
        $sanitized = $this->sanitizeValue($metadata);

        return is_array($sanitized) ? $sanitized : [];
    }

    protected function sanitizeValue(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && $this->shouldDropKey($key)) {
            return null;
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $itemKey => $itemValue) {
                $normalizedKey = is_string($itemKey) ? $itemKey : (string) $itemKey;
                $sanitizedValue = $this->sanitizeValue($itemValue, $normalizedKey);

                if ($sanitizedValue === null && $this->shouldDropKey($normalizedKey)) {
                    continue;
                }

                $sanitized[$itemKey] = $sanitizedValue;
            }

            return $sanitized;
        }

        if ($value instanceof Arrayable) {
            return $this->sanitizeValue($value->toArray(), $key);
        }

        if ($value instanceof Model) {
            return [
                'type' => class_basename($value),
                'id' => $value->getKey(),
            ];
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value)) {
            return Str::limit($value, 1000, '');
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        return Str::limit((string) $value, 1000, '');
    }

    protected function shouldDropKey(string $key): bool
    {
        $normalized = Str::lower($key);

        foreach ([
            'password',
            'token',
            'secret',
            'authorization',
            'cookie',
            'headers',
            'upload_url',
        ] as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
