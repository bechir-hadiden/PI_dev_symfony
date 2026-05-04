<?php
// src/Service/CurrencyConverterService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CurrencyConverterService
{
    private const API_URL = 'https://api.frankfurter.app';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache
    ) {}

    /**
     * Convertit un montant d'une devise à une autre
     */
    public function convert(float $amount, string $from, string $to): array
    {
        $rates = $this->getRates($from);

        if (!isset($rates[$to])) {
            throw new \InvalidArgumentException("Devise '$to' non supportée.");
        }

        $convertedAmount = $amount * $rates[$to];

        return [
            'from'      => $from,
            'to'        => $to,
            'amount'    => $amount,
            'result'    => round($convertedAmount, 2),
            'rate'      => $rates[$to],
            'date'      => $this->getLatestDate(),
        ];
    }

    /**
     * Récupère les taux (mis en cache 1h)
     */
    public function getRates(string $baseCurrency = 'EUR'): array
    {
        $cacheKey = 'currency_rates_' . $baseCurrency;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($baseCurrency) {
            $item->expiresAfter(3600); // Cache 1 heure

            $response = $this->httpClient->request('GET', self::API_URL . '/latest', [
                'query' => ['from' => $baseCurrency],
            ]);

            $data = $response->toArray();
            return $data['rates'];
        });
    }

    /**
     * Récupère la liste des devises disponibles
     */
    public function getCurrencies(): array
    {
        return $this->cache->get('currency_list', function (ItemInterface $item) {
            $item->expiresAfter(86400); // Cache 24h

            $response = $this->httpClient->request('GET', self::API_URL . '/currencies');
            return $response->toArray();
        });
    }

    private function getLatestDate(): string
    {
        $response = $this->httpClient->request('GET', self::API_URL . '/latest');
        $data = $response->toArray();
        return $data['date'];
    }
}