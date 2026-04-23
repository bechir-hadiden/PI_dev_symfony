<?php

namespace App\Service;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function envoyerCouponStylise(string $destinataire, string $offreNom, string $codeTexte, \DateTimeInterface $dateExpire): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('rayouf.aziz25@gmail.com', 'SmartTrip Privilège'))
                ->to($destinataire)
                ->subject('✨ Votre Coupon de Réduction SmartTrip : ' . $offreNom)
                
                // On indique le fichier Twig à utiliser
                ->htmlTemplate('emails/coupon.html.twig')
                
                // On envoie les données au template
                ->context([
                    'offreNom' => $offreNom,
                    'codeTexte' => $codeTexte,
                    'dateExpire' => $dateExpire,
                ]);

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            
            // Log l'erreur si nécessaire pour le débuggage
            return false;
        }
    }

    public function envoyerEmail(string $destinataire, string $sujet, string $contenu): bool
    {
        try {
            $email = (new Email())
                ->from('rayouf.aziz25@gmail.com') // Ton email configuré dans le .env
                ->to($destinataire)
                ->subject($sujet)
                ->text($contenu);

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            dump($e->getMessage());
            return false;
        }
    }
}

