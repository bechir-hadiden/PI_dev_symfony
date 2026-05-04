<?php

namespace App\Controller\Admin;

use App\Repository\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OffreAdminController extends AbstractController
{
    #[Route('/admin/offre', name: 'app_admin_offre_index')]
    public function index(OffreRepository $offreRepository): Response
    {
        $offres = $offreRepository->findAll();

        // LOGIQUE MÉTIER : Préparation des stats pour le PieChart
        $stats = [
            'HOTEL' => 0,
            'VOL' => 0,
            'TRANSPORT' => 0,
            'VOYAGE' => 0
        ];

        foreach ($offres as $o) {
            $cat = $o->getCategory();
            if (isset($stats[$cat])) {
                $stats[$cat]++;
            }
        }

        return $this->render('admin/offre/index.html.twig', [
            'offres' => $offres,
            'stats' => $stats, // On envoie les stats au template
        ]);
    }
}