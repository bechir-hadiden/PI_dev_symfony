<?php

namespace App\Controller\Client;

use App\Entity\Destination;
use App\Repository\DestinationRepository;
use App\Service\QRCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface; 
#[Route('/destinations', name: 'client_destination_')]
class ClientDestinationController extends AbstractController
{
    // ── LISTE (lecture seule + recherche) ─────────────────────────
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
        8 // nombre par page
    );

    return $this->render('client/destination/index.html.twig', [
        'destinations' => $destinations,
        'query' => $query,
    ]);
}

    // ── DÉTAIL (lecture seule + QR Code) ──────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Destination $destination, QRCodeService $qrCodeService): Response
    {
        $qrCodeUrl = $qrCodeService->genererQRCodeDestination(
            $destination->getId(),
            $destination->getNom() . ' ' . $destination->getPays(),
            160
        );

        return $this->render('client/destination/show.html.twig', [
            'destination' => $destination,
            'qrCodeUrl'   => $qrCodeUrl,
        ]);
    }
}