<?php

namespace App\Service;

use App\Entity\Avis;
use App\Repository\AvisRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;

class PredictiveAnalysisService
{
    private AvisRepository $avisRepository;
    private EntityManagerInterface $entityManager;

    // Poids des différents critères pour la prédiction
    private array $weights = [
        'note' => 0.35,
        'sentiment' => 0.25,
        'commentaire_length' => 0.10,
        'keywords' => 0.15,
        'historique_voyage' => 0.15
    ];

    public function __construct(AvisRepository $avisRepository, EntityManagerInterface $entityManager)
    {
        $this->avisRepository = $avisRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Analyse un avis et prédit la satisfaction client
     */
    public function predictSatisfaction(Avis $avis): array
    {
        $commentaire = strtolower($avis->getCommentaire());
        $note = $avis->getNote();
        
        // 1. Analyse de la note
        $noteScore = $this->analyzeNote($note);
        
        // 2. Analyse du sentiment
        $sentimentScore = $this->analyzeSentiment($avis);
        
        // 3. Analyse de la longueur du commentaire
        $lengthScore = $this->analyzeCommentLength($commentaire);
        
        // 4. Analyse des mots-clés
        $keywordsScore = $this->analyzeKeywords($commentaire);
        
        // 5. Analyse de l'historique du voyage
        $historyScore = $this->analyzeVoyageHistory($avis);
        
        // Calcul du score global
        $totalScore = ($noteScore * $this->weights['note']) +
                      ($sentimentScore * $this->weights['sentiment']) +
                      ($lengthScore * $this->weights['commentaire_length']) +
                      ($keywordsScore * $this->weights['keywords']) +
                      ($historyScore * $this->weights['historique_voyage']);
        
        // Normalisation (0-100)
        $satisfactionScore = round($totalScore * 100, 1);
        
        // Détermination du niveau
        $level = $this->getSatisfactionLevel($satisfactionScore);
        
        // Prédiction de la recommandation
        $recommendationProbability = $this->predictRecommendation($satisfactionScore);
        
        // Alerte si client insatisfait
        $alert = null;
        if ($satisfactionScore < 40) {
            $alert = $this->generateAlert($avis, $satisfactionScore);
        }
        
        // Suggestions d'amélioration
        $suggestions = $this->generateSuggestions($commentaire, $note);
        
        return [
            'satisfaction_score' => $satisfactionScore,
            'niveau' => $level,
            'recommandation_probabilite' => $recommendationProbability,
            'details' => [
                'note_score' => round($noteScore * 100, 1),
                'sentiment_score' => round($sentimentScore * 100, 1),
                'longueur_score' => round($lengthScore * 100, 1),
                'keywords_score' => round($keywordsScore * 100, 1),
                'historique_score' => round($historyScore * 100, 1)
            ],
            'alert' => $alert,
            'suggestions' => $suggestions,
            'risque' => $this->calculateRisk($satisfactionScore)
        ];
    }
    
    /**
     * Analyse de la note
     */
    private function analyzeNote(int $note): float
    {
        return $note / 5; // 5/5 = 1.0, 1/5 = 0.2
    }
    
    /**
     * Analyse du sentiment (basée sur l'analyse existante)
     */
    private function analyzeSentiment(Avis $avis): float
    {
        $sentiment = $avis->getSentimentAnalysis();
        if (!$sentiment || !isset($sentiment['score'])) {
            return 0.5; // Neutre par défaut
        }
        
        $score = $sentiment['score'];
        // Convertir le score de [-1, 1] à [0, 1]
        return ($score + 1) / 2;
    }
    
    /**
     * Analyse de la longueur du commentaire
     */
    private function analyzeCommentLength(string $commentaire): float
    {
        $length = strlen($commentaire);
        
        if ($length === 0) return 0;
        if ($length < 20) return 0.3;      // Trop court
        if ($length < 50) return 0.5;      // Court
        if ($length < 100) return 0.7;     // Moyen
        if ($length < 200) return 0.85;    // Long
        return 1.0;                         // Très long
    }
    
    /**
     * Analyse des mots-clés
     */
    private function analyzeKeywords(string $commentaire): float
    {
        $positiveKeywords = [
            'super', 'excellent', 'parfait', 'génial', 'magnifique', 'top', 'extra',
            'bien', 'satisfait', 'content', 'ravi', 'merci', 'formidable',
            'incroyable', 'exceptionnel', 'fantastique', 'génial', 'cool', 'superbe'
        ];
        
        $negativeKeywords = [
            'déçu', 'mauvais', 'problème', 'dommage', 'cher', 'retard', 'mal',
            'décevant', 'horrible', 'terrible', 'nul', 'pas bien', 'insatisfait',
            'manque', 'absence', 'lent', 'long', 'compliqué'
        ];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveKeywords as $word) {
            if (strpos($commentaire, $word) !== false) $positiveCount++;
        }
        
        foreach ($negativeKeywords as $word) {
            if (strpos($commentaire, $word) !== false) $negativeCount++;
        }
        
        $total = $positiveCount + $negativeCount;
        if ($total === 0) return 0.5;
        
        return $positiveCount / $total;
    }
    
