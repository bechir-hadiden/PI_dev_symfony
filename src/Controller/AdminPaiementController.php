<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\Facture;
use App\Repository\SubscriptionRepository;
use App\Repository\PaiementRepository;
use App\Repository\UserRepository;
use App\Service\SubscriptionService;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/paiement')]
class AdminPaiementController extends AbstractController
{
    #[Route('/', name: 'app_admin_paiement_index')]
    public function index(Request $request, PaiementRepository $paiementRepository, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $sort = $request->query->get('sort', 'p.datePaiement');
        $direction = $request->query->get('direction', 'DESC');
        
        $query = $paiementRepository->createQueryBuilder('p')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $total = $paiementRepository->countAll();
        $completed = $paiementRepository->count(['status' => 'Effectué']);
        $successRate = ($total > 0) ? round(($completed / $total) * 100) : 0;

        return $this->render('backoffice/paiement/index.html.twig', [
            'pagination' => $pagination,
            'totalRevenue' => $paiementRepository->getTotalRevenue(),
            'totalTransactions' => $total,
            'pendingPayments' => $paiementRepository->count(['status' => 'En attente']),
            'successRate' => $successRate,
            'current_sort' => $sort,
            'current_direction' => $direction,
        ]);
    }

    #[Route('/export-pdf', name: 'app_admin_paiement_export_pdf')]
    public function exportPdf(PaiementRepository $paiementRepository): Response
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->renderView('export/paiement_pdf.html.twig', [
            'paiements' => $paiementRepository->findAll(),
            'title' => 'Rapport Global des Transactions - Admin'
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rapport-paiements-smarttrip.pdf"'
        ]);
    }

    #[Route('/nouveau', name: 'app_admin_paiement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository, SubscriptionRepository $subscriptionRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $user = $userRepository->find($request->request->get('user_id'));
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé.');
                return $this->redirectToRoute('app_admin_paiement_new');
            }

            if (!$request->request->get('amount') || !$request->request->get('methode_paiement') || !$request->request->get('status')) {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
                return $this->redirectToRoute('app_admin_paiement_new');
            }

            $subscription = null;
            $subscriptionId = $request->request->get('subscription_id');
            if ($subscriptionId !== null && $subscriptionId !== '') {
                $subscription = $subscriptionRepository->find((int) $subscriptionId);
                if (!$subscription || $subscription->getUser()?->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Abonnement invalide pour cet utilisateur.');
                    return $this->redirectToRoute('app_admin_paiement_new');
                }
            }

            $paiement = new Paiement();
            $paiement->setUser($user);
            $paiement->setAmount((float) $request->request->get('amount'));
            $paiement->setStatus($request->request->get('status'));
            $paiement->setMethodePaiement($request->request->get('methode_paiement'));
            $paiement->setDatePaiement(new \DateTime());
            $paiement->setReservationId($request->request->get('reservation_id') ? (int) $request->request->get('reservation_id') : null);
            $paiement->setSubscription($subscription);

            // New Billing Fields
            $paiement->setNom($request->request->get('nom'));
            $paiement->setPrenom($request->request->get('prenom'));
            $paiement->setEmail($request->request->get('email'));
            $paiement->setTelephone($request->request->get('telephone'));

            $errors = $validator->validate($paiement);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('app_admin_paiement_new');
            }

            $entityManager->persist($paiement);
            $entityManager->flush();

            $this->addFlash('success', 'Paiement créé par l\'admin.');
            return $this->redirectToRoute('app_admin_paiement_index');
        }

        return $this->render('backoffice/paiement/new.html.twig', [
            'users' => $userRepository->findAll(),
            'subscriptions' => $subscriptionRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_paiement_edit', methods: ['GET', 'POST'])]
    public function edit(#[MapEntity(id: 'id')] Paiement $paiement, Request $request, SubscriptionRepository $subscriptionRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $paiement->setAmount((float) $request->request->get('amount'));
            $paiement->setStatus($request->request->get('status'));
            $paiement->setMethodePaiement($request->request->get('methode_paiement'));

            $subscription = null;
            $subscriptionId = $request->request->get('subscription_id');
            if ($subscriptionId !== null && $subscriptionId !== '') {
                $subscription = $subscriptionRepository->find((int) $subscriptionId);
                if (!$subscription || $subscription->getUser()?->getId() !== $paiement->getUser()?->getId()) {
                    $this->addFlash('error', 'Abonnement invalide pour cet utilisateur.');
                    return $this->redirectToRoute('app_admin_paiement_edit', ['id' => $paiement->getId()]);
                }
            }
            $paiement->setSubscription($subscription);

            // Update Billing Fields
            $paiement->setNom($request->request->get('nom'));
            $paiement->setPrenom($request->request->get('prenom'));
            $paiement->setEmail($request->request->get('email'));
            $paiement->setTelephone($request->request->get('telephone'));
            
            $errors = $validator->validate($paiement);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('app_admin_paiement_edit', ['id' => $paiement->getId()]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Paiement mis à jour.');
            return $this->redirectToRoute('app_admin_paiement_index');
        }

        return $this->render('backoffice/paiement/edit.html.twig', [
            'paiement' => $paiement,
            'subscriptions' => $subscriptionRepository->findBy(['user' => $paiement->getUser()], ['id' => 'DESC']),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_paiement_delete', methods: ['POST'])]
    public function delete(#[MapEntity(id: 'id')] Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($paiement);
        $entityManager->flush();
        $this->addFlash('success', 'Paiement supprimé.');
        return $this->redirectToRoute('app_admin_paiement_index');
    }

    #[Route('/{id}/valider', name: 'app_admin_paiement_valider', methods: ['POST', 'GET'])]
    public function valider(#[MapEntity(id: 'id')] Paiement $paiement, EntityManagerInterface $entityManager, SubscriptionService $subscriptionService): Response
    {
        if ($paiement->getStatus() === 'En attente') {
            $paiement->setStatus('Effectué');
            
            $user = $paiement->getUser();
            $cashback = $paiement->getAmount() * 0.05;
            $user->setWalletBalance($user->getWalletBalance() + $cashback);
            $user->setLoyaltyPoints($user->getLoyaltyPoints() + 10);
            
            $this->createFactureLogic($paiement, $entityManager);
            
            // --- Subscription Integration ---
            if ($paiement->getSubscription()) {
                $subscriptionService->handlePaymentSuccess($paiement);
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Paiement validé avec succès (Facture générée, Loyalty Points +10, Cashback 5%)');
        }

        return $this->redirectToRoute('app_admin_paiement_index');
    }

    #[Route('/{id}/refuser', name: 'app_admin_paiement_refuser', methods: ['POST'])]
    public function refuser(Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        if ($paiement->getStatus() === 'En attente') {
            $paiement->setStatus('Refusé');
            $entityManager->flush();
            $this->addFlash('error', 'Paiement refusé.');
        }

        return $this->redirectToRoute('app_admin_paiement_index');
    }

    #[Route('/{id}/export-facture', name: 'app_admin_paiement_export_single', methods: ['GET'])]
    public function exportSinglePdf(Paiement $paiement): Response
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($pdfOptions);
        
        $facture = $paiement->getFacture();
        
        $html = $this->renderView('export/paiement_pdf.html.twig', [
            'paiements' => [$paiement],
            'facture' => $facture,
            'title' => $facture ? 'Facture ' . $facture->getNumeroFacture() : 'Reçu de Paiement N°' . $paiement->getId()
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = $facture ? $facture->getNumeroFacture() : 'paiement-' . $paiement->getId();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"'
        ]);
    }

    #[Route('/{id}/facture-creer', name: 'app_admin_paiement_facture_creer', methods: ['POST'])]
    public function createFacture(Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        if ($paiement->getStatus() !== 'Effectué') {
            $this->addFlash('error', 'Impossible de générer une facture pour un paiement non effectué.');
            return $this->redirectToRoute('app_admin_paiement_index');
        }

        if ($paiement->getFacture()) {
            $this->addFlash('info', 'Une facture existe déjà pour ce paiement.');
            return $this->redirectToRoute('app_admin_paiement_index');
        }

        $this->createFactureLogic($paiement, $entityManager);
        $entityManager->flush();

        $this->addFlash('success', 'Facture générée avec succès.');
        return $this->redirectToRoute('app_admin_paiement_index');
    }

    private function createFactureLogic(Paiement $paiement, EntityManagerInterface $entityManager): void
    {
        $facture = new Facture();
        $facture->setPaiement($paiement);
        $facture->setNumeroFacture('FAC-' . date('Y') . '-' . sprintf('%04d', $paiement->getId()));
        
        $tvaRate = 0.19;
        $montantTTC = $paiement->getAmount();
        $montantHT = $montantTTC / (1 + $tvaRate);
        
        $facture->setMontantTTC($montantTTC);
        $facture->setTva(19.0);
        $facture->setMontantHT($montantHT);
        $facture->setDateEmission(new \DateTime());

        $entityManager->persist($facture);
    }

    #[Route('/bulk-action', name: 'app_admin_paiement_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request, EntityManagerInterface $entityManager, PaiementRepository $paiementRepository): Response
    {
        $ids = $request->request->all('paiement_ids');
        $action = $request->request->get('action');

        if (empty($ids)) {
            $this->addFlash('error', 'Aucune transaction sélectionnée.');
            return $this->redirectToRoute('app_admin_paiement_index');
        }

        $count = 0;
        foreach ($ids as $id) {
            $paiement = $paiementRepository->find($id);
            if (!$paiement) continue;

            if ($action === 'valider' && $paiement->getStatus() === 'En attente') {
                $paiement->setStatus('Effectué');
                $user = $paiement->getUser();
                $cashback = $paiement->getAmount() * 0.05;
                $user->setWalletBalance($user->getWalletBalance() + $cashback);
                $user->setLoyaltyPoints($user->getLoyaltyPoints() + 10);
                $this->createFactureLogic($paiement, $entityManager);
                $count++;
            } elseif ($action === 'supprimer') {
                $entityManager->remove($paiement);
                $count++;
            }
        }

        $entityManager->flush();
        
        if ($count > 0) {
            $this->addFlash('success', "Action '$action' effectuée sur $count transactions.");
        }

        return $this->redirectToRoute('app_admin_paiement_index');
    }
}
