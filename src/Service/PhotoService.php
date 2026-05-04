<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PhotoService
{
    private HttpClientInterface $httpClient;
    private string $accessKey;

    public function __construct(HttpClientInterface $httpClient, string $unsplashAccessKey)
    {
        $this->httpClient = $httpClient;
        $this->accessKey = $unsplashAccessKey;
    }

    /**
     * Recherche des photos par mot-clé
     */
    public function searchPhotos(string $query, int $perPage = 5): array
    {
        if (empty($this->accessKey)) {
            return $this->getFallbackPhotos($query);
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.unsplash.com/search/photos', [
                'query' => [
                    'query' => $query,
                    'per_page' => $perPage,
                    'orientation' => 'landscape'
                ],
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->accessKey,
                ],
                'timeout' => 10
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->getFallbackPhotos($query);
            }

            $data = $response->toArray();
            $photos = [];

            if (isset($data['results']) && !empty($data['results'])) {
                foreach ($data['results'] as $result) {
                    $photos[] = [
                        'id' => $result['id'],
                        'url' => $result['urls']['regular'],
                        'thumb' => $result['urls']['thumb'],
                        'small' => $result['urls']['small'],
                        'description' => $result['description'] ?? $result['alt_description'] ?? ucfirst($query),
                        'photographer' => $result['user']['name'] ?? 'Unknown',
                        'photographer_url' => $result['user']['links']['html'] ?? '#',
                        'likes' => $result['likes'] ?? 0
                    ];
                }
            }

            return [
                'success' => true,
                'total' => $data['total'] ?? count($photos),
                'photos' => $photos
            ];
        } catch (\Exception $e) {
            return $this->getFallbackPhotos($query);
        }
    }

    /**
     * Récupère une photo aléatoire
     */
    public function getRandomPhoto(string $query): ?array
    {
        if (empty($this->accessKey)) {
            return $this->getFallbackPhoto($query);
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.unsplash.com/photos/random', [
                'query' => [
                    'query' => $query,
                    'orientation' => 'landscape'
                ],
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->accessKey,
                ],
                'timeout' => 10
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->getFallbackPhoto($query);
            }

            $data = $response->toArray();
            
            // Si c'est un tableau (plusieurs photos) ou un objet unique
            $photo = is_array($data) && isset($data[0]) ? $data[0] : $data;
            
            return [
                'id' => $photo['id'],
                'url' => $photo['urls']['regular'],
                'thumb' => $photo['urls']['thumb'],
                'small' => $photo['urls']['small'],
                'description' => $photo['description'] ?? $photo['alt_description'] ?? ucfirst($query),
                'photographer' => $photo['user']['name'] ?? 'Unknown',
                'photographer_url' => $photo['user']['links']['html'] ?? '#',
                'likes' => $photo['likes'] ?? 0
            ];
        } catch (\Exception $e) {
            return $this->getFallbackPhoto($query);
        }
    }

    /**
     * Photos de secours (gratuites)
     */
    private function getFallbackPhotos(string $query): array
    {
        $fallbackPhotos = [
            'paris' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34',
            'marrakech' => 'https://images.unsplash.com/photo-1532423622396-10a3f979251a',
            'new york' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9',
            'tokyo' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf',
            'rome' => 'https://images.unsplash.com/photo-1552832230-c0197dd311b5',
            'barcelone' => 'https://images.unsplash.com/photo-1583422409516-2895a77efded',
            'londres' => 'https://images.unsplash.com/photo-1513635260975-26863e365acb',
            'dubai' => 'https://images.unsplash.com/photo-1518684079-3c830dcef090',
            'bangkok' => 'https://images.unsplash.com/photo-1508009603885-50cf7c579365',
            'default' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800'
        ];

        $key = strtolower(explode(',', $query)[0]);
        $url = $fallbackPhotos[$key] ?? $fallbackPhotos['default'];
        
        return [
            'success' => false,
            'total' => 1,
            'photos' => [[
                'id' => 'fallback',
                'url' => $url . '?w=800&h=600&fit=crop',
                'thumb' => $url . '?w=150&h=150&fit=crop',
                'small' => $url . '?w=400&h=300&fit=crop',
                'description' => ucfirst($query),
                'photographer' => 'Unsplash',
                'photographer_url' => 'https://unsplash.com',
                'likes' => 0
            ]]
        ];
    }

    private function getFallbackPhoto(string $query): ?array
    {
        $photos = $this->getFallbackPhotos($query);
        return $photos['photos'][0] ?? null;
    }
}