    /**
     * Analyse de l'historique du voyage
     */
    private function analyzeVoyageHistory(Avis $avis): float
    {
        $autresAvis = $this->avisRepository->findBy(['voyageId' => $avis->getVoyageId()]);
        $total = count($autresAvis);
        
        if ($total === 0) return 0.6; // Premier avis
        
        $moyenneVoyage = 0;
        foreach ($autresAvis as $autre) {
            $moyenneVoyage += $autre->getNote();
        }
        $moyenneVoyage /= $total;
        
        // Si la note est meilleure que la moyenne, score plus élevé
        if ($avis->getNote() > $moyenneVoyage) {
            return min(1.0, 0.7 + (($avis->getNote() - $moyenneVoyage) / 5));
        } elseif ($avis->getNote() < $moyenneVoyage) {
            return max(0.3, 0.5 - (($moyenneVoyage - $avis->getNote()) / 5));
        }
        
        return 0.5;
    }
    
    /**
     * Niveau de satisfaction
     */
    private function getSatisfactionLevel(float $score): string
    {
        if ($score >= 80) return 'Très satisfait';
        if ($score >= 60) return 'Satisfait';
        if ($score >= 40) return 'Moyennement satisfait';
        if ($score >= 20) return 'Insatisfait';
        return 'Très insatisfait';
    }
    
    /**
     * Prédit la probabilité que le client recommande
     */
    private function predictRecommendation(float $score): int
    {
        if ($score >= 80) return 95;
        if ($score >= 60) return 75;
        if ($score >= 40) return 50;
        if ($score >= 20) return 25;
        return 10;
    }
    
    /**
     * Génère une alerte si nécessaire
     */
    private function generateAlert(Avis $avis, float $score): array
    {
        $alert = [
            'niveau' => 'CRITIQUE',
            'message' => "⚠️ Client insatisfait - Score: {$score}%",
            'actions' => []
        ];
        
        if ($avis->getNote() <= 2) {
            $alert['actions'][] = '📞 Contacter le client pour un geste commercial';
        }
        
        if (strlen($avis->getCommentaire()) > 50) {
            $alert['actions'][] = '📝 Analyser en détail le commentaire négatif';
        }
        
        $alert['actions'][] = '🎯 Mettre à jour la base de connaissances qualité';
        
        return $alert;
    }
    
    /**
     * Génère des suggestions d'amélioration
     */
    private function generateSuggestions(string $commentaire, int $note): array
    {
        $suggestions = [];
        
        if ($note <= 2) {
            $suggestions[] = '🔴 Action urgente : Suivi client nécessaire';
        }
        
        // Suggestions basées sur les mots-clés
        $keywords = ['prix', 'service', 'accueil', 'propreté', 'emplacement', 'chambre'];
        $found = [];
        
        foreach ($keywords as $keyword) {
            if (strpos($commentaire, $keyword) !== false) {
                $found[] = $keyword;
            }
        }
        
        if (!empty($found)) {
            $suggestions[] = "🔍 Points à améliorer : " . implode(', ', $found);
        }
        
        if (strlen($commentaire) < 20) {
            $suggestions[] = '💡 Inviter le client à détailler son expérience';
        }
        
        if (empty($suggestions)) {
            $suggestions[] = '✅ Aucune action immédiate requise';
        }
        
        return $suggestions;
    }
    
    /**
     * Calcule le niveau de risque (public pour être utilisé par les contrôleurs)
     */
    public function calculateRisk(float $score): array
    {
        if ($score >= 70) {
            return [
                'niveau' => 'Faible',
                'couleur' => 'success',
                'description' => 'Client satisfait, faible risque de mécontentement'
            ];
        } elseif ($score >= 40) {
            return [
                'niveau' => 'Moyen',
                'couleur' => 'warning',
                'description' => 'Risque modéré, surveiller les prochains avis'
            ];
        } else {
            return [
                'niveau' => 'Élevé',
                'couleur' => 'danger',
                'description' => 'Risque élevé de mécontentement, action immédiate recommandée'
            ];
        }
    }
    
    /**
     * Analyse prédictive batch (pour tous les avis)
     */
    public function analyzeAllAvis(): array
    {
        $allAvis = $this->avisRepository->findAll();
        $results = [];
        
        foreach ($allAvis as $avis) {
            $results[] = [
                'id' => $avis->getId(),
                'nom' => $avis->getNomClient(),
                'commentaire' => substr($avis->getCommentaire(), 0, 100),
                'note' => $avis->getNote(),
                'prediction' => $this->predictSatisfaction($avis)
            ];
        }
        
        return $results;
    }
    
    /**
     * Statistiques globales prédictives
     */
    public function getPredictiveStats(): array
    {
        $allAvis = $this->avisRepository->findAll();
        
        $stats = [
            'total' => count($allAvis),
            'tres_satisfait' => 0,
            'satisfait' => 0,
            'moyen' => 0,
            'insatisfait' => 0,
            'tres_insatisfait' => 0,
            'score_moyen' => 0
        ];
        
        $totalScore = 0;
        
        foreach ($allAvis as $avis) {
            $prediction = $this->predictSatisfaction($avis);
            $niveau = $prediction['niveau'];
            
            // Ajouter au compteur approprié
            switch ($niveau) {
                case 'Très satisfait':
                    $stats['tres_satisfait']++;
                    break;
                case 'Satisfait':
                    $stats['satisfait']++;
                    break;
                case 'Moyennement satisfait':
                    $stats['moyen']++;
                    break;
                case 'Insatisfait':
                    $stats['insatisfait']++;
                    break;
                case 'Très insatisfait':
                    $stats['tres_insatisfait']++;
                    break;
                default:
                    $stats['moyen']++;
            }
            
            $totalScore += $prediction['satisfaction_score'];
        }
        
        $stats['score_moyen'] = round($totalScore / max(1, count($allAvis)), 1);
        
        return $stats;
    }
}