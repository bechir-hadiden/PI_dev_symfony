<?php

namespace App\Controller\Client;

use App\Repository\HotelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hotels', name: 'client_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class HotelController extends AbstractController
{
    public function __construct(private HotelRepository $hotelRepo) {}

    #[Route('', name: 'hotels')]
    public function index(Request $request): Response
    {
        $query   = $request->query->get('q', '');
        $priceMin = $request->query->get('price_min', '');
        $priceMax = $request->query->get('price_max', '');

        $hotels = $this->hotelRepo->findWithFilters($query, $priceMin, $priceMax);

        return $this->render('client/hotel/index.html.twig', [
            'hotels'   => $hotels,
            'query'    => $query,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
        ]);
    }

    #[Route('/{id}', name: 'hotel_detail', requirements: ['id' => '\d+'])]
    public function detail(int $id): Response
    {
        $hotel = $this->hotelRepo->findOneWithDetails($id);
        if (!$hotel) {
            throw $this->createNotFoundException('Hotel not found.');
        }

        return $this->render('client/hotel/detail.html.twig', [
            'hotel' => $hotel,
        ]);
    }

    #[Route('/{id}/reserve', name: 'hotel_reserve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reserve(int $id, Request $request): Response
    {
        $hotel = $this->hotelRepo->find($id);
        if (!$hotel) {
            throw $this->createNotFoundException('Hotel not found.');
        }

        if (!$this->isCsrfTokenValid('reserve_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request. Please try again.');
            return $this->redirectToRoute('client_hotel_detail', ['id' => $id]);
        }

        $nights = max(1, (int) $request->request->get('nights', 1));
        $total  = $nights * (float) $hotel->getPricePerNight();

        $this->addFlash('success', sprintf(
            'Reservation request submitted for %d night(s) at %s — Total: €%.2f. Our team will confirm shortly.',
            $nights,
            $hotel->getName(),
            $total
        ));

        return $this->redirectToRoute('client_hotel_detail', ['id' => $id]);
    }
}
