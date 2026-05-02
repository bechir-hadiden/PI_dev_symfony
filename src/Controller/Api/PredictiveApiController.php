<?php

namespace App\Controller\Api;

use App\Repository\AvisRepository;
use App\Service\PredictiveAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PredictiveApiController extends AbstractController
{
    #[Route('/api/predictive/stats', name: 'api_predictive_stats', methods: ['GET'])]
    public function getStats(PredictiveAnalysisService $predictiveAnalysis): JsonResponse
    {
        $stats = $predictiveAnalysis->getPredictiveStats();
        
        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    #[Route('/api/predictive/analyze-all', name: 'api_predictive_all', methods: ['GET'])]
    public function analyzeAll(PredictiveAnalysisService $predictiveAnalysis, AvisRepository $avisRepository): JsonResponse
    {
        $allAvis = $avisRepository->findAll();
        $results = [];
        
        foreach ($allAvis as $avis) {
            $results[] = [
                'id' => $avis->getId(),
                'nom' => $avis->getNomClient(),
                'commentaire' => substr($avis->getCommentaire(), 0, 100),
                'note' => $avis->getNote(),
                'prediction' => $predictiveAnalysis->predictSatisfaction($avis)
            ];
        }
        
        return $this->json([
            'success' => true,
            'count' => count($results),
            'data' => $results
        ]);
    }
    
    #[Route('/api/predictive/risk-alerts', name: 'api_predictive_alerts', methods: ['GET'])]
    public function getRiskAlerts(PredictiveAnalysisService $predictiveAnalysis, AvisRepository $avisRepository): JsonResponse
    {
        $allAvis = $avisRepository->findAll();
        $alerts = [];
        
        foreach ($allAvis as $avis) {
            $prediction = $predictiveAnalysis->predictSatisfaction($avis);
            if ($prediction['risque']['niveau'] === 'Élevé') {
                $alerts[] = [
                    'id' => $avis->getId(),
                    'nom' => $avis->getNomClient(),
                    'commentaire' => substr($avis->getCommentaire(), 0, 100),
                    'note' => $avis->getNote(),
                    'score' => $prediction['satisfaction_score'],
                    'alert' => $prediction['alert']
                ];
            }
        }
        
        return $this->json([
            'success' => true,
            'count' => count($alerts),
            'alerts' => $alerts
        ]);
    }
    
    #[Route('/api/predictive/avis/{id}', name: 'api_predictive_avis', methods: ['GET'])]
    public function analyzeAvis(int $id, PredictiveAnalysisService $predictiveAnalysis, AvisRepository $avisRepository): JsonResponse
    {
        $avis = $avisRepository->find($id);
        
        if (!$avis) {
            return $this->json([
                'success' => false,
                'error' => 'Avis non trouvé'
            ], 404);
        }
        
        $prediction = $predictiveAnalysis->predictSatisfaction($avis);
        
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $avis->getId(),
                'nom' => $avis->getNomClient(),
                'commentaire' => $avis->getCommentaire(),
                'note' => $avis->getNote(),
                'prediction' => $prediction
            ]
        ]);
    }
}