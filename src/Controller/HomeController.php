<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Repository\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(AvisRepository $avisRepo): Response
    {
        // Récupérer les 3 derniers avis pour les afficher
        $recentAvis = $avisRepo->findBy([], ['dateAvis' => 'DESC'], 3);
        
        // Calculer la note moyenne
        $averageNote = $avisRepo->getAverageNote();
        
        // Compter le nombre total d'avis
        $totalAvis = $avisRepo->count([]);
        
        $stats = [
            'noteMoyenne' => $averageNote,
            'nombreAvis' => $totalAvis,
            'anneesExperience' => 18
        ];
        
        return $this->render('home/voyage_elite.html.twig', [
            'reviews' => $recentAvis,
            'stats' => $stats,
        ]);
    }
}