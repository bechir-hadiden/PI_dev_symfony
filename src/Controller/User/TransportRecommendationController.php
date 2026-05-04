<?php

namespace App\Controller\User;

use App\Service\SmartAdvisorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/transport/recommendation', name: 'user_transport_recommendation_')]
class TransportRecommendationController extends AbstractController
{
    /**
     * Page du formulaire de recommandation intelligente.
     */
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, SmartAdvisorService $advisor): Response
    {
        $recommendation = null;
        
        if ($request->isMethod('POST')) {
            $depart     = $request->request->get('depart', 'Tunis');
            $arrivee    = $request->request->get('arrivee', 'Hammamet');
            $budget     = (float)$request->request->get('budget', 100);
            $passengers = (int)$request->request->get('passengers', 1);
            $hour       = $request->request->get('hour', date('H:i'));

            $recommendation = $advisor->getRecommendations($depart, $arrivee, $budget, $passengers, $hour);
            
            return $this->render('FrontOffice/recommendation/result.html.twig', [
                'recommendation' => $recommendation,
                'params' => [
                    'depart' => $depart,
                    'arrivee' => $arrivee,
                    'budget' => $budget,
                    'passengers' => $passengers,
                    'hour' => $hour
                ]
            ]);
        }

        return $this->render('FrontOffice/recommendation/index.html.twig');
    }

    /**
     * Génère un ticket/plan de route en PDF (Bundle externe).
     */
    #[Route('/export-pdf', name: 'export_pdf', methods: ['POST'])]
    public function exportPdf(Request $request): Response
    {
        $data = json_decode($request->request->get('pdf_data', '{}'), true);

        $html = $this->renderView('FrontOffice/recommendation/pdf_plan.html.twig', [
            'data' => $data
        ]);

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Plan_Voyage_Intelligent.pdf"'
        ]);
    }
}
