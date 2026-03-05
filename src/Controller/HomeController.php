<?php

namespace App\Controller;

use App\Repository\DestinationRepository;
use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(VoyageRepository $voyageRepo, DestinationRepository $destRepo): Response
    {
        return $this->render('home/index.html.twig', [
            'voyagesPopulaires'  => $voyageRepo->findPopulaires(3),
            'totalVoyages'       => $voyageRepo->count([]),
            'totalDestinations'  => $destRepo->count([]),
        ]);
    }
}