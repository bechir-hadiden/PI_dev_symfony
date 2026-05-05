<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleOAuthController extends AbstractController
{
    // ── Step 1: Redirect user to Google consent screen ────────────────────────

    #[Route('/auth/google/connect', name: 'google_oauth_connect')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        // Scopes passed here directly — NOT in config file
        return $clientRegistry
            ->getClient('google')
            ->redirect(['openid', 'email', 'profile'], []);
    }

    // ── Step 2: Handle callback from Google ───────────────────────────────────

    #[Route('/auth/google/callback', name: 'google_oauth_callback')]
    public function callback(
        ClientRegistry $clientRegistry,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        Security $security,
    ): Response {
        try {
            $client = $clientRegistry->getClient('google');

            /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
            $googleUser = $client->fetchUser();

            $googleId = (string) $googleUser->getId();
            $email    = (string) $googleUser->getEmail();
            $name     = (string) ($googleUser->getName() ?: $email);
            $avatar   = $googleUser->getAvatar();

            // 1. Try by Google ID first
            $user = $userRepo->findOneBy(['googleId' => $googleId]);

            // 2. Fall back to email (user may have registered via form earlier)
            if (!$user) {
                $user = $userRepo->findOneBy(['email' => $email]);
            }

            // 3. Brand-new user — create a CLIENT account
            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setFullName($name);
                $user->setUsername($this->makeUniqueUsername($email, $userRepo));
                $user->setRole('CLIENT');
                // No password — Google-only account
            }

            // Always update Google ID and avatar
            $user->setGoogleId($googleId);
            if ($avatar && !$user->getAvatar()) {
                $user->setAvatar($avatar);
            }

            // Block check before saving
            if ($user->isBlocked()) {
                $this->addFlash('error', 'Your account is blocked. Contact admin@smarttrip.com');
                return $this->redirectToRoute('app_login');
            }

            $em->persist($user);
            $em->flush();

            // Log in programmatically
            $security->login($user, 'App\Security\AppAuthenticator', 'main');

            $this->addFlash('success', 'Welcome, ' . $user->getFullName() . '! Signed in with Google.');

            return $this->redirectToRoute(
                $user->isAdmin() ? 'admin_dashboard' : 'client_hotels'
            );

        } catch (\Throwable) {
            $this->addFlash('error', 'Google sign-in failed. Please try again or use email/password.');
            return $this->redirectToRoute('app_login');
        }
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function makeUniqueUsername(string $email, UserRepository $userRepo): string
    {
        $base = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]);
        $base = ltrim($base, '0123456789') ?: 'user';

        $username = $base;
        $i        = 1;
        while ($userRepo->findOneBy(['username' => $username])) {
            $username = $base . $i++;
        }

        return $username;
    }
}
