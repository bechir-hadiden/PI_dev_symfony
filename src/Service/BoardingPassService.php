<?php

namespace App\Service;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class BoardingPassService
{
    private string $projectDir;
    private string $adminEmail;
    private string $baseUrl;
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private Environment $twig;

    public function __construct(
        string $projectDir,
        string $adminEmail,
        string $baseUrl,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        Environment $twig
    ) {
        $this->projectDir = $projectDir;
        $this->adminEmail = $adminEmail;
        $this->baseUrl = $baseUrl;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * Génère et sauvegarde le QR code pour une réservation
     * 
     * @param Reservation $reservation La réservation
     * @param bool $regenerate Si true, force la régénération
     */
    public function generateAndSaveQRCode(Reservation $reservation, bool $regenerate = false): string
    {
        // Créer le dossier si nécessaire
        $uploadDir = $this->projectDir . '/public/uploads/boarding_passes';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Si régénération, supprimer l'ancien fichier
        if ($regenerate && $reservation->getBoardingPassFile()) {
            $oldFile = $uploadDir . '/' . $reservation->getBoardingPassFile();
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        
        // Générer les données du QR code
        $qrData = $this->prepareQRCodeData($reservation);
        $qrDataJson = json_encode($qrData);
        
        // Utiliser QuickChart.io
        $qrCodeUrl = "https://quickchart.io/qr?text=" . urlencode($qrDataJson) . "&size=300";
        
        // Télécharger l'image
        $imageContent = file_get_contents($qrCodeUrl);
        
        if ($imageContent === false) {
            throw new \Exception("Impossible de générer le QR code");
        }
        
        // Sauvegarder le fichier
        $filename = sprintf('boarding_pass_%d_%s.png', 
            $reservation->getId(), 
            date('YmdHis')
        );
        $filepath = $uploadDir . '/' . $filename;
        file_put_contents($filepath, $imageContent);
        
        // Mettre à jour l'entité
        $reservation->setBoardingPassFile($filename);
        
        // Générer un numéro de siège si besoin
        if (!$reservation->getSeatNumber()) {
            $reservation->setSeatNumber($this->generateSeatNumber());
        }
        
        return '/uploads/boarding_passes/' . $filename;
    }

    /**
     * Prépare les données pour le QR code
     */
    private function prepareQRCodeData(Reservation $reservation): array
    {
        return [
            'booking_ref' => $reservation->getReservationNumber(),
            'passenger' => $reservation->getNomClient(),
            'email' => $reservation->getEmail(),
            'flight' => $reservation->getFlightNumber(),
            'from' => $reservation->getDepartureAirport(),
            'to' => $reservation->getArrivalAirport(),
            'date' => $reservation->getDepartureTime()->format('Y-m-d'),
            'time' => $reservation->getDepartureTime()->format('H:i'),
            'seat' => $reservation->getSeatNumber() ?? 'A définir',
            'class' => 'Economy'
        ];
    }

    /**
     * Génère un numéro de siège aléatoire
     */
    private function generateSeatNumber(): string
    {
        $rows = ['A', 'B', 'C', 'D', 'E', 'F'];
        $row = $rows[array_rand($rows)];
        $number = rand(1, 30);
        return $row . $number;
    }

    /**
     * Génère le HTML de la carte d'embarquement
     */
    public function generateBoardingPassHTML(Reservation $reservation): string
    {
        return $this->twig->render('boarding_pass/pdf.html.twig', [
            'reservation' => $reservation,
            'qrCodeUrl' => $reservation->getBoardingPassUrl()
        ]);
    }

    /**
     * Envoie la carte d'embarquement par email
     */
    public function sendBoardingPassByEmail(Reservation $reservation): bool
    {
        try {
            $html = $this->generateBoardingPassHTML($reservation);
            
            $email = (new Email())
                ->from($this->adminEmail)
                ->to($reservation->getEmail())
                ->subject('Votre carte d\'embarquement - Vol ' . $reservation->getFlightNumber())
                ->html($html);
            
            $this->mailer->send($email);
            $reservation->setBoardingPassSent(true);
            $this->entityManager->flush();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}