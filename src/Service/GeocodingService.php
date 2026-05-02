<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodingService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Convertit une adresse en coordonnées (Nominatim - OpenStreetMap)
     */
    public function geocode(string $address): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                    'accept-language' => 'fr'
                ],
                'headers' => [
                    'User-Agent' => 'SmartTrip/1.0'
                ]
            ]);

            $data = $response->toArray();

            if (!empty($data)) {
                return [
                    'lat' => floatval($data[0]['lat']),
                    'lng' => floatval($data[0]['lon']),
                    'formatted_address' => $data[0]['display_name'],
                    'city' => $data[0]['address']['city'] ?? $data[0]['address']['town'] ?? $data[0]['address']['village'] ?? null,
                    'country' => $data[0]['address']['country'] ?? null,
                    'postcode' => $data[0]['address']['postcode'] ?? null
                ];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Récupère les suggestions de lieux (autocomplétion)
     */
    public function autocomplete(string $input): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $input,
                    'format' => 'json',
                    'limit' => 5,
                    'addressdetails' => 1,
                    'accept-language' => 'fr'
                ],
                'headers' => [
                    'User-Agent' => 'SmartTrip/1.0'
                ]
            ]);

            $data = $response->toArray();
            $results = [];

            foreach ($data as $item) {
                $results[] = [
                    'description' => $item['display_name'],
                    'lat' => $item['lat'],
                    'lng' => $item['lon']
                ];
            }
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Inverse géocodage (coordonnées -> adresse)
     */
    public function reverseGeocode(float $lat, float $lng): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/reverse', [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'accept-language' => 'fr'
                ],
                'headers' => [
                    'User-Agent' => 'SmartTrip/1.0'
                ]
            ]);

            $data = $response->toArray();

            return [
                'address' => $data['display_name'] ?? null,
                'city' => $data['address']['city'] ?? $data['address']['town'] ?? $data['address']['village'] ?? null,
                'country' => $data['address']['country'] ?? null
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}