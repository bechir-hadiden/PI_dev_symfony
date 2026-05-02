<?php

namespace App\Service;

use App\Entity\Avis;
use App\Entity\Reservation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    private MailerInterface $mailer;
    
    // Ton adresse email unique pour l'envoi et la réception admin
    private string $adminEmail = 'sandesnidhia@gmail.com';

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    // ========== MÉTHODES POUR LES AVIS ==========

    /**
     * Envoie un email de confirmation UNIQUEMENT au client
     */
    public function sendConfirmationClient(Avis $avis): void
    {
        $email = (new Email())
            ->from(new Address($this->adminEmail, 'SmartTrip Voyages'))
            ->to($avis->getEmail()) // Destination : Client uniquement
            ->subject('✅ Confirmation de votre avis - SmartTrip')
            ->html($this->getConfirmationTemplate($avis));

        $this->safeSend($email, 'Erreur envoi email client');
    }

    /**
     * Envoie une notification UNIQUEMENT à l'administrateur
     */
    public function sendNotificationAdmin(Avis $avis): void
    {
        $email = (new Email())
            ->from(new Address($this->adminEmail, 'SmartTrip Voyages'))
            ->to($this->adminEmail) // Destination : Toi uniquement
            ->subject('🔔 MODÉRATION : Nouvel avis à vérifier - ' . $avis->getNomClient())
            ->html($this->getAdminNotificationTemplate($avis));

        $this->safeSend($email, 'Erreur envoi email admin');
    }

    /**
     * Envoie un email au client quand son avis est approuvé (si validation manuelle)
     */
    public function sendAvisApproved(Avis $avis): void
    {
        $email = (new Email())
            ->from(new Address($this->adminEmail, 'SmartTrip Voyages'))
            ->to($avis->getEmail())
            ->subject('✅ Votre avis a été publié - SmartTrip')
            ->html($this->getAvisApprovedTemplate($avis));

        $this->safeSend($email, 'Erreur envoi email avis approuvé');
    }

    // ========== MÉTHODES POUR LES RÉSERVATIONS ==========

    public function sendReservationConfirmation(Reservation $reservation): void
    {
        $email = (new Email())
            ->from(new Address($this->adminEmail, 'SmartTrip Voyages'))
            ->to($reservation->getEmail())
            ->subject('✈️ Confirmation de votre réservation - SmartTrip')
            ->html($this->getReservationConfirmationTemplate($reservation));

        $this->safeSend($email, 'Erreur envoi confirmation réservation');
    }

    public function notifyNewReservation(Reservation $reservation): void
    {
        $email = (new Email())
            ->from(new Address($this->adminEmail, 'SmartTrip Voyages'))
            ->to($this->adminEmail)
            ->subject('🆕 Nouvelle réservation - ' . $reservation->getReservationNumber())
            ->html($this->getNewReservationNotificationTemplate($reservation));

        $this->safeSend($email, 'Erreur notification admin réservation');
    }

    public function notifyReservationConfirmed(Reservation $reservation): void
    {
        $email = (new Email())
            ->from(new Address($this->adminEmail, 'SmartTrip Voyages'))
            ->to($reservation->getEmail())
            ->subject('✅ Votre réservation a été confirmée - SmartTrip')
            ->html($this->getReservationConfirmedTemplate($reservation));

        $this->safeSend($email, 'Erreur notification confirmation');
    }

    // ========== OUTILS INTERNES ==========

    /**
     * Méthode de sécurité pour envoyer l'email sans faire planter l'application
     */
    private function safeSend(Email $email, string $errorMessage): void
    {
        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log($errorMessage . ': ' . $e->getMessage());
        }
    }

    // ========== TEMPLATES HTML (Contenu inchangé) ==========

    private function getConfirmationTemplate(Avis $avis): string
    {
        $etoiles = str_repeat('⭐', $avis->getNote() ?? 0) . str_repeat('☆', 5 - ($avis->getNote() ?? 0));
        return "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px;'>
                    <h1 style='color: #667eea;'>✨ Merci pour votre avis !</h1>
                    <p>Bonjour <strong>{$avis->getNomClient()}</strong>,</p>
                    <p>Nous avons bien reçu votre avis sur votre voyage.</p>
                    <div style='background: #f9f9f9; padding: 15px;'>
                        <p>{$etoiles}</p>
                        <p><i>\"{$avis->getCommentaire()}\"</i></p>
                    </div>
                    <p>L'équipe SmartTrip</p>
                </div>
            </body>
        </html>";
    }

    private function getAdminNotificationTemplate(Avis $avis): string
    {
        return "
        <html>
            <body>
                <h1>📬 Nouvel avis à modérer</h1>
                <p><strong>Client :</strong> {$avis->getNomClient()} ({$avis->getEmail()})</p>
                <p><strong>Note :</strong> {$avis->getNote()}/5</p>
                <p><strong>Commentaire :</strong> {$avis->getCommentaire()}</p>
                <hr>
                <p><a href='http://127.0.0.1:8000/admin/avis'>Cliquer ici pour valider dans l'admin</a></p>
            </body>
        </html>";
    }

    private function getAvisApprovedTemplate(Avis $avis): string { return ""; /* Ajoute ton template ici */ }
    private function getReservationConfirmationTemplate(Reservation $res): string { return ""; /* Ajoute ton template ici */ }
    private function getNewReservationNotificationTemplate(Reservation $res): string { return ""; /* Ajoute ton template ici */ }
    private function getReservationConfirmedTemplate(Reservation $res): string { return ""; /* Ajoute ton template ici */ }
}