<?php

namespace App\Controller\Admin;

use App\Entity\Transport;
use App\Form\TransportFormType;
use App\Repository\TransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\AiTransportService;
use App\Service\TransportBusinessService;
use App\Service\WeatherApiService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/transports', name: 'admin_transport_')]
class TransportAdminController extends AbstractController
{
    // ── INDEX ───────────────────────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, TransportRepository $repo, PaginatorInterface $paginator, WeatherApiService $weatherApi): Response
    {
        $q     = $request->query->get('q', '');
        $sort  = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'ASC');

        // Api B (API + Metier) : Récupération de la météo pour affichage métier
        $weather = $weatherApi->getTravelAdvice();

        $query = $repo->createQueryBuilder('t');
        if ($q) {
            $query->where('t.compagnie LIKE :q OR t.numero LIKE :q')
                  ->setParameter('q', '%'.$q.'%');
        }
        
        $validSorts = ['id', 'compagnie', 'numero', 'capacite', 'prix'];
        $sortField  = in_array($sort, $validSorts) ? $sort : 'id';
        $orderDir   = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $query->orderBy('t.' . $sortField, $orderDir);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5 // Limite par page
        );

        return $this->render('BackOffice/transport/index.html.twig', [
            'pagination' => $pagination,
            'weather'    => $weather,
            'q'          => $q,
            'sort'       => $sort,
            'order'      => $order,
        ]);
    }

    // ── NEW ─────────────────────────────────────────────────────────────────
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $transport = new Transport();
        $form      = $this->createForm(TransportFormType::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($transport);
            $em->flush();
            $this->addFlash('success', 'Véhicule « ' . $transport->getCompagnie() . ' » créé !');
            return $this->redirectToRoute('admin_transport_index');
        }

        return $this->render('BackOffice/transport/form.html.twig', [
            'form'      => $form->createView(),
            'transport' => $transport,
            'mode'      => 'new',
        ]);
    }

    // ── SHOW ────────────────────────────────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Transport $transport, TransportBusinessService $businessService): Response
    {
        // Métier Avancé : Calcul de prix dynamique et eco-score
        $ecoScore     = $businessService->calculateEcoScore($transport);
        $dynamicPrice = $businessService->calculateDynamicPrice($transport);

        return $this->render('BackOffice/transport/show.html.twig', [
            'transport'     => $transport,
            'ecoScore'      => $ecoScore,
            'dynamicPrice'  => $dynamicPrice,
        ]);
    }

    #[Route('/{id}/ai-description', name: 'ai_description', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function generateAiDescription(Transport $transport, AiTransportService $aiService): JsonResponse
    {
        // IA (Api D) : Génération de description marketing via IA
        $description = $aiService->generateMarketingDescription($transport);
        return new JsonResponse(['description' => $description]);
    }

    // ── EDIT ────────────────────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Transport $transport, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TransportFormType::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Véhicule mis à jour !');
            return $this->redirectToRoute('admin_transport_show', ['id' => $transport->getId()]);
        }

        return $this->render('BackOffice/transport/form.html.twig', [
            'form'      => $form->createView(),
            'transport' => $transport,
            'mode'      => 'edit',
        ]);
    }

    // ── DELETE ──────────────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Transport $transport, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_transport_' . $transport->getId(), $request->request->get('_token'))) {
            $em->remove($transport);
            $em->flush();
            $this->addFlash('success', 'Véhicule supprimé.');
        }
        return $this->redirectToRoute('admin_transport_index');
    }

    // ── PDF ─────────────────────────────────────────────────────────────────
    #[Route('/pdf', name: 'pdf', methods: ['GET'], priority: 10)]
    public function generatePdf(Request $request, TransportRepository $repo): Response
    {
        // 1. Fetch filtered/sorted data
        $q     = $request->query->get('q', '');
        $sort  = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'ASC');

        $transports = $repo->findSortedSearch($q, $sort, $order);

        // 2. Render view to HTML
        $html = $this->renderView('BackOffice/transport/pdf_list.html.twig', [
            'transports' => $transports,
            'q'          => $q
        ]);

        // 3. Configure Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true); // Pour autoriser les images externes (si SSL ok)

        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');

        // 4. Compile HTML to PDF
        $dompdf->render();

        // 5. Output PDF (Stream download)
        $dompdf->stream('Catalogue_Transports.pdf', [
            'Attachment' => true
        ]);
        
        return new Response();
    }
}
