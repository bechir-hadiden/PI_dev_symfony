<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de Conversion de Devises
 */
class CurrencyService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Récupère le taux de change TND -> EUR
     */
    public function getExchangeRate(): float
    {
        try {
            // Utilisation d'une API de change gratuite
            $response = $this->httpClient->request('GET', 'https://api.exchangerate-api.com/v4/latest/TND');
            
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return (float) ($data['rates']['EUR'] ?? 0.30); // Fallback à 0.30 si KO
            }
        } catch (\Exception $e) {
            $this->logger->error("Erreur API Change : " . $e->getMessage());
        }

        return 0.30; // Valeur par défaut
    }
}
