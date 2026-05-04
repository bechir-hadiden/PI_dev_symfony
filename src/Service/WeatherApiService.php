<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service consommant l'API Open-Meteo pour obtenir des conseils de voyage.
 * Répond au critère : API B (API + Métier).
 */
class WeatherApiService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Récupère la météo actuelle pour une latitude et longitude données.
     * Par défaut Tunis (36.8, 10.1).
     */
    public function getTravelAdvice(float $lat = 36.8065, float $lon = 10.1815): array
    {
        try {
            $url = sprintf(
                'https://api.open-meteo.com/v1/forecast?latitude=%f&longitude=%f&current_weather=true',
                $lat,
                $lon
            );

            $response = $this->client->request('GET', $url);
            $data = $response->toArray();

            $temp = $data['current_weather']['temperature'] ?? null;
            $windspeed = $data['current_weather']['windspeed'] ?? null;
            $weatherCode = $data['current_weather']['weathercode'] ?? null;

            // Logique métier basée sur les résultats de l'API (Métier + API)
            $status = 'Safe';
            $message = 'Conditions idéales pour voyager.';
            $color = 'success';

            // Codes météo critiques (Orage, Neige forte, etc.)
            if (in_array($weatherCode, [95, 96, 99, 75, 82])) {
                $status = 'Danger';
                $message = 'Alerte météo : Conditions dangereuses ! Vols et bus déconseillés.';
                $color = 'danger';
            } elseif ($windspeed > 40 || $temp > 40 || $temp < 0) {
                $status = 'Warning';
                $message = 'Conditions difficiles (Vent fort ou Température extrême). Soyez prudent.';
                $color = 'warning';
            }

            return [
                'status' => $status,
                'message' => $message,
                'color' => $color,
                'temperature' => $temp,
                'windspeed' => $windspeed
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'Unknown',
                'message' => 'Impossible de récupérer les conditions météo.',
                'color' => 'secondary',
                'temperature' => null,
                'windspeed' => null
            ];
        }
    }
}
