<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The single server-side gateway to the Google Maps data APIs.
 * This is the ONLY place the server key is read, so the key never ships in the
 * client. Responses are shaped exactly to what the app needs and cached.
 */
class GoogleMapsGateway
{
    private string $baseUrl = 'https://maps.googleapis.com/maps/api';

    private function key(): string
    {
        return (string) config('services.google_maps.key');
    }

    /**
     * Pre-configured HTTP client. Forces IPv4 so Google sees the server's stable
     * IPv4 egress (the one allow-listed on the key), not its rotating IPv6 address.
     */
    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(10)->withOptions(['force_ip_resolve' => 'v4']);
    }

    /**
     * Place autocomplete predictions, biased to the caller's location.
     *
     * @return array{predictions: array<int, array<string, mixed>>}
     */
    public function autocomplete(string $input, ?float $lat, ?float $lng, string $language = 'ar'): array
    {
        $cacheKey = 'maps:ac:' . md5($input . '|' . $language . '|' . round((float) $lat, 2) . '|' . round((float) $lng, 2));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($input, $lat, $lng, $language) {
            $params = [
                'input' => $input,
                'language' => $language,
                'key' => $this->key(),
            ];

            if ($lat !== null && $lng !== null) {
                $params['location'] = "{$lat},{$lng}";
                $params['radius'] = 50000;
            }

            $response = $this->client()->get("{$this->baseUrl}/place/autocomplete/json", $params);

            $predictions = collect($response->json('predictions', []))->map(fn ($p) => [
                'place_id' => $p['place_id'] ?? null,
                'description' => $p['description'] ?? null,
                'main_text' => data_get($p, 'structured_formatting.main_text'),
                'secondary_text' => data_get($p, 'structured_formatting.secondary_text'),
            ])->all();

            return ['predictions' => $predictions];
        });
    }

    /**
     * Resolve a place_id to coordinates + a formatted address.
     *
     * @return array{lat: float|null, lng: float|null, address: string|null}
     */
    public function placeDetails(string $placeId, string $language = 'ar'): array
    {
        $cacheKey = 'maps:pd:' . md5($placeId . '|' . $language);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($placeId, $language) {
            $response = $this->client()->get("{$this->baseUrl}/place/details/json", [
                'place_id' => $placeId,
                'fields' => 'geometry,formatted_address',
                'language' => $language,
                'key' => $this->key(),
            ]);

            $result = $response->json('result', []);

            return [
                'lat' => data_get($result, 'geometry.location.lat'),
                'lng' => data_get($result, 'geometry.location.lng'),
                'address' => $result['formatted_address'] ?? null,
            ];
        });
    }

    /**
     * Reverse-geocode coordinates into a formatted address.
     *
     * @return array{address: string|null}
     */
    public function reverseGeocode(float $lat, float $lng, string $language = 'ar', ?string $region = null): array
    {
        $cacheKey = 'maps:rg:' . md5(round($lat, 4) . '|' . round($lng, 4) . '|' . $language);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($lat, $lng, $language, $region) {
            $params = [
                'latlng' => "{$lat},{$lng}",
                'language' => $language,
                'key' => $this->key(),
            ];

            if ($region) {
                $params['region'] = $region;
            }

            $response = $this->client()->get("{$this->baseUrl}/geocode/json", $params);

            return [
                'address' => data_get($response->json('results', []), '0.formatted_address'),
            ];
        });
    }

    /**
     * Route between two points, polyline decoded server-side.
     *
     * @return array{points: array<int, array{lat: float, lng: float}>, eta_seconds: int|null, distance_meters: int|null}
     */
    public function directions(float $fromLat, float $fromLng, float $toLat, float $toLng, string $mode = 'driving'): array
    {
        $response = $this->client()->get("{$this->baseUrl}/directions/json", [
            'origin' => "{$fromLat},{$fromLng}",
            'destination' => "{$toLat},{$toLng}",
            'mode' => $mode,
            'key' => $this->key(),
        ]);

        $route = data_get($response->json('routes', []), '0');

        if (! $route) {
            return ['points' => [], 'eta_seconds' => null, 'distance_meters' => null];
        }

        $leg = data_get($route, 'legs.0', []);

        return [
            'points' => $this->decodePolyline((string) data_get($route, 'overview_polyline.points', '')),
            'eta_seconds' => data_get($leg, 'duration.value'),
            'distance_meters' => data_get($leg, 'distance.value'),
        ];
    }

    /**
     * Decode a Google encoded polyline into [{lat, lng}, ...].
     *
     * @return array<int, array{lat: float, lng: float}>
     */
    private function decodePolyline(string $encoded): array
    {
        $points = [];
        $index = 0;
        $lat = 0;
        $lng = 0;
        $length = strlen($encoded);

        while ($index < $length) {
            foreach (['lat', 'lng'] as $coord) {
                $shift = 0;
                $result = 0;

                do {
                    $byte = ord($encoded[$index++]) - 63;
                    $result |= ($byte & 0x1f) << $shift;
                    $shift += 5;
                } while ($byte >= 0x20 && $index < $length);

                $delta = ($result & 1) ? ~($result >> 1) : ($result >> 1);
                $$coord += $delta;
            }

            $points[] = ['lat' => $lat / 1e5, 'lng' => $lng / 1e5];
        }

        return $points;
    }
}
