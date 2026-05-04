<?php
// src/Controller/SignalementController.php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SignalementController extends AbstractController
{
    #[Route('/avis/{id}/signaler', name: 'app_signalement_new', methods: ['POST'])]
    public function newSignalement(
        Avis $avis,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifier que l'avis existe et est approuvé
        if (!$avis->isApproved()) {
            return $this->json(['success' => false, 'error' => 'Cet avis ne peut pas être signalé'], 400);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $motif = $data['motif'] ?? null;
        $description = $data['description'] ?? null;
        $emailSignaleur = $data['email'] ?? null;
        
        // Validation du motif
        $motifsValides = [
            Signalement::MOTIF_SPAM,
            Signalement::MOTIF_INAPPROPRIE,
            Signalement::MOTIF_FAUX,
            Signalement::MOTIF_INJURIEUX,
            Signalement::MOTIF_HARCELEMENT,
            Signalement::MOTIF_VIOLATION,
            Signalement::MOTIF_AUTRE
        ];
        
        if (!$motif || !in_array($motif, $motifsValides)) {
            return $this->json(['success' => false, 'error' => 'Motif invalide'], 400);
        }
        
        // Créer le signalement
        $signalement = new Signalement();
        $signalement->setAvis($avis);
        $signalement->setMotif($motif);
        $signalement->setDescription($description);
        $signalement->setEmailSignaleur($emailSignaleur);
        $signalement->setIpAddress($request->getClientIp());
        $signalement->setUserAgent($request->headers->get('User-Agent'));
        
        $entityManager->persist($signalement);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Merci pour votre signalement. Notre équipe va vérifier cet avis.'
        ]);
    }
    
    #[Route('/admin/signalements', name: 'app_admin_signalements')]
    public function adminSignalements(SignalementRepository $signalementRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $signalements = $signalementRepository->findPending();
        $stats = $signalementRepository->getStats();
        
        return $this->render('admin/signalements.html.twig', [
            'signalements' => $signalements,
            'stats' => $stats
        ]);
    }
    
    #[Route('/admin/signalement/{id}/traiter', name: 'app_admin_signalement_traiter', methods: ['POST'])]
    public function traiterSignalement(
        Signalement $signalement,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $signalement->setStatut(Signalement::STATUT_TRAITE);
        $entityManager->flush();
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/admin/signalement/{id}/rejeter', name: 'app_admin_signalement_rejeter', methods: ['POST'])]
    public function rejeterSignalement(
        Signalement $signalement,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $signalement->setStatut(Signalement::STATUT_REJETE);
        $entityManager->flush();
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/admin/avis/{id}/supprimer-signales', name: 'app_admin_avis_supprimer_signales', methods: ['POST'])]
    public function supprimerAvisSignale(
        Avis $avis,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Supprimer l'avis (les signalements seront supprimés en cascade)
        $entityManager->remove($avis);
        $entityManager->flush();
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/api/signalements/stats', name: 'api_signalements_stats', methods: ['GET'])]
    public function apiStats(SignalementRepository $signalementRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $stats = $signalementRepository->getStats();
        
        return $this->json($stats);
    }
}