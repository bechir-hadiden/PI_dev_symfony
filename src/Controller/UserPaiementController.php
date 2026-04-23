<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Repository\PaiementRepository;
use App\Service\SubscriptionService;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/paiement')]
class UserPaiementController extends AbstractController
{
    #[Route('/', name: 'app_user_paiement_index')]
    public function index(Request $request, PaiementRepository $paiementRepository, EntityManagerInterface $entityManager, \App\Service\CurrencyService $currencyService, \App\Service\GeoLocationService $geoService, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // MOCK USER FOR TESTING WITHOUT AUTH
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
        }

        $sort = $request->query->get('sort', 'p.datePaiement');
        if (strpos($sort, '.') === false) {
            $sort = 'p.' . $sort;
            $request->query->set('sort', $sort);
        }
        $direction = $request->query->get('direction', 'DESC');

        // DÉTECTION GÉO IP
        $clientIp = $request->getClientIp();
        $detectedCountry = $geoService->getCountryByIp($clientIp);

        // RÉCUPÉRATION DES RÉSERVATIONS EN ATTENTE (Pour l'IA de Rétention)
        $pendingReservations = []; // Default
        try {
            $pendingReservations = $entityManager->getRepository(\App\Entity\ReservationTransport::class)->findBy([
                'user' => $user,
                'status' => 'Pending'
            ]);
        } catch (\Exception $e) {
            // Silently ignore if entity or field doesn't exist
        }

