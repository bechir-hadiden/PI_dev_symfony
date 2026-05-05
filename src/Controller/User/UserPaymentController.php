<?php

namespace App\Controller\User;

use App\Repository\PaiementRepository;
use App\Service\SarahAiService;
use App\Service\SmartAdvisorService;
use App\Service\CurrencyService;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserPaymentController extends AbstractController
{
    private SarahAiService $sarahAi;
    private SmartAdvisorService $smartAdvisor;
    private CurrencyService $currencyService;
    private EntityManagerInterface $em;

    public function __construct(SarahAiService $sarahAi, SmartAdvisorService $smartAdvisor, CurrencyService $currencyService, EntityManagerInterface $em)
    {
        $this->sarahAi = $sarahAi;
        $this->smartAdvisor = $smartAdvisor;
        $this->currencyService = $currencyService;
        $this->em = $em;
    }
    #[Route('/mes-paiements', name: 'user_paiement_index', methods: ['GET'])]
    public function index(Request $request, PaiementRepository $repo): Response
    {
        $email = $request->query->get('email');
        $paiements = [];
        $nudges = [];
        $advice = null;

        if ($email) {
            // Find payments related to reservations with this email
            $paiements = $repo->createQueryBuilder('p')
                ->where('p.email = :email')
                ->setParameter('email', $email)
                ->orderBy('p.datePaiement', 'DESC')
                ->getQuery()
                ->getResult();

            // Sarah AI Nudges
            $nudges = $this->sarahAi->getNudgesForUser($email);

            // Smart Advisor Advice
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $advice = $this->smartAdvisor->getPaymentAdvice($user, 100.0); // Mock amount for example
            }
        }

        return $this->render('FrontOffice/paiement/index.html.twig', [
            'paiements' => $paiements,
            'email' => $email,
            'nudges' => $nudges,
            'advice' => $advice,
            'eur_rate' => $this->currencyService->getExchangeRate(),
        ]);
    }
}
