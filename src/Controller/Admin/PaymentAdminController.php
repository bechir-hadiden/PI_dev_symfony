<?php

namespace App\Controller\Admin;

use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/paiements', name: 'admin_paiement_')]
class PaymentAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(\Symfony\Component\HttpFoundation\Request $request, PaiementRepository $repo, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $allPaiements = $repo->findAll();
        $totalRevenue = array_reduce($allPaiements, fn($carry, $p) => $carry + $p->getAmount(), 0);
        $totalTransactions = count($allPaiements);

        $query = $repo->createQueryBuilder('p')
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('BackOffice/paiement/index.html.twig', [
            'pagination' => $pagination,
            'totalRevenue' => $totalRevenue,
            'totalTransactions' => $totalTransactions,
        ]);
    }
}
