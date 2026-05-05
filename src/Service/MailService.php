<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromAddress,
        private string $fromName,
    ) {}

    /**
     * Sends the password-reset OTP email to the user.
     */
    public function sendPasswordResetOtp(User $user, string $otp): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Your SmartTrip verification code: ' . $otp)
            ->htmlTemplate('emails/reset_otp.html.twig')
            ->context([
                'user' => $user,
                'otp'  => $otp,
            ]);

        $this->mailer->send($email);
    }
}
