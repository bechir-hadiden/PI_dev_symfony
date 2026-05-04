<?php

namespace App\Controller\User;

use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserPaymentController extends AbstractController
{
    #[Route('/mes-paiements', name: 'user_paiement_index', methods: ['GET'])]
    public function index(Request $request, PaiementRepository $repo): Response
    {
        $email = $request->query->get('email');
        $paiements = [];

        if ($email) {
            // Find payments related to reservations with this email
            $paiements = $repo->createQueryBuilder('p')
                ->join('p.reservation', 'r')
                ->where('r.emailClient = :email')
                ->setParameter('email', $email)
                ->orderBy('p.datePaiement', 'DESC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('FrontOffice/paiement/index.html.twig', [
            'paiements' => $paiements,
            'email' => $email,
        ]);
    }
}
