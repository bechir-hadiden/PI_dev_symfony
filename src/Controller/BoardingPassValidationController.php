<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BoardingPassValidationController extends AbstractController
{
    #[Route('/boarding-pass/validate', name: 'app_boarding_pass_validate')]
    public function validate(Request $request, ReservationRepository $reservationRepository): Response
    {
        // Récupérer les données du QR code
        $qrData = $request->query->get('data');
        
        if (!$qrData) {
            return $this->render('boarding_pass/error.html.twig', [
                'error' => 'QR Code invalide'
            ]);
        }
        
        // Décoder les données JSON
        $data = json_decode($qrData, true);
        
        if (!$data || !isset($data['booking_ref'])) {
            return $this->render('boarding_pass/error.html.twig', [
                'error' => 'Données de réservation invalides'
            ]);
        }
        
        // Trouver la réservation par référence
        $reference = $data['booking_ref'];
        $reservation = null;
        
        // Chercher la réservation par son numéro
        $allReservations = $reservationRepository->findAll();
        foreach ($allReservations as $r) {
            if ($r->getReservationNumber() === $reference) {
                $reservation = $r;
                break;
            }
        }
        
        if (!$reservation) {
            return $this->render('boarding_pass/error.html.twig', [
                'error' => 'Réservation non trouvée'
            ]);
        }
        
        // Vérifier le statut
        if ($reservation->getStatus() !== Reservation::STATUS_CONFIRMED) {
            return $this->render('boarding_pass/error.html.twig', [
                'error' => 'Cette réservation n\'est pas confirmée'
            ]);
        }
        
        return $this->render('boarding_pass/validation.html.twig', [
            'reservation' => $reservation,
            'qrData' => $data
        ]);
    }
    
    #[Route('/boarding-pass/scan', name: 'app_boarding_pass_scan')]
    public function scanDemo(): Response
    {
        return $this->render('boarding_pass/scan_demo.html.twig');
    }
}