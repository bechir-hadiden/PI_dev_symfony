<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use App\Service\EmailService;
use App\Service\PredictiveAnalysisService;
use App\Service\GeocodingService;
use App\Service\WeatherService;
use App\Service\PhotoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AvisController extends AbstractController
{
    #[Route('/avis', name: 'app_avis_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AvisRepository $avisRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        PredictiveAnalysisService $predictiveAnalysis,
        GeocodingService $geocoding,
        WeatherService $weather,
        PhotoService $photoService,
        HttpClientInterface $httpClient
    ): Response {
        $avis = new Avis();
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setDateAvis(new \DateTime());
            
            // --- ÉTAPE 1 : PAR DÉFAUT EN ATTENTE ---
            $avis->setStatus(Avis::STATUS_PENDING);

            // --- ÉTAPE 2 : APPEL N8N ---
            try {
                $response = $httpClient->request('POST', 'http://localhost:5678/webhook/analyse-avis', [
                    'json' => ['commentaire' => $avis->getCommentaire()],
                    'timeout' => 40,
                ]);

                if ($response->getStatusCode() === 200) {
                    $content = $response->getContent();
                    $data = json_decode($content, true);

                    if (isset($data[0])) {
                        $data = $data[0];
                    }

                    $statutIA = isset($data['statut']) ? strtolower(trim((string)$data['statut'])) : '';

                    // Si l'IA approuve, on change le statut
                    if ($statutIA === 'approved' || $statutIA === Avis::STATUS_APPROVED) {
                        $avis->setStatus(Avis::STATUS_APPROVED);
                    }

                    $analysis = $avis->getPredictiveAnalysis() ?? [];
                    $analysis['ai_suggestion'] = $data['reponse_suggeree'] ?? null;
                    $avis->setPredictiveAnalysis($analysis);
                }
            } catch (\Exception $e) {
                // En cas d'erreur réseau, l'avis reste en PENDING
            }

            // --- ÉTAPE 3 : ENRICHISSEMENT (Géo, Météo, Photos) ---
            $destination = $avis->getDestination();
            if ($destination) {
                $location = $geocoding->geocode($destination);
                if ($location) {
                    $avis->setLatitude($location['lat']);
                    $avis->setLongitude($location['lng']);
                    if ($weatherData = $weather->getCurrentWeather($location['lat'], $location['lng'])) {
                        $avis->setWeatherData($weatherData);
                    }
                    $photos = $photoService->searchPhotos($destination, 3);
                    if ($photos['success'] && !empty($photos['photos'])) {
                        $avis->setPhotos($photos['photos']);
                        $avis->setMainPhoto($photos['photos'][0]['url']);
                    }
                }
            }

            // --- ÉTAPE 4 : SATISFACTION ---
            $prediction = $predictiveAnalysis->predictSatisfaction($avis);
            $avis->setSatisfactionScore($prediction['satisfaction_score'] ?? 0);
            $avis->setPredictiveAnalysis(array_merge($avis->getPredictiveAnalysis() ?? [], $prediction));

            // --- ÉTAPE 5 : SAUVEGARDE ---
            $entityManager->persist($avis);
            $entityManager->flush();

            // --- ÉTAPE 6 : EMAILS ET NOTIFICATIONS FLASH ---
            $emailService->sendConfirmationClient($avis);

            if ($avis->getStatus() === Avis::STATUS_APPROVED) {
                // NOTIFICATION VERTE : Avis positif
                $this->addFlash('success', '✅ Super ! Votre avis a été validé par notre IA et publié instantanément.');
            } else {
                // NOTIFICATION BLEUE/ORANGE : Avis négatif ou neutre
                $emailService->sendNotificationAdmin($avis);
                $this->addFlash('info', 'ℹ️ Merci. Votre avis est en cours de modération par nos agents.');
            }

            return $this->redirectToRoute('app_avis_index');
        }

        return $this->render('avis/index.html.twig', [
            'form' => $form->createView(),
            'avisList' => $avisRepository->findBy(['status' => Avis::STATUS_APPROVED], ['dateAvis' => 'DESC']),
            'stats' => [
                'total' => count($avisRepository->findAll()),
                'average_note' => $avisRepository->getAverageNote()
            ]
        ]);
    }
}