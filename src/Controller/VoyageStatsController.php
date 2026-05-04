<?php

namespace App\Controller;

use App\Repository\AvisRepository;
use App\Service\PredictiveAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VoyageStatsController extends AbstractController
{
    #[Route('/voyage-stats', name: 'app_voyage_stats')]
    public function index(AvisRepository $avisRepository, PredictiveAnalysisService $predictiveAnalysis): Response
    {
        $avisList = $avisRepository->findAll();
        
        // Grouper les avis par voyage
        $voyagesStats = [];
        foreach ($avisList as $avis) {
            $voyageId = $avis->getVoyageId();
            if (!isset($voyagesStats[$voyageId])) {
                $voyagesStats[$voyageId] = [
                    'voyage_id' => $voyageId,
                    'total_avis' => 0,
                    'notes' => [],
                    'satisfaction_scores' => [],
                    'sentiments' => [
                        'positif' => 0,
                        'negatif' => 0,
                        'neutre' => 0
                    ],
                    'keywords' => [],
                ];
            }
            
            $voyagesStats[$voyageId]['total_avis']++;
            $voyagesStats[$voyageId]['notes'][] = $avis->getNote();
            if ($avis->getSatisfactionScore() !== null) {
                $voyagesStats[$voyageId]['satisfaction_scores'][] = $avis->getSatisfactionScore();
            }
            
            // Sentiment à partir de l'analyse de sentiment (si disponible)
            $sentimentAnalysis = $avis->getSentimentAnalysis();
            if ($sentimentAnalysis && isset($sentimentAnalysis['sentiment'])) {
                $sentiment = $sentimentAnalysis['sentiment'];
                if ($sentiment === 'positif') {
                    $voyagesStats[$voyageId]['sentiments']['positif']++;
                } elseif ($sentiment === 'negatif') {
                    $voyagesStats[$voyageId]['sentiments']['negatif']++;
                } else {
                    $voyagesStats[$voyageId]['sentiments']['neutre']++;
                }
            } else {
                // Approximation basée sur la note
                if ($avis->getNote() >= 4) {
                    $voyagesStats[$voyageId]['sentiments']['positif']++;
                } elseif ($avis->getNote() <= 2) {
                    $voyagesStats[$voyageId]['sentiments']['negatif']++;
                } else {
                    $voyagesStats[$voyageId]['sentiments']['neutre']++;
                }
            }
            
            // Mots-clés (si disponibles)
            $keywords = $avis->getKeywords();
            if ($keywords) {
                foreach ($keywords as $keyword) {
                    $voyagesStats[$voyageId]['keywords'][$keyword] = ($voyagesStats[$voyageId]['keywords'][$keyword] ?? 0) + 1;
                }
            }
        }
        
        // Calcul des moyennes et préparation des données finales
        $voyagesData = [];
        foreach ($voyagesStats as $voyageId => $data) {
            $noteMoyenne = count($data['notes']) > 0 ? array_sum($data['notes']) / count($data['notes']) : 0;
            $satisfactionMoyenne = count($data['satisfaction_scores']) > 0 
                ? array_sum($data['satisfaction_scores']) / count($data['satisfaction_scores']) 
                : null;
            
            // Top 5 mots-clés
            arsort($data['keywords']);
            $topKeywords = array_slice($data['keywords'], 0, 5, true);
            
            // Déterminer le niveau de risque global (basé sur les scores de satisfaction)
            $globalRisk = null;
            if ($satisfactionMoyenne !== null) {
                $globalRisk = $predictiveAnalysis->calculateRisk($satisfactionMoyenne);
            }
            
            $voyagesData[] = [
                'voyage_id' => $voyageId,
                'total_avis' => $data['total_avis'],
                'note_moyenne' => round($noteMoyenne, 1),
                'satisfaction_moyenne' => $satisfactionMoyenne !== null ? round($satisfactionMoyenne, 1) : null,
                'sentiments' => $data['sentiments'],
                'top_keywords' => $topKeywords,
                'risque' => $globalRisk,
                'recommendation_level' => $this->getRecommendationLevel($noteMoyenne, $satisfactionMoyenne)
            ];
        }
        
        // Trier par satisfaction moyenne décroissante (si disponible), sinon par note moyenne
        usort($voyagesData, function($a, $b) {
            $aScore = $a['satisfaction_moyenne'] ?? $a['note_moyenne'];
            $bScore = $b['satisfaction_moyenne'] ?? $b['note_moyenne'];
            return $bScore <=> $aScore;
        });
        
        // Top recommandations (5 premiers)
        $recommendations = array_slice($voyagesData, 0, 5);
        
        return $this->render('voyage_stats/index.html.twig', [
            'voyages' => $voyagesData,
            'recommendations' => $recommendations,
            'stats_globales' => $this->getGlobalStats($avisList)
        ]);
    }
    
    private function getRecommendationLevel(float $noteMoyenne, ?float $satisfactionMoyenne): string
    {
        $score = $satisfactionMoyenne ?? ($noteMoyenne * 20); // transformation approximative
        if ($score >= 80) return 'Fortement recommandé';
        if ($score >= 60) return 'Recommandé';
        if ($score >= 40) return 'Moyennement recommandé';
        if ($score >= 20) return 'Déconseillé';
        return 'Fortement déconseillé';
    }
    
    private function getGlobalStats(array $avisList): array
    {
        $total = count($avisList);
        if ($total === 0) {
            return [
                'total_avis' => 0,
                'note_moyenne_globale' => 0,
                'satisfaction_moyenne_globale' => 0,
                'sentiments' => ['positif' => 0, 'negatif' => 0, 'neutre' => 0]
            ];
        }
        
        $notes = [];
        $satisfactions = [];
        $sentiments = ['positif' => 0, 'negatif' => 0, 'neutre' => 0];
        
        foreach ($avisList as $avis) {
            $notes[] = $avis->getNote();
            if ($avis->getSatisfactionScore() !== null) {
                $satisfactions[] = $avis->getSatisfactionScore();
            }
            
            $sentimentAnalysis = $avis->getSentimentAnalysis();
            if ($sentimentAnalysis && isset($sentimentAnalysis['sentiment'])) {
                $sentiment = $sentimentAnalysis['sentiment'];
                if ($sentiment === 'positif') $sentiments['positif']++;
                elseif ($sentiment === 'negatif') $sentiments['negatif']++;
                else $sentiments['neutre']++;
            } else {
                if ($avis->getNote() >= 4) $sentiments['positif']++;
                elseif ($avis->getNote() <= 2) $sentiments['negatif']++;
                else $sentiments['neutre']++;
            }
        }
        
        return [
            'total_avis' => $total,
            'note_moyenne_globale' => round(array_sum($notes) / $total, 1),
            'satisfaction_moyenne_globale' => count($satisfactions) > 0 ? round(array_sum($satisfactions) / count($satisfactions), 1) : null,
            'sentiments' => $sentiments
        ];
    }
}