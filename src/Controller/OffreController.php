<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Form\OffreType;
use App\Repository\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Hotel;
use App\Entity\Voyage;
use App\Entity\Vehicule;
use App\Entity\CodePromo;
use App\Service\EmailService; // Vérifie le dossier exact de ton EmailService
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/offre')]
class OffreController extends AbstractController
{
    // --- VUE CLIENT (Les Cards) ---
    #[Route('/client', name: 'app_offre_client_index', methods: ['GET'])]
    public function indexClient(OffreRepository $repo, Request $request): Response
    {
        $category = $request->query->get('cat');
        if ($category && $category !== 'ALL') {
            $offres = $repo->findBy(['category' => $category]);
        } else {
            $offres = $repo->findAll();
        }

        return $this->render('offre/index_client.html.twig', [
            'offres' => $offres,
            'active_cat' => $category ?? 'ALL'
        ]);
    }

    // --- VUE ADMIN (Le Tableau) ---
    // Note : On l'appelle 'app_offre_index' pour que les templates générés par le CRUD fonctionnent
    #[Route('/admin', name: 'app_offre_index', methods: ['GET'])]
    public function indexAdmin(OffreRepository $offreRepository): Response
    {
        $offres = $offreRepository->findAll();
        $stats = ['HOTEL' => 0, 'VOL' => 0, 'TRANSPORT' => 0, 'VOYAGE' => 0];
        foreach ($offres as $o) {
            $cat = $o->getCategory();
            if (isset($stats[$cat])) $stats[$cat]++;
        }

        return $this->render('admin/offre/index.html.twig', [
            'offres' => $offres,
            'stats' => $stats
        ]);
    }

    // src/Controller/OffreController.php

#[Route('/new', name: 'app_offre_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $offre = new Offre();
    $form = $this->createForm(OffreType::class, $offre);
    $form->handleRequest($request);
if ($form->isSubmitted() && $form->isValid()) {
            $idTarget = $form->get('id_target')->getData();
            $category = $offre->getCategory();

            // Nettoyage des liens pour éviter les conflits
            $offre->setVoyage(null);
            $offre->setHotel(null);
            $offre->setVehicule(null);

            // CHAÎNE CORRECTE if / elseif
            if ($category === 'HOTEL') {
                $hotel = $entityManager->getRepository(Hotel::class)->find($idTarget);
                $offre->setHotel($hotel);
            } elseif ($category === 'VOL') {
                $offre->setIdVol($idTarget);
            } elseif ($category === 'TRANSPORT') { // Ajout du elseif ici
                $vehicule = $entityManager->getRepository(Vehicule::class)->find($idTarget);
                $offre->setVehicule($vehicule);
            } elseif ($category === 'VOYAGE') {
                $voyage = $entityManager->getRepository(Voyage::class)->find($idTarget);
                $offre->setVoyage($voyage);
            }

            $entityManager->persist($offre);
            $entityManager->flush();

            return $this->redirectToRoute('app_offre_index');
        }

