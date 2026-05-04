<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Service\ContactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function index(Request $request, ContactService $contactService): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData(); // ← tableau complet avec prenom, nom, email, etc.

            try {
                $contactService->envoyerContact($data); // ← on passe le tableau directement

                $this->addFlash('contact_success', 'Votre message a été envoyé avec succès ! Nous vous répondrons sous 24h.');
                return $this->redirectToRoute('contact');

            } catch (\Exception $e) {
                $this->addFlash('contact_error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
            }
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}