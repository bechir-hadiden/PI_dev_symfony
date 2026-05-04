<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Repository\DestinationRepository;
use App\Repository\VoyageRepository;
use App\Service\ContactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        DestinationRepository $destRepo,
        VoyageRepository $voyageRepo,
    ): Response {
        return $this->render('home/index.html.twig', [
            'destinations' => $destRepo->findBy([], ['id' => 'ASC'], 6),
            'voyages'      => $voyageRepo->findBy([], ['id' => 'DESC'], 4),
        ]);
    }
} 