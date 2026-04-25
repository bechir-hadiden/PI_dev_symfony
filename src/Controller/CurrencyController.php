<?php
// src/Controller/CurrencyController.php

namespace App\Controller;

use App\Service\CurrencyConverterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/currency')]
class CurrencyController extends AbstractController
{
    public function __construct(
        private CurrencyConverterService $currencyService
    ) {}

    #[Route('/convert', methods: ['GET'])]
    public function convert(Request $request): JsonResponse
    {
        $amount   = (float) $request->query->get('amount', 1);
        $from     = strtoupper($request->query->get('from', 'EUR'));
        $to       = strtoupper($request->query->get('to', 'USD'));

        try {
            $result = $this->currencyService->convert($amount, $from, $to);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/rates/{base}', methods: ['GET'])]
    public function rates(string $base = 'EUR'): JsonResponse
    {
        $rates = $this->currencyService->getRates(strtoupper($base));
        return $this->json(['base' => $base, 'rates' => $rates]);
    }

    #[Route('/currencies', methods: ['GET'])]
    public function currencies(): JsonResponse
    {
        return $this->json($this->currencyService->getCurrencies());
    }
}