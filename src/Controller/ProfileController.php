<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/settings', name: 'profile_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'settings')]
    public function settings(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        /** @var User $user */
        $user      = $this->getUser();
        $errors    = [];
        $activeTab = $request->request->get('_tab', 'info');

        if ($request->isMethod('POST')) {
            $tab       = $request->request->get('_tab', 'info');
            $activeTab = $tab;

            // ── Personal Info tab ─────────────────────────────────────────────
            if ($tab === 'info') {
                $fullName = trim((string) $request->request->get('fullName', ''));
                $email    = trim((string) $request->request->get('email', ''));
                $phone    = trim((string) $request->request->get('phone', ''));
                $username = trim((string) $request->request->get('username', ''));

                if (strlen($fullName) < 2) {
                    $errors['fullName'] = 'Full name must be at least 2 characters.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Please enter a valid email address.';
                }
                if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $username)) {
                    $errors['username'] = 'Username must start with a letter and contain only letters and numbers.';
                }
                if (strlen($username) < 3) {
                    $errors['username'] = 'Username must be at least 3 characters.';
                }

                if (!isset($errors['email'])) {
                    $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                    if ($existing && $existing->getId() !== $user->getId()) {
                        $errors['email'] = 'This email is already used by another account.';
                    }
                }
                if (!isset($errors['username'])) {
                    $existing = $em->getRepository(User::class)->findOneBy(['username' => $username]);
                    if ($existing && $existing->getId() !== $user->getId()) {
                        $errors['username'] = 'This username is already taken.';
                    }
                }

                if (empty($errors)) {
                    $user->setFullName($fullName);
                    $user->setEmail($email);
                    $user->setPhone($phone ?: null);
                    $user->setUsername($username);
                    $em->flush();

                    $this->addFlash('success', 'Profile updated successfully.');
                    return $this->redirectToRoute('profile_settings', ['tab' => 'info']);
                }
            }

            // ── Change Password tab ───────────────────────────────────────────
            if ($tab === 'password') {
                // Google-only accounts have no password — block this tab
                if ($user->isGoogleAccount() && !$user->getPassword()) {
                    $errors['current_password'] = 'Your account uses Google sign-in. Set a password by using Forgot Password on the login page.';
                } else {
                    $currentPassword = (string) $request->request->get('current_password', '');
                    $newPassword     = (string) $request->request->get('new_password', '');
                    $confirmPassword = (string) $request->request->get('confirm_password', '');

                    if (!$hasher->isPasswordValid($user, $currentPassword)) {
                        $errors['current_password'] = 'Current password is incorrect.';
                    } elseif (strlen($newPassword) < 8) {
                        $errors['new_password'] = 'New password must be at least 8 characters.';
                    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/', $newPassword)) {
                        $errors['new_password'] = 'Password must contain uppercase, lowercase, a number, and a special character.';
                    } elseif ($newPassword !== $confirmPassword) {
                        $errors['confirm_password'] = 'Passwords do not match.';
                    }

                    if (empty($errors)) {
                        $user->setPassword($hasher->hashPassword($user, $newPassword));
                        $em->flush();

                        $this->addFlash('success', 'Password changed successfully.');
                        return $this->redirectToRoute('profile_settings', ['tab' => 'password']);
                    }
                }
            }
        }

        // Read tab from query string (for redirect-after-success)
        $activeTab = $request->query->get('tab', $activeTab);

        return $this->render('profile/settings.html.twig', [
            'user'       => $user,
            'errors'     => $errors,
            'active_tab' => $activeTab,
        ]);
    }
}
