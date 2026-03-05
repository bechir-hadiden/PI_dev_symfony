<?php

namespace App\Service;

use App\Repository\DestinationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YouTubeService
{
    public function __construct(
        private HttpClientInterface   $httpClient,
        private DestinationRepository $destinationRepository,
        private EntityManagerInterface $em,
        #[Autowire('%env(YOUTUBE_API_KEY)%')]
        private string $apiKey = '',
    ) {}

    /**
     * Retourne l'URL YouTube DIRECTE (watch?v=ID) pour une destination.
     * 1. Cherche l'ID en BDD (video_url)
     * 2. Si vide → appelle l'API YouTube → sauvegarde l'ID en BDD
     */
    public function getVideoUrl(int $destinationId, string $nomVille): ?string
    {
        $destination = $this->destinationRepository->find($destinationId);

        // 1️⃣ Déjà en BDD → retourne directement
        if ($destination && $destination->getVideoUrl()) {
            return 'https://www.youtube.com/watch?v=' . $destination->getVideoUrl();
        }

        // 2️⃣ Pas en BDD → cherche sur YouTube API
        if (!$this->apiKey) return null;

        $videoId = $this->fetchFirstVideoId($nomVille . ' travel destination');

        if (!$videoId) return null;

        // 3️⃣ Sauvegarde l'ID en BDD pour éviter de rappeler l'API
        if ($destination) {
            $destination->setVideoUrl($videoId);
            $this->em->flush();
        }

        return 'https://www.youtube.com/watch?v=' . $videoId;
    }

    /**
     * Appelle l'API YouTube et retourne le videoId de la 1ère vidéo trouvée
     */
    private function fetchFirstVideoId(string $query): ?string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/search', [
                'query' => [
                    'part'       => 'id',
                    'q'          => $query,
                    'type'       => 'video',
                    'maxResults' => 1,
                    'key'        => $this->apiKey,
                ],
            ]);

            $data = $response->toArray();

            return $data['items'][0]['id']['videoId'] ?? null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Recherche plusieurs vidéos — utilisé par le formulaire
     */
    public function searchVideos(string $query, int $maxResults = 6): array
    {
        if (!$this->apiKey) return [];

        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/search', [
                'query' => [
                    'part'       => 'snippet',
                    'q'          => $query,
                    'type'       => 'video',
                    'maxResults' => $maxResults,
                    'key'        => $this->apiKey,
                ],
            ]);

            $data = $response->toArray();

            return array_map(fn($item) => [
                'videoId'   => $item['id']['videoId'],
                'title'     => $item['snippet']['title'],
                'thumbnail' => $item['snippet']['thumbnails']['medium']['url'],
                'channel'   => $item['snippet']['channelTitle'],
            ], $data['items'] ?? []);

        } catch (\Exception $e) {
            return [];
        }
    }
}