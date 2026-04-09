<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
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
            return false;
        }
    }
}