<?php

namespace App\Controller;

use App\Repository\SubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mes-abonnements')]
class UserSubscriptionController extends AbstractController
{
    #[Route('/', name: 'app_user_subscription_index', methods: ['GET'])]
    public function index(\Doctrine\ORM\EntityManagerInterface $entityManager, SubscriptionRepository $subscriptionRepository): Response
    {
        $user = $this->getUser();
        
        // MOCK USER FOR TESTING WITHOUT AUTH (Consistency with UserPaiementController)
        if (!$user) {
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
            if (!$user) {
                // If really no user exists, redirect to home
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('frontoffice/subscription/index.html.twig', [
            'subscriptions' => $subscriptionRepository->findBy(['user' => $user], ['startDate' => 'DESC']),
        ]);
    }
}
