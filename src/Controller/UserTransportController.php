<?php

namespace App\Controller;

use App\Entity\ReservationTransport;
use App\Entity\Transport;
use App\Repository\ReservationTransportRepository;
use App\Repository\TransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/transport')]
class UserTransportController extends AbstractController
{
    #[Route('/', name: 'app_user_transport_index')]
    public function index(TransportRepository $transportRepository): Response
    {
        return $this->render('frontoffice/transport/list.html.twig', [
            'transports' => $transportRepository->findAll(),
        ]);
    }

    #[Route('/reserver/{id}', name: 'app_user_transport_book', methods: ['POST'])]
    public function book(Transport $transport, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        }

        if (!$user) {
            $this->addFlash('error', 'Aucun utilisateur trouvé pour la réservation.');
            return $this->redirectToRoute('app_user_transport_index');
        }

        $reservation = new ReservationTransport();
        $reservation->setUser($user);
        $reservation->setTransport($transport);
        $reservation->setDateReservation(new \DateTime());
        $reservation->setStatus('Confirmé');

        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Réservation de transport effectuée avec succès !');
        return $this->redirectToRoute('app_user_reservation_index');
    }

    #[Route('/mes-reservations', name: 'app_user_reservation_index')]
    public function reservations(EntityManagerInterface $entityManager, ReservationTransportRepository $reservationRepo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        }

        if (!$user) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('frontoffice/reservation/mes_reservations.html.twig', [
            'reservations' => $reservationRepo->findBy(['user' => $user], ['dateReservation' => 'DESC']),
        ]);
    }
}
