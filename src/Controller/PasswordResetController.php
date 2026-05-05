<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forgot-password', name: 'password_reset_')]
class PasswordResetController extends AbstractController
{
    // ── Step 1: Enter email ───────────────────────────────────────────────────

    #[Route('', name: 'request')]
    public function request(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        MailService $mail,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute(
                $this->isGranted('ROLE_ADMIN') ? 'admin_dashboard' : 'client_hotels'
            );
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $user = $userRepo->findOneBy(['email' => $email]);

                if ($user) {
                    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $user->setResetOtp(password_hash($otp, PASSWORD_BCRYPT));
                    $user->setResetOtpExpiresAt(new \DateTimeImmutable('+10 minutes'));
                    $em->flush();

                    try {
                        $mail->sendPasswordResetOtp($user, $otp);
                    } catch (TransportExceptionInterface $e) {
                        // Roll back OTP on mail failure
                        $user->setResetOtp(null);
                        $user->setResetOtpExpiresAt(null);
                        $em->flush();

                        // Log the actual error for debugging
                        error_log('Mailer error: ' . $e->getMessage());

                        return $this->render('security/forgot_password/step1_email.html.twig', [
                            'error' => 'Could not send the email. Error: ' . $e->getMessage(),
                        ]);
                    }
                }

                // Always redirect — prevents user enumeration
                $request->getSession()->set('reset_email', $email);
                $request->getSession()->set('reset_step', 'verify');

                return $this->redirectToRoute('password_reset_verify');
            }
        }

        return $this->render('security/forgot_password/step1_email.html.twig', [
            'error' => $error,
        ]);
    }

    // ── Step 2: Verify 6-digit OTP ────────────────────────────────────────────

    #[Route('/verify', name: 'verify')]
    public function verify(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute(
                $this->isGranted('ROLE_ADMIN') ? 'admin_dashboard' : 'client_hotels'
            );
        }

        $email = $request->getSession()->get('reset_email');
        $step  = $request->getSession()->get('reset_step');

        if (!$email || $step !== 'verify') {
            return $this->redirectToRoute('password_reset_request');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $digits = [];
            for ($i = 1; $i <= 6; $i++) {
                $digits[] = trim((string) $request->request->get("digit{$i}", ''));
            }
            $otp = implode('', $digits);

            if (strlen($otp) !== 6 || !ctype_digit($otp)) {
                $error = 'Please enter the complete 6-digit code.';
            } else {
                $user = $userRepo->findOneBy(['email' => $email]);

                if (!$user || !$user->getResetOtp() || !$user->getResetOtpExpiresAt()) {
                    $error = 'This reset session has expired. Please start over.';
                } elseif ($user->getResetOtpExpiresAt() < new \DateTimeImmutable()) {
                    $user->setResetOtp(null);
                    $user->setResetOtpExpiresAt(null);
                    $em->flush();
                    $error = 'This code has expired. Please request a new one.';
                } elseif (!password_verify($otp, $user->getResetOtp())) {
                    $error = 'Incorrect code. Please check your email and try again.';
                } else {
                    $request->getSession()->set('reset_step', 'new_password');
                    return $this->redirectToRoute('password_reset_new_password');
                }
            }
        }

        return $this->render('security/forgot_password/step2_verify.html.twig', [
            'error' => $error,
            'email' => $this->maskEmail($email),
        ]);
    }

    // ── Resend OTP ────────────────────────────────────────────────────────────

    #[Route('/resend', name: 'resend', methods: ['POST'])]
    public function resend(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        MailService $mail,
    ): Response {
        $email = $request->getSession()->get('reset_email');
        if (!$email) {
            return $this->redirectToRoute('password_reset_request');
        }

        $user = $userRepo->findOneBy(['email' => $email]);
        if ($user) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->setResetOtp(password_hash($otp, PASSWORD_BCRYPT));
            $user->setResetOtpExpiresAt(new \DateTimeImmutable('+10 minutes'));
            $em->flush();

            try {
                $mail->sendPasswordResetOtp($user, $otp);
                $this->addFlash('success', 'A new code has been sent to your email.');
            } catch (TransportExceptionInterface $e) {
                error_log('Mailer error (resend): ' . $e->getMessage());
                $this->addFlash('error', 'Could not resend the code. Error: ' . $e->getMessage());
            }
        }

        $request->getSession()->set('reset_step', 'verify');
        return $this->redirectToRoute('password_reset_verify');
    }

    // ── Step 3: Set new password ──────────────────────────────────────────────

    #[Route('/new-password', name: 'new_password')]
    public function newPassword(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute(
                $this->isGranted('ROLE_ADMIN') ? 'admin_dashboard' : 'client_hotels'
            );
        }

        $email = $request->getSession()->get('reset_email');
        $step  = $request->getSession()->get('reset_step');

        if (!$email || $step !== 'new_password') {
            return $this->redirectToRoute('password_reset_request');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $password        = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');

            if (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/', $password)) {
                $error = 'Password must include uppercase, lowercase, a number, and a special character.';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Passwords do not match.';
            } else {
                $user = $userRepo->findOneBy(['email' => $email]);
                if (!$user) {
                    return $this->redirectToRoute('password_reset_request');
                }

                $user->setPassword($hasher->hashPassword($user, $password));
                $user->setResetOtp(null);
                $user->setResetOtpExpiresAt(null);
                $em->flush();

                $request->getSession()->remove('reset_email');
                $request->getSession()->remove('reset_step');

                $this->addFlash('success', 'Password updated! Please sign in with your new password.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/forgot_password/step3_new_password.html.twig', [
            'error' => $error,
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $masked = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
        return $masked . '@' . $domain;
    }
}
