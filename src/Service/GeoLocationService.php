<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de Géolocalisation par IP
 */
class GeoLocationService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Récupère le pays d'une adresse IP via l'API ipinfo.io
     * 
     * @param string $ip
     * @return string Le nom du pays (ou 'Tunisie' par défaut si erreur/localhost)
     */
    public function getCountryByIp(string $ip): string
    {
        // Cas spécial pour localhost lors des tests
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Tunisie';
        }

        try {
            $this->logger->info("Appel API Géolocalisation pour IP : $ip");
            
            // Appel à l'API ipinfo.io (gratuit pour 50k requêtes/mois)
            $response = $this->httpClient->request('GET', "https://ipinfo.io/{$ip}/json");
            
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                
                // Correspondance des codes pays (Ex: TN => Tunisie, FR => France)
                $countryCode = $data['country'] ?? 'TN';
                
                if ($countryCode === 'TN') return 'Tunisie';
                if ($countryCode === 'FR') return 'France';
                
                return 'International';
            }
        } catch (\Exception $e) {
            $this->logger->error("Erreur Géolocalisation IP : " . $e->getMessage());
        }

        return 'Tunisie'; // Fallback sécurisé
    }
}