    return $this->render('offre/new.html.twig', [
        'offre' => $offre,
        'form' => $form,
    ]);
}
    // --- MODIFIER ---
    #[Route('/{id}/edit', name: 'app_offre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request); // CORRIGÉ : -> au lieu de .

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_offre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('offre/edit.html.twig', [
            'offre' => $offre,
            'form' => $form,
        ]);
    }

    // --- SUPPRIMER ---
    #[Route('/{id}', name: 'app_offre_delete', methods: ['POST'])]
    public function delete(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$offre->getId(), $request->request->get('_token'))) {
            $entityManager->remove($offre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_offre_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/admin/offre/{id}/coupons', name: 'app_admin_offre_coupons')]
public function manageCoupons(Offre $offre, EntityManagerInterface $em): Response
{
    // On récupère les coupons liés à cette offre
    $coupons = $em->getRepository(CodePromo::class)->findBy(['offre' => $offre]);

    return $this->render('admin/offre/coupons.html.twig', [
        'offre' => $offre,
        'coupons' => $coupons
    ]);
}

    // --- API : RÉCUPÉRER LES ÉLÉMENTS PAR CATÉGORIE ---
    #[Route('/api/items/{category}', name: 'app_offre_api_items', methods: ['GET'])]
    public function getItemsByCategory(string $category, EntityManagerInterface $entityManager): JsonResponse
    {
        $connection = $entityManager->getConnection();
        $items = [];

        try {
            switch ($category) {
                case 'HOTEL':
                    // On récupère l'ID et le Nom des hôtels
                    $sql = "SELECT id, name as label FROM hotels";
                    $items = $connection->fetchAllAssociative($sql);
                    break;

                case 'VOL':
                    // On récupère l'ID et la destination d'arrivée
                    $sql = "SELECT id, CONCAT('Vers ', arrivee) as label FROM vols";
                    $items = $connection->fetchAllAssociative($sql);
                    break;

                case 'TRANSPORT':
                    // On récupère l'ID du véhicule, son type et sa ville
                    $sql = "SELECT idVehicule as id, CONCAT(type, ' (', ville, ')') as label FROM vehicule";
                    $items = $connection->fetchAllAssociative($sql);
                    break;

                case 'VOYAGE':
                    // On récupère l'ID et la destination du voyage
                    $sql = "SELECT id_voyage as id, destination as label FROM voyage";
                    $items = $connection->fetchAllAssociative($sql);
                    break;
            }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e.getMessage()], 500);
        }

        // Retourne la liste en format JSON pour le JavaScript
        return new JsonResponse($items);
    }

    #[Route('/api/offre/{id}/generate-code', name: 'app_api_generate_code', methods: ['POST'])]
public function generateCode(Offre $offre, EntityManagerInterface $em): Response
{
    // LOGIQUE MÉTIER : Générer un code unique (Comme en Java)
    $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $randomCode = "SMART-" . substr(str_shuffle($chars), 0, 8);

    $codePromo = new CodePromo();
    $codePromo->setOffre($offre);
    $codePromo->setCodeTexte($randomCode);
    $codePromo->setDateExpiration((new \DateTime())->modify('+1 month'));

    $em->persist($codePromo);
    $em->flush();

    return $this->json([
        'code' => $randomCode,
        'id_code' => $codePromo->getId()
    ]);
}

#[Route('/coupon/{id}/send-mail', name: 'app_coupon_send_mail')]
public function sendCouponMail(CodePromo $coupon, EmailService $emailService): JsonResponse
{
    $user = $this->getUser(); 
    $emailDestinataire = $user ? $user->getEmail() : 'rayouf.aziz25@gmail.com';

    $sujet = "Votre Coupon SmartTrip - " . $coupon->getOffre()->getTitre();
    $message = "Félicitations ! Voici votre code promo : " . $coupon->getCodeTexte();

    // Appel du service PHP
    $success = $emailService->envoyerEmail($emailDestinataire, $sujet, $message);

    return new JsonResponse(['success' => $success]);
}

    // --- API 2 : GÉNÉRATION DU PDF (Bonus pour tes 3 APIs) ---
    #[Route('/coupon/{id}/pdf', name: 'app_coupon_pdf')]
    public function generatePdf(CodePromo $coupon): Response
    {
        // 1. Configurer Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);

        // 2. Créer le HTML du coupon (Tu peux faire un petit template à part)
        $html = "
            <div style='text-align:center; border:2px solid #1A73E8; padding:20px; border-radius:15px;'>
                <h1 style='color:#1A73E8;'>SMARTTRIP - BON DE RÉDUCTION</h1>
                <hr>
                <p>Offre : <strong>{$coupon->getOffre()->getTitre()}</strong></p>
                <h2 style='color:#FF5A1F;'>CODE : {$coupon->getCodeTexte()}</h2>
                <p>Remise : -{$coupon->getOffre()->getTauxRemise()}%</p>
                <p>Expire le : {$coupon->getDateExpiration()->format('d/m/Y')}</p>
                <br>
                <p><i>Merci de soutenir l'économie locale (ODD 8)</i></p>
            </div>
        ";

        // 3. Charger le HTML et générer le PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->render();

        // 4. Envoyer le PDF au navigateur
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="coupon_smarttrip.pdf"'
        ]);
    }


}