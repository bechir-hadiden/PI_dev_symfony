<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\BoardingPassService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservation/new', name: 'app_reservation_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, EmailService $emailService): Response
    {
        $reservation = new Reservation();
        
        // Récupérer les données du vol depuis la requête
        $flightData = $request->query->all();
        
        if ($flightData) {
            $reservation->setDestination($flightData['destination'] ?? '')
                ->setAirline($flightData['airline'] ?? '')
                ->setFlightNumber($flightData['flightNumber'] ?? '')
                ->setDepartureAirport($flightData['departureAirport'] ?? '')
                ->setArrivalAirport($flightData['arrivalAirport'] ?? '')
                ->setPrice((float)($flightData['price'] ?? 0));
            
            if (!empty($flightData['departureTime'])) {
                $reservation->setDepartureTime(new \DateTime($flightData['departureTime']));
            }
            if (!empty($flightData['arrivalTime'])) {
                $reservation->setArrivalTime(new \DateTime($flightData['arrivalTime']));
            }
        }
        
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // ========== STATUT : EN ATTENTE DE VALIDATION ==========
            $reservation->setStatus(Reservation::STATUS_PENDING);
            $reservation->setReservationDate(new \DateTime());
            
            $entityManager->persist($reservation);
            $entityManager->flush();
            
            // Envoyer les notifications
            try {
                // Email de confirmation au client
                $emailService->sendReservationConfirmation($reservation);
                // Notification à l'admin
                $emailService->notifyNewReservation($reservation);
                $this->addFlash('success', '✅ Réservation enregistrée ! Vous recevrez une confirmation sous 24h.');
            } catch (\Exception $e) {
                $this->addFlash('success', '✅ Réservation enregistrée avec succès !');
            }
            
            return $this->redirectToRoute('app_reservation_success', ['id' => $reservation->getId()]);
        }
        
        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
            'flight' => $flightData
        ]);
    }
    
    #[Route('/reservation/success/{id}', name: 'app_reservation_success')]
    public function success(Reservation $reservation): Response
    {
        return $this->render('reservation/success.html.twig', [
            'reservation' => $reservation,
        ]);
    }
    
    #[Route('/reservations', name: 'app_reservation_list')]
    public function list(ReservationRepository $reservationRepository, Request $request): Response
    {
        // Permettre la recherche par email
        $email = $request->query->get('email');
        $reservations = [];
        
        if ($email) {
            $reservations = $reservationRepository->findByEmail($email);
        } else {
            // Afficher uniquement les réservations confirmées ou terminées sur le site client
            $reservations = $reservationRepository->findBy(
                ['status' => [Reservation::STATUS_CONFIRMED, Reservation::STATUS_COMPLETED]],
                ['reservationDate' => 'DESC']
            );
        }
        
        return $this->render('reservation/list.html.twig', [
            'reservations' => $reservations,
            'searchEmail' => $email
        ]);
    }
    
    #[Route('/mes-reservations', name: 'app_mes_reservations')]
    public function myReservations(ReservationRepository $reservationRepository, Request $request): Response
    {
        $email = $request->query->get('email');
        $reservations = [];
        
        if ($email) {
            $reservations = $reservationRepository->findByEmail($email);
        }
        
        return $this->render('reservation/my_reservations.html.twig', [
            'reservations' => $reservations,
            'searchEmail' => $email
        ]);
    }
    
    #[Route('/reservation/{id}', name: 'app_reservation_show')]
    public function show(Reservation $reservation): Response
    {
        // Vérifier que l'utilisateur a le droit de voir cette réservation
        // (via email ou admin)
        $isOwner = false;
        if ($this->getUser() && $this->isGranted('ROLE_ADMIN')) {
            $isOwner = true;
        }
        
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
            'isOwner' => $isOwner
        ]);
    }
    
    #[Route('/reservation/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancel(Reservation $reservation, EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$reservation->isCancellable()) {
            $this->addFlash('error', '❌ Cette réservation ne peut plus être annulée (départ dans moins de 48h).');
            return $this->redirectToRoute('app_mes_reservations');
        }
        
        $reason = $request->request->get('reason', 'Annulé par le client');
        $reservation->cancel($reason);
        $entityManager->flush();
        
        $this->addFlash('success', '✅ Réservation annulée avec succès.');
        return $this->redirectToRoute('app_mes_reservations');
    }
    
    // ========== BILLET ÉLECTRONIQUE ==========
    
    #[Route('/reservation/{id}/boarding-pass', name: 'app_reservation_boarding_pass')]
    public function boardingPass(Reservation $reservation, BoardingPassService $boardingPassService): Response
    {
        // Vérifier que la réservation est confirmée
        if (!$reservation->isConfirmed()) {
            $this->addFlash('error', '❌ Cette réservation n\'est pas confirmée.');
            return $this->redirectToRoute('app_mes_reservations');
        }
        
        // S'assurer que le billet existe
        if (!$reservation->hasBoardingPass()) {
            try {
                $boardingPassService->generateAndSaveQRCode($reservation);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Génération du billet en cours...');
            }
        }
        
        // Générer le billet HTML
        $html = $boardingPassService->generateBoardingPassHTML($reservation);
        
        return new Response($html);
    }
    
    #[Route('/reservation/{id}/send-boarding-pass', name: 'app_reservation_send_boarding_pass', methods: ['POST'])]
    public function sendBoardingPass(
        Reservation $reservation, 
        BoardingPassService $boardingPassService
    ): JsonResponse {
        // Vérifier que la réservation est confirmée
        if (!$reservation->isConfirmed()) {
            return $this->json(['success' => false, 'error' => 'Réservation non confirmée'], 400);
        }
        
        try {
            // S'assurer que le billet existe
            if (!$reservation->hasBoardingPass()) {
                $boardingPassService->generateAndSaveQRCode($reservation);
            }
            
            // Envoyer l'email
            $success = $boardingPassService->sendBoardingPassByEmail($reservation);
            
            if ($success) {
                return $this->json([
                    'success' => true, 
                    'message' => 'Billet électronique envoyé par email'
                ]);
            } else {
                return $this->json([
                    'success' => false, 
                    'error' => 'Erreur lors de l\'envoi'
                ], 500);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/reservation/{id}/regenerate-boarding-pass', name: 'app_reservation_regenerate_boarding_pass', methods: ['POST'])]
    public function regenerateBoardingPass(
        Reservation $reservation, 
        BoardingPassService $boardingPassService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$reservation->isConfirmed()) {
            return $this->json(['success' => false, 'error' => 'Réservation non confirmée'], 400);
        }
        
        try {
            // Régénérer le QR code
            $boardingPassService->generateAndSaveQRCode($reservation, true);
            $reservation->setBoardingPassSent(false);
            $entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => 'Billet régénéré avec succès',
                'boarding_pass_url' => $reservation->getBoardingPassUrl()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ========== API POUR LE FRONT ==========
    
    #[Route('/api/reservations', name: 'api_reservations', methods: ['GET'])]
    public function apiList(ReservationRepository $reservationRepository): JsonResponse
    {
        // API pour admin - toutes les réservations
        $reservations = $reservationRepository->findAll();
        $data = [];
        
        foreach ($reservations as $reservation) {
            $data[] = [
                'id' => $reservation->getId(),
                'nomClient' => $reservation->getNomClient(),
                'destination' => $reservation->getDestination(),
                'flightNumber' => $reservation->getFlightNumber(),
                'departureTime' => $reservation->getDepartureTime()->format('Y-m-d H:i'),
                'status' => $reservation->getStatus(),
                'statusLabel' => $reservation->getStatusLabel(),
                'totalPrice' => $reservation->getTotalPrice(),
                'hasBoardingPass' => $reservation->hasBoardingPass()
            ];
        }
        
        return $this->json($data);
    }
    
    #[Route('/api/reservation/{id}/status', name: 'api_reservation_status', methods: ['GET'])]
    public function apiStatus(Reservation $reservation): JsonResponse
    {
        return $this->json([
            'id' => $reservation->getId(),
            'status' => $reservation->getStatus(),
            'statusLabel' => $reservation->getStatusLabel(),
            'statusClass' => $reservation->getStatusClass(),
            'isCancellable' => $reservation->isCancellable(),
            'hasBoardingPass' => $reservation->hasBoardingPass(),
            'reservationNumber' => $reservation->getReservationNumber()
        ]);
    }
    
    #[Route('/api/reservation/check', name: 'api_reservation_check', methods: ['POST'])]
    public function apiCheckReservation(Request $request, ReservationRepository $repository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $reference = $data['reference'] ?? null;
        
        if (!$email && !$reference) {
            return $this->json(['success' => false, 'error' => 'Email ou référence requis'], 400);
        }
        
        $reservation = null;
        
        if ($reference) {
            // Chercher par référence
            $all = $repository->findAll();
            foreach ($all as $r) {
                if ($r->getReservationNumber() === $reference) {
                    $reservation = $r;
                    break;
                }
            }
        } elseif ($email) {
            $reservations = $repository->findByEmail($email);
            $reservation = $reservations[0] ?? null;
        }
        
        if (!$reservation) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }
        
        return $this->json([
            'success' => true,
            'id' => $reservation->getId(),
            'status' => $reservation->getStatus(),
            'statusLabel' => $reservation->getStatusLabel(),
            'canCancel' => $reservation->isCancellable(),
            'hasBoardingPass' => $reservation->hasBoardingPass(),
            'reservationNumber' => $reservation->getReservationNumber()
        ]);
    }
}