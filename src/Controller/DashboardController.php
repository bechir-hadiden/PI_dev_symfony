<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\VoyageRepository;
use App\Repository\DestinationRepository;
use App\Repository\PaiementRepository;
use App\Repository\TransportRepository;
use App\Repository\VehiculeRepository;
use App\Repository\ReservationTransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function adminDashboard(
        PaiementRepository $paiementRepository,
        UserRepository $userRepository,
        VoyageRepository $voyageRepository,
        DestinationRepository $destinationRepository,
        TransportRepository $transportRepository,
        VehiculeRepository $vehiculeRepository
    ): Response
    {
        $payments = $paiementRepository->findAll();
        $totalRevenue = 0;
        foreach ($payments as $p) {
            if ($p->getStatus() === 'Effectué') {
                $totalRevenue += $p->getAmount();
            }
        }

        return $this->render('backoffice/dashboard.html.twig', [
            'total_payments' => count($payments),
            'pending_payments' => count($paiementRepository->findBy(['status' => 'En attente'])),
            'total_users' => count($userRepository->findAll()),
            'total_voyages' => count($voyageRepository->findAll()),
            'total_destinations' => count($destinationRepository->findAll()),
            'total_revenue' => $totalRevenue,
            'total_transports' => count($transportRepository->findAll()),
            'total_vehicules' => count($vehiculeRepository->findAll()),
        ]);
    }

    #[Route('/client/dashboard', name: 'app_client_dashboard')]
    public function clientDashboard(PaiementRepository $paiementRepository, ReservationTransportRepository $resTransportRepo, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
        }

        if (!$user) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('frontoffice/dashboard.html.twig', [
            'user' => $user,
            'recent_payments' => $paiementRepository->findBy(['user' => $user], ['datePaiement' => 'DESC'], 5),
            'recent_transports' => $resTransportRepo->findBy(['user' => $user], ['dateReservation' => 'DESC'], 3),
        ]);
    }
}
