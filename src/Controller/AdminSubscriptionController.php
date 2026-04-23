<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/subscriptions')]
class AdminSubscriptionController extends AbstractController
{
    /**
     * Dashboard de gestion des abonnements.
     */
    #[Route('/', name: 'app_admin_subscription_index', methods: ['GET'])]
    public function index(SubscriptionRepository $subscriptionRepository): Response
    {
        // On récupère tous les abonnements, les plus récents en premier
        $subscriptions = $subscriptionRepository->findBy([], ['startDate' => 'DESC']);

        // Statistiques rapides
        $totalActive = 0;
        $totalRevenue = 0;
        foreach ($subscriptions as $s) {
            if ($s->isActive()) {
                $totalActive++;
                $totalRevenue += (float)$s->getPrice();
            }
        }

        return $this->render('admin/subscription/index.html.twig', [
            'subscriptions' => $subscriptions,
            'stats' => [
                'total_active' => $totalActive,
                'total_revenue' => $totalRevenue,
                'total_count' => count($subscriptions)
            ]
        ]);
    }

    /**
     * Action Admin : Suspendre manuellement un abonnement.
     */
    #[Route('/{id}/suspend', name: 'app_admin_subscription_suspend', methods: ['POST'])]
    public function suspend(Subscription $subscription, EntityManagerInterface $entityManager): Response
    {
        $subscription->suspend();
        $entityManager->flush();

        $this->addFlash('warning', "L'abonnement de " . $subscription->getUser()->getEmail() . " a été suspendu.");

        return $this->redirectToRoute('app_admin_subscription_index');
    }

    /**
     * Action Admin : Réactiver manuellement un abonnement.
     */
    #[Route('/{id}/activate', name: 'app_admin_subscription_activate', methods: ['POST'])]
    public function activate(Subscription $subscription, EntityManagerInterface $entityManager): Response
    {
        $subscription->activate();
        $entityManager->flush();

        $this->addFlash('success', "L'abonnement de " . $subscription->getUser()->getEmail() . " a été réactivé.");

        return $this->redirectToRoute('app_admin_subscription_index');
    }
}
