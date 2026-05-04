<?php

namespace App\Controller; // ✅ namespace correct (pas Admin)

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
use App\Repository\DestinationRepository;
class ReservationController extends AbstractController // ✅ nom de classe correct
{
  // src/Controller/ReservationController.php

  #[Route('/reservation/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, DestinationRepository $destRepo): Response 
{
    $reservation = new Reservation();
    
    // 1. Récupération des données du vol envoyées via l'URL (le lien "Réserver")
    $flightData = $request->query->all();

    if (!empty($flightData)) {
        // Liaison avec l'objet Destination
        $destination = $destRepo->findOneBy(['nom' => $flightData['destination'] ?? '']);
        if ($destination) {
            $reservation->setDestination($destination);
        }

        // REMPLISSAGE DES CHAMPS OBLIGATOIRES (Ceux qui causent l'erreur SQL)
        $reservation->setAirline($flightData['airline'] ?? 'N/A')
                    ->setFlightNumber($flightData['flightNumber'] ?? 'N/A')
                    ->setDepartureAirport($flightData['departureAirport'] ?? 'N/A')
                    ->setArrivalAirport($flightData['arrivalAirport'] ?? 'N/A')
                    ->setPrice((float)($flightData['price'] ?? 0));

        // Gestion des dates (Conversion du texte de l'URL en objet DateTime)
        try {
            if (!empty($flightData['departureTime'])) {
                $reservation->setDepartureTime(new \DateTime($flightData['departureTime']));
            } else {
                $reservation->setDepartureTime(new \DateTime()); // Fallback si vide
            }

            if (!empty($flightData['arrivalTime'])) {
                $reservation->setArrivalTime(new \DateTime($flightData['arrivalTime']));
            } else {
                $reservation->setArrivalTime(new \DateTime()); // Fallback
            }
        } catch (\Exception $e) {
            // En cas de format de date invalide dans l'URL
            $reservation->setDepartureTime(new \DateTime());
            $reservation->setArrivalTime(new \DateTime());
        }
    }

    // 2. Création du formulaire
    $form = $this->createForm(ReservationType::class, $reservation);
    $form->handleRequest($request);

    // 3. Tentative d'enregistrement
    if ($form->isSubmitted() && $form->isValid()) {
        
        // On s'assure que le statut et la date de résa sont là (via ton constructeur normalement)
        $reservation->setStatus(Reservation::STATUS_PENDING);
        $reservation->setReservationDate(new \DateTime());

        $entityManager->persist($reservation);
        $entityManager->flush(); // L'erreur SQL disparaitra ici !

        $this->addFlash('success', 'Votre réservation a été enregistrée avec succès.');
        
        // Redirige vers une page de confirmation ou l'accueil
        return $this->redirectToRoute('app_home'); 
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
        $email = $request->query->get('email');
        $reservations = [];

        if ($email) {
            $reservations = $reservationRepository->findByEmail($email);
        } else {
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
        if (!$reservation->isConfirmed()) {
            $this->addFlash('error', '❌ Cette réservation n\'est pas confirmée.');
            return $this->redirectToRoute('app_mes_reservations');
        }

        if (!$reservation->hasBoardingPass()) {
            try {
                $boardingPassService->generateAndSaveQRCode($reservation);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Génération du billet en cours...');
            }
        }

        $html = $boardingPassService->generateBoardingPassHTML($reservation);

        return new Response($html);
    }

    #[Route('/reservation/{id}/send-boarding-pass', name: 'app_reservation_send_boarding_pass', methods: ['POST'])]
    public function sendBoardingPass(
        Reservation $reservation,
        BoardingPassService $boardingPassService
    ): JsonResponse {
        if (!$reservation->isConfirmed()) {
            return $this->json(['success' => false, 'error' => 'Réservation non confirmée'], 400);
        }

        try {
            if (!$reservation->hasBoardingPass()) {
                $boardingPassService->generateAndSaveQRCode($reservation);
            }

            $success = $boardingPassService->sendBoardingPassByEmail($reservation);

            if ($success) {
                return $this->json(['success' => true, 'message' => 'Billet électronique envoyé par email']);
            } else {
                return $this->json(['success' => false, 'error' => 'Erreur lors de l\'envoi'], 500);
            }
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
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
            $boardingPassService->generateAndSaveQRCode($reservation, true);
            $reservation->setBoardingPassSent(false);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Billet régénéré avec succès',
                'boarding_pass_url' => $reservation->getBoardingPassUrl()
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ========== API ==========

    #[Route('/api/reservations', name: 'api_reservations', methods: ['GET'])]
    public function apiList(ReservationRepository $reservationRepository): JsonResponse
    {
        $reservations = $reservationRepository->findAll();
        $data = [];

        foreach ($reservations as $reservation) {
            $data[] = [
                'id'             => $reservation->getId(),
                'nomClient'      => $reservation->getNomClient(),
                'destination'    => $reservation->getDestination(),
                'flightNumber'   => $reservation->getFlightNumber(),
                'departureTime'  => $reservation->getDepartureTime()->format('Y-m-d H:i'),
                'status'         => $reservation->getStatus(),
                'statusLabel'    => $reservation->getStatusLabel(),
                'totalPrice'     => $reservation->getTotalPrice(),
                'hasBoardingPass'=> $reservation->hasBoardingPass()
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/reservation/{id}/status', name: 'api_reservation_status', methods: ['GET'])]
    public function apiStatus(Reservation $reservation): JsonResponse
    {
        return $this->json([
            'id'               => $reservation->getId(),
            'status'           => $reservation->getStatus(),
            'statusLabel'      => $reservation->getStatusLabel(),
            'statusClass'      => $reservation->getStatusClass(),
            'isCancellable'    => $reservation->isCancellable(),
            'hasBoardingPass'  => $reservation->hasBoardingPass(),
            'reservationNumber'=> $reservation->getReservationNumber()
        ]);
    }

    #[Route('/api/reservation/check', name: 'api_reservation_check', methods: ['POST'])]
    public function apiCheckReservation(Request $request, ReservationRepository $repository): JsonResponse
    {
        $data      = json_decode($request->getContent(), true);
        $email     = $data['email'] ?? null;
        $reference = $data['reference'] ?? null;

        if (!$email && !$reference) {
            return $this->json(['success' => false, 'error' => 'Email ou référence requis'], 400);
        }

        $reservation = null;

        if ($reference) {
            foreach ($repository->findAll() as $r) {
                if ($r->getReservationNumber() === $reference) {
                    $reservation = $r;
                    break;
                }
            }
        } elseif ($email) {
            $reservations = $repository->findByEmail($email);
            $reservation  = $reservations[0] ?? null;
        }

        if (!$reservation) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }

        return $this->json([
            'success'           => true,
            'id'                => $reservation->getId(),
            'status'            => $reservation->getStatus(),
            'statusLabel'       => $reservation->getStatusLabel(),
            'canCancel'         => $reservation->isCancellable(),
            'hasBoardingPass'   => $reservation->hasBoardingPass(),
            'reservationNumber' => $reservation->getReservationNumber()
        ]);
    }
}