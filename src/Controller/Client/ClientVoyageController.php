<?php

namespace App\Controller\Client;

use App\Entity\Voyage;
use App\Repository\DestinationRepository;
use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
#[Route('/voyages', name: 'client_voyage_')]
class ClientVoyageController extends AbstractController
{
    // ── LISTE (lecture seule + recherche + filtre destination) ────
#[Route('', name: 'index', methods: ['GET'])]
public function index(
    Request $request,
    DestinationRepository $repo,
    PaginatorInterface $paginator
): Response {

    $query = $request->query->get('q', '');

    $qb = $repo->createQueryBuilder('d');

    if ($query) {
        $qb->where('d.nom LIKE :q OR d.pays LIKE :q')
           ->setParameter('q', '%'.$query.'%');
    }

    $qb->orderBy('d.id', 'DESC');

    $destinations = $paginator->paginate(
        $qb,
        $request->query->getInt('page', 1),
        8
    );

    return $this->render('client/voyage/index.html.twig', [
        'destinations' => $destinations,
        'query' => $query,
    ]);
}

    // ── DÉTAIL (lecture seule) ────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Voyage $voyage): Response
    {
        return $this->render('client/voyage/show.html.twig', [
            'voyage' => $voyage,
        ]);
    }
}