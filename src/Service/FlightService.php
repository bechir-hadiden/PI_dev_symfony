<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FlightService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $aviationStackApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $aviationStackApiKey;
    }

    public function getAllFlightsToDestination(string $destination, int $limit = 15): array
    {
        $destCode = $this->getAirportCode($destination);
        
        // Si la clé API est vide, utiliser les données de démonstration
        if (empty($this->apiKey) || $this->apiKey === 'e163115bf39130e4be898b2b05dc4ba3') {
            return $this->getDemoFlights($destCode, $destination);
        }
        
        try {
            $response = $this->httpClient->request('GET', 'https://api.aviationstack.com/v1/flights', [
                'query' => [
                    'access_key' => $this->apiKey,
                    'arr_iata' => $destCode,
                    'limit' => $limit,
                    'flight_status' => 'scheduled'
                ],
                'timeout' => 10
            ]);

            $data = $response->toArray();
            
            if (isset($data['data']) && !empty($data['data'])) {
                $flights = [];
                foreach ($data['data'] as $flight) {
                    $flights[] = [
                        'airline' => $flight['airline']['name'] ?? $flight['airline']['iata'] ?? 'Compagnie inconnue',
                        'flight_number' => $flight['flight']['iata'] ?? $flight['flight']['number'] ?? 'N/A',
                        'departure' => [
                            'airport' => $flight['departure']['iata'] ?? 'N/A',
                            'city' => $flight['departure']['city'] ?? '',
                            'time' => $flight['departure']['scheduled'] ?? date('Y-m-d\TH:i:s')
                        ],
                        'arrival' => [
                            'airport' => $flight['arrival']['iata'] ?? $destCode,
                            'city' => $flight['arrival']['city'] ?? $destination,
                            'time' => $flight['arrival']['scheduled'] ?? date('Y-m-d\TH:i:s', strtotime('+2 hours'))
                        ],
                        'status' => $this->getStatusFrench($flight['flight_status'] ?? 'scheduled'),
                        'price' => rand(80, 300),
                        'currency' => 'EUR',
                        'booking_url' => $this->getBookingUrl($flight['airline']['name'] ?? '')
                    ];
                }
                
                return [
                    'success' => true,
                    'source' => 'aviationstack',
                    'count' => count($flights),
                    'flights' => $flights,
                    'airport_code' => $destCode,
                    'city' => $destination
                ];
            }
            
            return $this->getDemoFlights($destCode, $destination);
            
        } catch (\Exception $e) {
            return $this->getDemoFlights($destCode, $destination);
        }
    }

    private function getStatusFrench(string $status): string
    {
        return match($status) {
            'scheduled' => 'Programmé',
            'active' => 'En vol',
            'landed' => 'Atterri',
            'cancelled' => 'Annulé',
            'delayed' => 'Retardé',
            default => 'Programmé'
        };
    }

    private function getBookingUrl(string $airline): string
    {
        $urls = [
            'Air France' => 'https://www.airfrance.fr',
            'Ryanair' => 'https://www.ryanair.com',
            'EasyJet' => 'https://www.easyjet.com',
            'Transavia' => 'https://www.transavia.com',
            'Vueling' => 'https://www.vueling.com',
            'British Airways' => 'https://www.britishairways.com',
            'Lufthansa' => 'https://www.lufthansa.com',
            'Emirates' => 'https://www.emirates.com',
            'Qatar Airways' => 'https://www.qatarairways.com',
            'Turkish Airlines' => 'https://www.turkishairlines.com',
            'Royal Air Maroc' => 'https://www.royalairmaroc.com',
            'Tunisair' => 'https://www.tunisair.com'
        ];
        
        foreach ($urls as $name => $url) {
            if (stripos($airline, $name) !== false) {
                return $url;
            }
        }
        return 'https://www.skyscanner.fr';
    }

    public function getAirportCode(string $city): string
    {
        $airports = [
            'paris' => 'CDG',
            'marrakech' => 'RAK',
            'casablanca' => 'CMN',
            'tunis' => 'TUN',
            'djerba' => 'DJE',
            'new york' => 'JFK',
            'londres' => 'LHR',
            'rome' => 'FCO',
            'barcelone' => 'BCN',
            'dubai' => 'DXB',
            'tokyo' => 'HND',
            'bangkok' => 'BKK',
            'madrid' => 'MAD',
            'berlin' => 'BER',
            'amsterdam' => 'AMS',
            'lisbonne' => 'LIS',
            'athenes' => 'ATH',
            'istanbul' => 'IST'
        ];
        
        $cityLower = strtolower(explode(',', $city)[0]);
        return $airports[$cityLower] ?? 'CDG';
    }

    private function getDemoFlights(string $destCode, string $destination): array
    {
        // Définir les prix par destination
        $prices = [
            'CDG' => 150, 'RAK' => 200, 'CMN' => 220, 'TUN' => 180, 'DJE' => 190,
            'JFK' => 450, 'LHR' => 120, 'FCO' => 180, 'BCN' => 140, 'DXB' => 550,
            'HND' => 800, 'BKK' => 650, 'MAD' => 130, 'BER' => 160, 'AMS' => 140,
            'LIS' => 130, 'ATH' => 170, 'IST' => 200
        ];
        
        $price = $prices[$destCode] ?? 250;
        
        $airlines = [
            ['name' => 'Air France', 'code' => 'AF', 'url' => 'https://www.airfrance.fr', 'price' => $price],
            ['name' => 'Ryanair', 'code' => 'FR', 'url' => 'https://www.ryanair.com', 'price' => round($price * 0.7)],
            ['name' => 'EasyJet', 'code' => 'U2', 'url' => 'https://www.easyjet.com', 'price' => round($price * 0.8)],
            ['name' => 'Transavia', 'code' => 'TO', 'url' => 'https://www.transavia.com', 'price' => round($price * 0.85)],
            ['name' => 'Vueling', 'code' => 'VY', 'url' => 'https://www.vueling.com', 'price' => round($price * 0.75)]
        ];
        
        $origins = [
            ['code' => 'CDG', 'city' => 'Paris'],
            ['code' => 'ORY', 'city' => 'Paris'],
            ['code' => 'LYS', 'city' => 'Lyon'],
            ['code' => 'MRS', 'city' => 'Marseille'],
            ['code' => 'NCE', 'city' => 'Nice'],
            ['code' => 'TLS', 'city' => 'Toulouse'],
            ['code' => 'BOD', 'city' => 'Bordeaux']
        ];
        
        $times = ['08:00', '10:30', '14:20', '16:45', '18:30', '21:15'];
        
        $flights = [];
        for ($i = 0; $i < min(8, count($airlines) * 2); $i++) {
            $airline = $airlines[$i % count($airlines)];
            $origin = $origins[$i % count($origins)];
            $time = $times[$i % count($times)];
            $hour = (int)substr($time, 0, 2);
            
            $flights[] = [
                'airline' => $airline['name'],
                'flight_number' => $airline['code'] . rand(100, 999),
                'departure' => [
                    'airport' => $origin['code'],
                    'city' => $origin['city'],
                    'time' => date('Y-m-d') . 'T' . $time . ':00'
                ],
                'arrival' => [
                    'airport' => $destCode,
                    'city' => $destination,
                    'time' => date('Y-m-d') . 'T' . sprintf('%02d', $hour + 2) . ':' . substr($time, 3) . ':00'
                ],
                'status' => 'Programmé',
                'price' => $airline['price'],
                'currency' => 'EUR',
                'booking_url' => $airline['url']
            ];
        }
        
        // Trier par prix
        usort($flights, fn($a, $b) => $a['price'] <=> $b['price']);
        
        return [
            'success' => true,
            'source' => 'demo',
            'count' => count($flights),
            'flights' => $flights,
            'airport_code' => $destCode,
            'city' => $destination
        ];
    }
}