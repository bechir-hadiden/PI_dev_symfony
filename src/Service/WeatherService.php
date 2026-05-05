<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Météo actuelle par coordonnées
     */
    public function getCurrentWeather(float $lat, float $lng): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                'query' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'current_weather' => true,
                    'hourly' => 'temperature_2m,relativehumidity_2m,windspeed_10m',
                    'timezone' => 'auto'
                ]
            ]);

            $data = $response->toArray();

            if (isset($data['current_weather'])) {
                $weather = $data['current_weather'];
                return [
                    'temperature' => round($weather['temperature']),
                    'windspeed' => $weather['windspeed'],
                    'weathercode' => $weather['weathercode'],
                    'description' => $this->getWeatherDescription($weather['weathercode']),
                    'icon' => $this->getWeatherIcon($weather['weathercode']),
                    'humidity' => $data['hourly']['relativehumidity_2m'][0] ?? null
                ];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Prévisions météo 5 jours
     */
    public function getForecast(float $lat, float $lng): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                'query' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'daily' => 'weathercode,temperature_2m_max,temperature_2m_min',
                    'timezone' => 'auto',
                    'forecast_days' => 5
                ]
            ]);

            $data = $response->toArray();
            $forecast = [];

            if (isset($data['daily'])) {
                for ($i = 0; $i < count($data['daily']['time']); $i++) {
                    $forecast[] = [
                        'date' => $data['daily']['time'][$i],
                        'temp_max' => round($data['daily']['temperature_2m_max'][$i]),
                        'temp_min' => round($data['daily']['temperature_2m_min'][$i]),
                        'weathercode' => $data['daily']['weathercode'][$i],
                        'description' => $this->getWeatherDescription($data['daily']['weathercode'][$i]),
                        'icon' => $this->getWeatherIcon($data['daily']['weathercode'][$i])
                    ];
                }
            }
            return $forecast;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Traduction des codes météo
     */
    private function getWeatherDescription(int $code): string
    {
        $descriptions = [
            0 => 'Ciel dégagé',
            1 => 'Principalement dégagé',
            2 => 'Partiellement nuageux',
            3 => 'Couvert',
            45 => 'Brouillard',
            48 => 'Brouillard givrant',
            51 => 'Bruine légère',
            53 => 'Bruine modérée',
            55 => 'Bruine dense',
            56 => 'Bruine verglaçante',
            57 => 'Bruine verglaçante dense',
            61 => 'Pluie légère',
            63 => 'Pluie modérée',
            65 => 'Pluie forte',
            66 => 'Pluie verglaçante légère',
            67 => 'Pluie verglaçante forte',
            71 => 'Neige légère',
            73 => 'Neige modérée',
            75 => 'Neige forte',
            77 => 'Grains de neige',
            80 => 'Averses de pluie légères',
            81 => 'Averses de pluie modérées',
            82 => 'Averses de pluie violentes',
            85 => 'Averses de neige légères',
            86 => 'Averses de neige fortes',
            95 => 'Orage',
            96 => 'Orage avec grêle légère',
            99 => 'Orage avec grêle forte'
        ];
        return $descriptions[$code] ?? 'Météo inconnue';
    }

    /**
     * Icône FontAwesome
     */
    private function getWeatherIcon(int $code): string
    {
        if ($code == 0) return 'fa-sun';
        if ($code <= 3) return 'fa-cloud-sun';
        if ($code <= 45) return 'fa-smog';
        if ($code <= 57) return 'fa-cloud-rain';
        if ($code <= 67) return 'fa-cloud-showers-heavy';
        if ($code <= 77) return 'fa-snowflake';
        if ($code <= 86) return 'fa-cloud-rain';
        return 'fa-cloud-bolt';
    }

    /**
     * Conseils météo
     */
    public function getWeatherTips(float $temperature, string $description): array
    {
        $tips = [];

        if ($temperature < 5) {
            $tips[] = '❄️ Très froid ! N\'oubliez pas vos vêtements chauds.';
        } elseif ($temperature < 15) {
            $tips[] = '🍂 Frais, prévoyez une veste.';
        } elseif ($temperature < 25) {
            $tips[] = '☀️ Température agréable, parfait pour visiter !';
        } else {
            $tips[] = '🌡️ Chaud ! Pensez à l\'eau et à la crème solaire.';
        }

        if (str_contains($description, 'pluie')) {
            $tips[] = '☔ Risque de pluie, emportez un parapluie.';
        }

        if (str_contains($description, 'neige')) {
            $tips[] = '⛄ Neige prévue, soyez prudent sur la route.';
        }

        if (str_contains($description, 'orage')) {
            $tips[] = '⚡ Orages possibles, restez informé.';
        }

        return $tips;
    }
}