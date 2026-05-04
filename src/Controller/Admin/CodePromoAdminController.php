<?php

namespace App\Controller\Admin;

use App\Entity\Offre;
use App\Entity\CodePromo;
use App\Repository\CodePromoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CodePromoAdminController extends AbstractController
{
    #[Route('/admin/offre/{id}/coupons', name: 'app_admin_offre_coupons')]
    public function manage(Offre $offre, CodePromoRepository $repo): Response
    {
        $coupons = $repo->findBy(['offre' => $offre]);
        return $this->render('admin/offre/coupons.html.twig', [
            'offre' => $offre,
            'coupons' => $coupons
        ]);
    }

    #[Route('/admin/offre/{id}/add-coupon', name: 'app_admin_add_coupon', methods: ['POST'])]
    public function add(Offre $offre, Request $request, EntityManagerInterface $em): Response
    {
        $code = new CodePromo();
        $code->setOffre($offre);
        $code->setCodeTexte($request->request->get('code_texte'));
        $code->setDateExpiration(new \DateTime($request->request->get('date_expire')));
        
        $em->persist($code);
        $em->flush();

        return $this->redirectToRoute('app_admin_offre_coupons', ['id' => $offre->getId()]);
    }
}