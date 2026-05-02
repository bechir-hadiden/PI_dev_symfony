<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Reservation;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use App\Repository\ReservationRepository;
use App\Service\BoardingPassService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    // ========== DASHBOARD ==========
    
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        AvisRepository $avisRepo, 
        ReservationRepository $reservationRepo
    ): Response {
        // Statistiques des avis
        $avisStats = [
            'total' => $avisRepo->count([]),
            'pending' => $avisRepo->count(['status' => Avis::STATUS_PENDING]),
            'approved' => $avisRepo->count(['status' => Avis::STATUS_APPROVED]),
            'rejected' => $avisRepo->count(['status' => Avis::STATUS_REJECTED]),
            'average_note' => $avisRepo->getAverageNote(),
        ];

        // Derniers avis
        $latestAvis = $avisRepo->findBy([], ['dateAvis' => 'DESC'], 5);

        // Statistiques des réservations
        $reservationStats = [
            'total' => $reservationRepo->count([]),
            'pending' => $reservationRepo->count(['status' => Reservation::STATUS_PENDING]),
            'confirmed' => $reservationRepo->count(['status' => Reservation::STATUS_CONFIRMED]),
            'cancelled' => $reservationRepo->count(['status' => Reservation::STATUS_CANCELLED]),
            'completed' => $reservationRepo->count(['status' => Reservation::STATUS_COMPLETED]),
            'total_revenue' => $reservationRepo->getTotalRevenue(),
        ];

        // Dernières réservations
        $latestReservations = $reservationRepo->findBy([], ['reservationDate' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'avisStats' => $avisStats,
            'latestAvis' => $latestAvis,
            'reservationStats' => $reservationStats,
            'latestReservations' => $latestReservations,
        ]);
    }

    // ========== API POUR LES NOTIFICATIONS ==========
    
    #[Route('/api/pending-count', name: 'admin_api_pending_count', methods: ['GET'])]
    public function getPendingCount(AvisRepository $avisRepo): JsonResponse
    {
        return $this->json([
            'avis_pending' => $avisRepo->count(['status' => Avis::STATUS_PENDING]),
        ]);
    }

    #[Route('/api/pending-reservations-count', name: 'admin_api_pending_reservations_count', methods: ['GET'])]
    public function getPendingReservationsCount(ReservationRepository $reservationRepo): JsonResponse
    {
        return $this->json([
            'pending' => $reservationRepo->count(['status' => Reservation::STATUS_PENDING]),
        ]);
    }

    // ========== GESTION DES AVIS ==========

    #[Route('/avis', name: 'admin_avis')]
    public function manageAvis(
        AvisRepository $avisRepo, 
        Request $request
    ): Response {
        $status = $request->query->get('status', 'pending');
        
        // Validation du statut
        $validStatuses = [Avis::STATUS_PENDING, Avis::STATUS_APPROVED, Avis::STATUS_REJECTED];
        if (!in_array($status, $validStatuses)) {
            $status = Avis::STATUS_PENDING;
        }

        $avis = $avisRepo->findBy(['status' => $status], ['dateAvis' => 'DESC']);
        
        // Compter le nombre d'avis par statut
        $counts = [
            'pending' => $avisRepo->count(['status' => Avis::STATUS_PENDING]),
            'approved' => $avisRepo->count(['status' => Avis::STATUS_APPROVED]),
            'rejected' => $avisRepo->count(['status' => Avis::STATUS_REJECTED]),
        ];

        return $this->render('admin/avis.html.twig', [
            'avis' => $avis,
            'currentStatus' => $status,
            'counts' => $counts,
        ]);
    }

    // ========== CRÉER UN AVIS DEPUIS L'ADMIN ==========

    #[Route('/avis/new', name: 'admin_avis_new', methods: ['GET', 'POST'])]
    public function newAvis(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $avis = new Avis();
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setDateAvis(new \DateTime());
            $avis->setStatus(Avis::STATUS_APPROVED); // L'admin peut approuver directement
            
            $em->persist($avis);
            $em->flush();

            $this->addFlash('success', 'Avis créé avec succès !');
            return $this->redirectToRoute('admin_avis');
        }

        return $this->render('admin/avis_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/avis/{id}/approve', name: 'admin_avis_approve', methods: ['POST', 'GET'])]
    public function approveAvis(
        Avis $avis, 
        EntityManagerInterface $em,
        EmailService $emailService
    ): Response {
        $avis->approve();
        $em->flush();

        // Envoyer un email au client pour l'informer que son avis est publié
        try {
            $emailService->sendAvisApproved($avis);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Avis approuvé mais email non envoyé.');
        }

        $this->addFlash('success', sprintf(
            'L\'avis de %s a été approuvé avec succès',
            $avis->getNomClient()
        ));

        return $this->redirectToRoute('admin_avis');
    }

    #[Route('/avis/{id}/reject', name: 'admin_avis_reject', methods: ['POST', 'GET'])]
    public function rejectAvis(
        Avis $avis, 
        EntityManagerInterface $em
    ): Response {
        $avis->reject();
        $em->flush();

        $this->addFlash('warning', sprintf(
            'L\'avis de %s a été rejeté',
            $avis->getNomClient()
        ));

        return $this->redirectToRoute('admin_avis');
    }

    #[Route('/avis/{id}/delete', name: 'admin_avis_delete', methods: ['POST', 'GET'])]
    public function deleteAvis(
        Avis $avis, 
        EntityManagerInterface $em
    ): Response {
        $nomClient = $avis->getNomClient();
        $em->remove($avis);
        $em->flush();

        $this->addFlash('danger', sprintf(
            'L\'avis de %s a été supprimé définitivement',
            $nomClient
        ));

        return $this->redirectToRoute('admin_avis');
    }

    #[Route('/avis/bulk-action', name: 'admin_avis_bulk', methods: ['POST'])]
    public function bulkAvisAction(
        Request $request,
        EntityManagerInterface $em,
        AvisRepository $avisRepo
    ): Response {
        $action = $request->request->get('action');
        $avisIds = $request->request->all('avis_ids');

        if (empty($avisIds)) {
            $this->addFlash('warning', 'Aucun avis sélectionné');
            return $this->redirectToRoute('admin_avis');
        }

        $count = 0;
        foreach ($avisIds as $id) {
            $avis = $avisRepo->find($id);
            if ($avis) {
                switch ($action) {
                    case 'approve':
                        $avis->approve();
                        $count++;
                        break;
                    case 'reject':
                        $avis->reject();
                        $count++;
                        break;
                    case 'delete':
                        $em->remove($avis);
                        $count++;
                        break;
                }
            }
        }

        $em->flush();
        $this->addFlash('success', sprintf('%d avis traités avec succès', $count));

        return $this->redirectToRoute('admin_avis');
    }

    // ========== GESTION DES RÉSERVATIONS ==========

    #[Route('/reservations', name: 'admin_reservations')]
    public function manageReservations(
        ReservationRepository $reservationRepo,
        Request $request
    ): Response {
        $status = $request->query->get('status', 'pending');
        
        // Validation du statut
        $validStatuses = [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_CANCELLED,
            Reservation::STATUS_COMPLETED
        ];
        
        if (!in_array($status, $validStatuses)) {
            $status = Reservation::STATUS_PENDING;
        }

        $reservations = $reservationRepo->findBy(
            ['status' => $status], 
            ['reservationDate' => 'DESC']
        );
        
        // Compter le nombre de réservations par statut
        $counts = [
            'pending' => $reservationRepo->count(['status' => Reservation::STATUS_PENDING]),
            'confirmed' => $reservationRepo->count(['status' => Reservation::STATUS_CONFIRMED]),
            'cancelled' => $reservationRepo->count(['status' => Reservation::STATUS_CANCELLED]),
            'completed' => $reservationRepo->count(['status' => Reservation::STATUS_COMPLETED]),
        ];

        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservations,
            'currentStatus' => $status,
            'counts' => $counts,
        ]);
    }

    #[Route('/reservation/{id}/confirm', name: 'admin_reservation_confirm', methods: ['POST', 'GET'])]
    public function confirmReservation(
        Reservation $reservation,
        EntityManagerInterface $em,
        BoardingPassService $boardingPassService,
        EmailService $emailService
    ): Response {
        if (!$reservation->isConfirmable()) {
            $this->addFlash('error', 'Cette réservation ne peut pas être confirmée');
            return $this->redirectToRoute('admin_reservations');
        }

        $reservation->confirm();
        
        // Générer le QR code et la carte d'embarquement
        try {
            $boardingPassService->generateAndSaveQRCode($reservation);
            $this->addFlash('success', 'Carte d\'embarquement générée avec succès');
            
            // Envoyer l'email de confirmation au client
            $emailService->notifyReservationConfirmed($reservation);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Réservation confirmée mais erreur lors de la génération de la carte: ' . $e->getMessage());
        }

        $em->flush();

        $this->addFlash('success', sprintf(
            'La réservation de %s a été confirmée',
            $reservation->getNomClient()
        ));

        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/reservation/{id}/cancel', name: 'admin_reservation_cancel', methods: ['POST', 'GET'])]
    public function cancelReservation(
        Reservation $reservation,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $reason = $request->request->get('reason', 'Annulé par l\'administrateur');
        
        $reservation->cancel($reason);
        $em->flush();

        $this->addFlash('warning', sprintf(
            'La réservation de %s a été annulée',
            $reservation->getNomClient()
        ));

        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/reservation/{id}/complete', name: 'admin_reservation_complete', methods: ['POST', 'GET'])]
    public function completeReservation(
        Reservation $reservation,
        EntityManagerInterface $em
    ): Response {
        $reservation->complete();
        $em->flush();

        $this->addFlash('success', sprintf(
            'La réservation de %s est marquée comme terminée',
            $reservation->getNomClient()
        ));

        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/reservation/{id}/generate-boarding-pass', name: 'admin_reservation_generate_boarding_pass', methods: ['POST', 'GET'])]
    public function generateBoardingPass(
        Reservation $reservation,
        BoardingPassService $boardingPassService
    ): Response {
        if (!$reservation->isConfirmed()) {
            $this->addFlash('error', 'Seules les réservations confirmées peuvent avoir une carte d\'embarquement');
            return $this->redirectToRoute('admin_reservations');
        }

        try {
            $boardingPassService->generateAndSaveQRCode($reservation, true);
            $this->addFlash('success', 'Carte d\'embarquement générée avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/reservation/{id}/send-boarding-pass', name: 'admin_reservation_send_boarding_pass', methods: ['POST', 'GET'])]
    public function sendBoardingPass(
        Reservation $reservation,
        BoardingPassService $boardingPassService
    ): Response {
        if (!$reservation->hasBoardingPass()) {
            $this->addFlash('error', 'Aucune carte d\'embarquement générée pour cette réservation');
            return $this->redirectToRoute('admin_reservations');
        }

        try {
            $success = $boardingPassService->sendBoardingPassByEmail($reservation);
            if ($success) {
                $this->addFlash('success', 'Carte d\'embarquement envoyée par email');
            } else {
                $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/reservation/{id}/view-boarding-pass', name: 'admin_reservation_view_boarding_pass')]
    public function viewBoardingPass(
        Reservation $reservation,
        BoardingPassService $boardingPassService
    ): Response {
        if (!$reservation->hasBoardingPass()) {
            $this->addFlash('error', 'Aucune carte d\'embarquement générée');
            return $this->redirectToRoute('admin_reservations');
        }

        $html = $boardingPassService->generateBoardingPassHTML($reservation);
        
        return new Response($html);
    }

    // ========== STATISTIQUES ==========

    #[Route('/statistiques', name: 'admin_statistiques')]
    public function statistics(
        AvisRepository $avisRepo,
        ReservationRepository $reservationRepo
    ): Response {
        // Statistiques avis
        $avisStats = [
            'total' => $avisRepo->count([]),
            'by_status' => [
                'pending' => $avisRepo->count(['status' => Avis::STATUS_PENDING]),
                'approved' => $avisRepo->count(['status' => Avis::STATUS_APPROVED]),
                'rejected' => $avisRepo->count(['status' => Avis::STATUS_REJECTED]),
            ],
            'average_note' => $avisRepo->getAverageNote(),
            'notes_distribution' => $avisRepo->getNotesDistribution(),
        ];

        // Statistiques réservations
        $reservationStats = [
            'total' => $reservationRepo->count([]),
            'by_status' => [
                'pending' => $reservationRepo->count(['status' => Reservation::STATUS_PENDING]),
                'confirmed' => $reservationRepo->count(['status' => Reservation::STATUS_CONFIRMED]),
                'cancelled' => $reservationRepo->count(['status' => Reservation::STATUS_CANCELLED]),
                'completed' => $reservationRepo->count(['status' => Reservation::STATUS_COMPLETED]),
            ],
            'total_revenue' => $reservationRepo->getTotalRevenue(),
            'monthly_revenue' => $reservationRepo->getMonthlyRevenue(),
            'top_destinations' => $reservationRepo->getTopDestinations(),
        ];

        // Statistiques des avis par mois
        $avisParMois = $avisRepo->getAvisByMonth();

        return $this->render('admin/statistiques.html.twig', [
            'avisStats' => $avisStats,
            'reservationStats' => $reservationStats,
            'avisParMois' => $avisParMois,
        ]);
    }

    // ========== BILLETS ÉLECTRONIQUES ==========

    #[Route('/boarding-passes', name: 'admin_boarding_passes')]
    public function manageBoardingPasses(
        ReservationRepository $reservationRepo
    ): Response {
        $reservations = $reservationRepo->findBy(
            ['status' => Reservation::STATUS_CONFIRMED],
            ['reservationDate' => 'DESC']
        );

        return $this->render('admin/boarding_passes.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}