        // PAGINATION
        $query = $paiementRepository->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery();



        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            3,
            [
                'defaultSortFieldName' => 'p.datePaiement',
                'defaultSortDirection' => 'DESC',
                'sortFieldParameterName' => 'sort',
                'sortDirectionParameterName' => 'direction',
            ]
        );

        return $this->render('frontoffice/paiement/index.html.twig', [
            'paiements' => $pagination,
            'wallet_balance' => $user->getWalletBalance(),
            'loyalty_points' => $user->getLoyaltyPoints(),
            'current_sort' => str_replace('p.', '', $sort),
            'current_direction' => $direction,
            'eur_rate' => $currencyService->getExchangeRate(),
            'detected_country' => $detectedCountry,
            'client_ip' => $clientIp,
            'pending_reservations' => $pendingReservations
        ]);
    }

    #[Route('/export-pdf', name: 'app_user_paiement_export_pdf')]
    public function exportPdf(PaiementRepository $paiementRepository, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
        }
        
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->renderView('export/paiement_pdf.html.twig', [
            'paiements' => $paiementRepository->findBy(['user' => $user], ['datePaiement' => 'DESC']),
            'title' => 'Mon Historique de Transactions'
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="ma-facturation-smarttrip.pdf"'
        ]);
    }

    #[Route('/nouveau', name: 'app_user_paiement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SubscriptionRepository $subscriptionRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator, SubscriptionService $subscriptionService, \App\Service\PaymentService $paymentService, \App\Service\CurrencyService $currencyService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // MOCK USER FOR TESTING WITHOUT AUTH
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
        }

        if (!$user) {
             $this->addFlash('error', 'Aucun utilisateur trouvé en base pour le test.');
             return $this->redirectToRoute('app_user_paiement_index');
        }

        $subscriptions = $subscriptionRepository->findBy(['user' => $user], ['startDate' => 'DESC']);

        $reservation = null;
        $resId = $request->query->get('reservation');
        if ($resId) {
            $reservation = $entityManager->getRepository(\App\Entity\ReservationTransport::class)->find($resId);
            if ($reservation && $reservation->getUser()?->getId() !== $user->getId()) {
                $reservation = null;
            }
        }

        if ($request->isMethod('POST')) {
            $amount = (float) $request->request->get('amount');
            $method = $request->request->get('method');
            
            if (!$amount || !$method) {
                $this->addFlash('error', 'Veuillez renseigner le montant et la méthode.');
                return $this->redirectToRoute('app_user_paiement_new');
            }
            
            if ($method === 'Wallet' && $user->getWalletBalance() < $amount) {
                $this->addFlash('error', 'Solde insuffisant dans votre Wallet.');
                return $this->redirectToRoute('app_user_paiement_new');
            }

            // --- Vérifier si l'utilisateur est bloqué ---
            if (in_array('ROLE_BLOCKED', $user->getRoles())) {
                $this->addFlash('error', 'Votre compte est temporairement bloqué suite à des échecs de paiement répétés. Contactez le support.');
                return $this->redirectToRoute('app_user_paiement_index');
            }

            $subscription = null;
            $subscriptionId = $request->request->get('subscription_id');
            if ($subscriptionId !== null && $subscriptionId !== '') {
                $subscription = $subscriptionRepository->findOneBy([
                    'id' => (int) $subscriptionId,
                    'user' => $user,
                ]);
                if (!$subscription) {
                    $this->addFlash('error', 'Abonnement invalide.');
                    return $this->redirectToRoute('app_user_paiement_new');
                }
            }

            $paiement = new Paiement();
            $paiement->setUser($user);
            $paiement->setAmount($amount);
            $paiement->setMethodePaiement($method);
            $paiement->setDatePaiement(new \DateTime());
            $paiement->setSubscription($subscription);
            
            // New Billing Fields
            $paiement->setNom($request->request->get('nom'));
            $paiement->setPrenom($request->request->get('prenom'));
            $paiement->setEmail($request->request->get('email'));
            $paiement->setTelephone($request->request->get('telephone'));

            if ($method === 'Stripe') {
                // ═══════════════════════════════════════════════════
                //  FLUX STRIPE : Créer un PaymentIntent et rediriger
                // ═══════════════════════════════════════════════════
                $paiement->setStatus('En attente');

                try {
                    $paymentIntent = $paymentService->createPaymentIntent(
                        $amount,
                        'eur',
                        'Paiement VoyageElite #' . time(),
                        [
                            'user_id' => $user->getId(),
                            'user_email' => $user->getEmail(),
                        ]
                    );
                    $paiement->setStripePaymentIntentId($paymentIntent->id);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur Stripe : ' . $e->getMessage());
                    return $this->redirectToRoute('app_user_paiement_new');
                }

                $errors = $validator->validate($paiement);
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                    return $this->redirectToRoute('app_user_paiement_new');
                }

                $entityManager->persist($paiement);
                $entityManager->flush();

                // Rediriger vers la page Stripe Elements Checkout
                return $this->redirectToRoute('app_stripe_checkout', ['id' => $paiement->getId()]);

            } else {
                // ═══════════════════════════════════════════════════
                //  FLUX WALLET (existant — inchangé)
                // ═══════════════════════════════════════════════════
                $paiement->setStatus('Effectué');

                $errors = $validator->validate($paiement);
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                    return $this->redirectToRoute('app_user_paiement_new');
                }

                // Original amount minus payment
                $user->setWalletBalance($user->getWalletBalance() - $amount);
                
                // Logic: 5% Cashback and 10 Loyalty Points
                $cashback = $amount * 0.05;
                $user->setWalletBalance($user->getWalletBalance() + $cashback);
                $user->setLoyaltyPoints($user->getLoyaltyPoints() + 10);
                
                $this->addFlash('success', sprintf('Paiement effectué via Wallet. Cashback de %.2f TND crédité.', $cashback));
                
                // --- Subscription Integration ---
                if ($paiement->getSubscription()) {
                    $subscriptionService->handlePaymentSuccess($paiement);
                }

                $entityManager->persist($paiement);
                $entityManager->flush();

                return $this->redirectToRoute('app_user_paiement_index');
            }
        }

        return $this->render('frontoffice/paiement/new.html.twig', [
            'user' => $user,
            'subscriptions' => $subscriptions,
            'reservation' => $reservation,
            'eur_rate' => $currencyService->getExchangeRate(),
        ]);
    }

    #[Route('/{id}/export-facture', name: 'app_user_paiement_export_single', methods: ['GET'])]
    public function exportSinglePdf(#[MapEntity(id: 'id')] Paiement $paiement): Response
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

    #[Route('/{id}/modifier', name: 'app_user_paiement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] Paiement $paiement, SubscriptionRepository $subscriptionRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
        }

        // Security Check
        if ($paiement->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce paiement.');
        }

        $subscriptions = $subscriptionRepository->findBy(['user' => $user], ['startDate' => 'DESC']);

        if ($request->isMethod('POST')) {
            $oldAmount = $paiement->getAmount();
            $oldMethod = $paiement->getMethodePaiement();
            
            $newAmount = (float) $request->request->get('amount');
            $newMethod = $request->request->get('method');
            
            // Refund old wallet payment if method changed or amount changed
            if ($oldMethod === 'Wallet') {
                $user->setWalletBalance($user->getWalletBalance() + $oldAmount);
                // Also remove cashback/points if we want for strictness (omitted for simplicity unless requested)
            }

            if ($newMethod === 'Wallet' && $user->getWalletBalance() < $newAmount) {
                // Restore old balance if error
                if ($oldMethod === 'Wallet') $user->setWalletBalance($user->getWalletBalance() - $oldAmount);
                $this->addFlash('error', 'Solde insuffisant dans votre Wallet.');
                return $this->redirectToRoute('app_user_paiement_edit', ['id' => $paiement->getId()]);
            }

            $paiement->setAmount($newAmount);
            $paiement->setMethodePaiement($newMethod);
            
            // New Billing Fields Update
            $paiement->setNom($request->request->get('nom'));
            $paiement->setPrenom($request->request->get('prenom'));
            $paiement->setEmail($request->request->get('email'));
            $paiement->setTelephone($request->request->get('telephone'));
            
            if ($newMethod === 'Wallet') {
                $paiement->setStatus('Effectué');
                $user->setWalletBalance($user->getWalletBalance() - $newAmount);
            } else {
                $paiement->setStatus('En attente');
            }

            $errors = $validator->validate($paiement);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('app_user_paiement_edit', ['id' => $paiement->getId()]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Paiement mis à jour avec succès.');
            return $this->redirectToRoute('app_user_paiement_index');
        }

        return $this->render('frontoffice/paiement/edit.html.twig', [
            'paiement' => $paiement,
            'subscriptions' => $subscriptions,
            'user' => $user
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_user_paiement_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, #[MapEntity(id: 'id')] Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
        }

        if ($paiement->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce paiement.');
        }

        if ($this->isCsrfTokenValid('delete'.$paiement->getId(), $request->request->get('_token'))) {
            // Refund if Wallet
            if ($paiement->getMethodePaiement() === 'Wallet' && $paiement->getStatus() === 'Effectué') {
                $user->setWalletBalance($user->getWalletBalance() + $paiement->getAmount());
            }

            $entityManager->remove($paiement);
            $entityManager->flush();
            $this->addFlash('success', 'Paiement supprimé.');
        }

        return $this->redirectToRoute('app_user_paiement_index');
    }
}
