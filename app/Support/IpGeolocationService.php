<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IpGeolocationService
{
    /**
     * @return array{country_code:?string,country_name:?string,city_name:?string}|null
     */
    public function lookup(string $ipAddress): ?array
    {
        $ipAddress = trim($ipAddress);

        if (
            $ipAddress === ''
            || app()->runningUnitTests()
            || ! filter_var(
                $ipAddress,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            )
            || ! (bool) config('services.ip_geolocation.enabled', true)
        ) {
            return null;
        }

        return Cache::remember('ip-geolocation:'.sha1($ipAddress), now()->addDays(30), function () use ($ipAddress): ?array {
            $response = Http::acceptJson()
                ->connectTimeout((float) config('services.ip_geolocation.connect_timeout', 0.75))
                ->timeout((float) config('services.ip_geolocation.timeout', 1.5))
                ->get(sprintf('%s/%s/json/', rtrim((string) config('services.ip_geolocation.base_url', 'https://ipapi.co'), '/'), $ipAddress));

            if (! $response->successful()) {
                return null;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                return null;
            }

            $countryName = trim((string) ($payload['country_name'] ?? ''));
            $countryCode = trim((string) ($payload['country_code'] ?? ''));
            $cityName = trim((string) ($payload['city'] ?? ''));

            if ($countryName === '' && $cityName === '') {
                return null;
            }

            return [
                'country_code' => $countryCode !== '' ? Str::upper(Str::limit($countryCode, 8, '')) : null,
                'country_name' => $countryName !== '' ? Str::limit($countryName, 120, '') : null,
                'city_name' => $cityName !== '' ? Str::limit($cityName, 120, '') : null,
            ];
        });
    }
}
