<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // --- AJOUTE CE BLOC ICI ---
        if ($this->getUser()) {
            // Si c'est un ADMIN, on l'envoie vers ton dashboard d'offres
            if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                return $this->redirectToRoute('app_offre_index');
            }
            // Sinon (Client), on l'envoie vers l'accueil
            return $this->redirectToRoute('app_home');
        }
        // --------------------------

        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('base_dark.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Ce corps ne s'exécute JAMAIS
        // Symfony intercepte cette route automatiquement via security.yaml
        throw new \LogicException('Intercepted by firewall.');
    }
}