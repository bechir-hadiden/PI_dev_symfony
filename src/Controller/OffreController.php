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
use App\Entity\Vol;
use App\Service\EmailService; 
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;

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

    #[Route('/admin', name: 'app_offre_index', methods: ['GET'])]
public function indexAdmin(OffreRepository $offreRepository, PaginatorInterface $paginator, Request $request): Response
{
    // 1. On prépare la requête (Query, pas encore exécutée)
    $query = $offreRepository->createQueryBuilder('o')
        ->orderBy('o.id', 'DESC')
        ->getQuery();

    // 2. On crée l'objet de pagination (C'est cet objet que Twig veut !)
    $pagination = $paginator->paginate(
        $query, 
        $request->query->getInt('page', 1), 
        5 
    );

    // 3. Logique des statistiques (On utilise un autre nom de variable !)
    $toutesLesOffres = $offreRepository->findAll(); 
    $stats = ['HOTEL' => 0, 'VOL' => 0, 'TRANSPORT' => 0, 'VOYAGE' => 0];
    foreach ($toutesLesOffres as $o) {
        $cat = $o->getCategory();
        if (isset($stats[$cat])) $stats[$cat]++;
    }

    // 4. On envoie BIEN le $pagination à la vue
    return $this->render('admin/offre/index.html.twig', [
        'offres' => $pagination, // <--- C'est ici que la magie opère
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
    // Dans OffreController.php (méthode new ET méthode edit)
    if ($form->isSubmitted() && $form->isValid()) {
        $idTarget = $form->get('id_target')->getData();
        $category = $offre->getCategory();

        // 1. Reset toutes les relations pour éviter les mélanges
        $offre->setVoyage(null);
        $offre->setHotel(null);
        $offre->setVehicule(null);
        $offre->setVol(null);

        // 2. Affectation de l'objet réel selon la catégorie (CHAÎNE CONTINUE)
        if ($category === 'HOTEL') {
            $hotel = $entityManager->getRepository(Hotel::class)->find($idTarget);
            $offre->setHotel($hotel);
        } 
        elseif ($category === 'VOL') {
            $vol = $entityManager->getRepository(Vol::class)->find($idTarget);
            $offre->setVol($vol);
        } 
        elseif ($category === 'TRANSPORT') {
            $vehicule = $entityManager->getRepository(Vehicule::class)->find($idTarget);
            $offre->setVehicule($vehicule);
        } 
        elseif ($category === 'VOYAGE') {
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
    #[Route('/{id}/edit', name: 'app_offre_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(OffreType::class, $offre);
    $form->handleRequest($request);

    // Dans OffreController.php (méthode new ET méthode edit)
    if ($form->isSubmitted() && $form->isValid()) {
        $idTarget = $form->get('id_target')->getData();
        $category = $offre->getCategory();

        // 1. Reset toutes les relations pour éviter les mélanges
        $offre->setVoyage(null);
        $offre->setHotel(null);
        $offre->setVehicule(null);
        $offre->setVol(null);

        // 2. Affectation de l'objet réel selon la catégorie (CHAÎNE CONTINUE)
        if ($category === 'HOTEL') {
            $hotel = $entityManager->getRepository(Hotel::class)->find($idTarget);
            $offre->setHotel($hotel);
        } 
        elseif ($category === 'VOL') {
            $vol = $entityManager->getRepository(Vol::class)->find($idTarget);
            $offre->setVol($vol);
        } 
        elseif ($category === 'TRANSPORT') {
            $vehicule = $entityManager->getRepository(Vehicule::class)->find($idTarget);
            $offre->setVehicule($vehicule);
        } 
        elseif ($category === 'VOYAGE') {
            $voyage = $entityManager->getRepository(Voyage::class)->find($idTarget);
            $offre->setVoyage($voyage);
        }

        $entityManager->persist($offre);
        $entityManager->flush();

        return $this->redirectToRoute('app_offre_index');
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

    // APPEL DE LA NOUVELLE MÉTHODE STYLISÉE
    $emailService->envoyerCouponStylise(
    $emailDestinataire, 
    $coupon->getOffre()->getTitre(), // <--- AJOUTE ->getTitre() ICI
    $coupon->getCodeTexte(), 
    $coupon->getDateExpiration()
);;

    return new JsonResponse(['success' => true]);
}


#[Route('/coupon/{id}/pdf', name: 'app_coupon_pdf')]
    public function generatePdf(CodePromo $coupon): Response
    {
        // 1. GÉNÉRATION DU QR CODE
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($coupon->getCodeTexte())
            ->encoding(new Encoding('UTF-8'))
            // --- MODIFICATION ICI ---
            ->errorCorrectionLevel(ErrorCorrectionLevel::High) 
            // ------------------------
            ->size(150)
            ->margin(10)
            ->build();

        $qrCodeBase64 = $result->getDataUri();

        // 2. CONFIGURATION DE DOMPDF
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);

        // 3. DESIGN DU HTML (Ton code existant...)
        $html = "
        <div style='text-align:center; border: 4px solid #b8963e; padding: 30px;'>
            <h1 style='color: #0d0d0d;'>SMARTTRIP</h1>
            <p style='letter-spacing: 2px; font-size: 14px; color: #b8963e; text-transform: uppercase;'>
                {$coupon->getOffre()->getLabelDestination()}
            </p>
            <div style='margin: 20px 0;'>
                <img src='{$qrCodeBase64}' style='width: 150px;'>
                <p style='font-size: 22px; font-weight: bold; color: #b8963e;'>{$coupon->getCodeTexte()}</p>
            </div>
            <h2>REMISE : -{$coupon->getOffre()->getTauxRemise()}%</h2>
        </div>
        ";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }


 #[Route('/api/ai-analyze', name: 'app_offre_api_ai', methods: ['POST'])]
public function analyzeAI(Request $request, HttpClientInterface $httpClient): JsonResponse
{
    $text = $request->request->get('text', '');
    $apiToken = trim($this->getParameter('app.hf_key'));

    $categoryLabels = [
        'Luxe'       => 'This is a luxury hotel, spa, or premium suite travel offer with high-end amenities',
        'Aventure'   => 'This is an adventure, outdoor, hiking, or extreme sports travel offer',
        'Famille'    => 'This is a family-friendly vacation or travel offer with activities for children',
        'Economique' => 'This is a budget-friendly, low-cost, or economical travel deal',
    ];

    $labelFallback = $this->keywordFallback($text);
    $odd8 = $this->detectODD8($text);  // 👈 détection ODD 8 sur le texte brut

    if (empty($apiToken)) {
        return new JsonResponse([
            'label'  => $labelFallback,
            'source' => 'fallback',
            'odd8'   => $odd8,
        ]);
    }

    try {
        $response = $httpClient->request('POST', 'https://api-inference.huggingface.co/models/facebook/bart-large-mnli', [
            'headers' => ['Authorization' => 'Bearer ' . $apiToken],
            'json' => [
                'inputs' => $text,
                'parameters' => [
                    'candidate_labels' => array_values($categoryLabels),
                    'multi_label'      => false,
                ],
                'options' => ['wait_for_model' => true],
            ],
            'verify_peer' => false,
            'timeout'     => 8,
        ]);

        $result = $response->toArray(false);

        if (!isset($result['labels'][0], $result['scores'][0])) {
            return new JsonResponse([
                'label'  => $labelFallback,
                'source' => 'fallback',
                'odd8'   => $odd8,
            ]);
        }

        if ($result['scores'][0] < 0.35) {
            return new JsonResponse([
                'label'  => $labelFallback,
                'source' => 'fallback',
                'odd8'   => $odd8,
            ]);
        }

        $winnerPhrase = $result['labels'][0];
        $frenchLabel  = array_search($winnerPhrase, $categoryLabels);

        if ($frenchLabel === false) {
            return new JsonResponse([
                'label'  => $labelFallback,
                'source' => 'fallback',
                'odd8'   => $odd8,
            ]);
        }

        return new JsonResponse([
            'label'  => $frenchLabel,
            'score'  => round($result['scores'][0] * 100),
            'source' => 'ai',
            'odd8'   => $odd8,   // 👈 toujours inclus
        ]);

    } catch (\Exception $e) {
        return new JsonResponse([
            'label'  => $labelFallback,
            'source' => 'fallback',
            'odd8'   => $odd8,
        ]);
    }
}

private function keywordFallback(string $text): string
{
    $t = mb_strtolower($text);

    $keywords = [
        'Luxe'       => ['luxe', 'luxury', 'premium', 'suite', 'spa', 'panoramique',
                         'palace', 'vip', 'exclusif', 'haut de gamme', 'service d\'étage', 'villa'],
        'Aventure'   => ['aventure', 'trek', 'randonnée', 'escalade', 'safari', 'surf',
                         'plongée', 'montagne', 'jungle', 'expédition', 'sport extrême', 'désert','éco-responsable', 'écotourisme', 'économie circulaire', 'circuit court'],
        'Famille'    => ['famille', 'enfant', 'kids', 'club', 'animé', 'baby',
                         'familial', 'tout compris', 'all inclusive', 'jeux', 'crèche'],
        'Economique' => ['budget', 'pas cher', 'low cost', 'promo',
                         'réduction', 'discount', 'abordable', 'bon marché', 'tarif réduit'],
    ];

    $scores = array_fill_keys(array_keys($keywords), 0);
    foreach ($keywords as $category => $words) {
        foreach ($words as $word) {
            if (str_contains($t, $word)) {
                $scores[$category]++;
            }
        }
    }
    arsort($scores);
    $best = array_key_first($scores);
    return ($scores[$best] > 0) ? $best : 'Economique';
}

private function detectODD8(string $text): bool
{
    $t = mb_strtolower($text);

    $odd8Keywords = [
        // Travail et production locale
        'local', 'artisan', 'artisanat', 'régional', 'région',
        'producteur local', 'commerce local', 'made in',
        // Économie responsable
        'économie circulaire', 'circuit court', 'durable', 'responsable',
        'éco-responsable', 'solidaire', 'équitable', 'bio',
        // Emploi et communauté
        'guide local', 'famille locale', 'communauté', 'coopérative',
        'association', 'emploi local', 'habitants',
        // Tourisme responsable
        'tourisme responsable', 'écotourisme', 'impact positif',
        'soutien local', 'économie locale',
    ];

    foreach ($odd8Keywords as $keyword) {
        if (str_contains($t, $keyword)) {
            return true;
        }
    }

    return false;
}

#[Route('/api/ai-enhance', name: 'app_offre_api_ai_enhance', methods: ['POST'])]
public function enhanceDescription(Request $request, HttpClientInterface $httpClient): JsonResponse
{
    $text = $request->request->get('text', '');
    $apiToken = trim($this->getParameter('app.hf_key'));

    if (strlen($text) < 15) {
        return new JsonResponse(['error' => 'Description trop courte.'], 400);
    }

    if (empty($apiToken)) {
        return new JsonResponse(['enhanced' => $this->localEnhance($text)]);
    }

    $prompt = "Write an elegant luxury French travel offer description based on this: " . $text 
            . " Make it sophisticated and attractive in 2 sentences.";

    try {
        $response = $httpClient->request('POST',
            'https://api-inference.huggingface.co/models/google/flan-t5-base', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'inputs'  => $prompt,
                'options' => ['wait_for_model' => true],
            ],
            'verify_peer' => false,
            'timeout'     => 20,
        ]);

        $result = $response->toArray(false);

        // flan-t5 retourne [{"generated_text": "..."}]
        if (!empty($result[0]['generated_text'])) {
            $enhanced = trim($result[0]['generated_text']);

            // Si le modèle retourne quelque chose de trop court ou inutile → fallback local
            if (strlen($enhanced) < 20) {
                return new JsonResponse(['enhanced' => $this->localEnhance($text)]);
            }

            return new JsonResponse(['enhanced' => $enhanced]);
        }

        return new JsonResponse(['enhanced' => $this->localEnhance($text)]);

    } catch (\Exception $e) {
        // Fallback local garanti — ne jamais montrer une erreur pour cette feature
        return new JsonResponse(['enhanced' => $this->localEnhance($text)]);
    }
}

/**
 * Fallback local : réécriture par templates prédéfinis selon les mots-clés.
 * Garantit un résultat élégant même sans API.
 */
private function localEnhance(string $text): string
{
    $t = mb_strtolower($text);

    // ── LUXE / SPA / SUITE ──
    if (str_contains($t, 'spa') || str_contains($t, 'suite') || str_contains($t, 'luxe')
        || str_contains($t, 'palace') || str_contains($t, 'vip') || str_contains($t, 'premium')
        || str_contains($t, 'prestige') || str_contains($t, 'étoile') || str_contains($t, 'villa')
        || str_contains($t, 'jacuzzi') || str_contains($t, 'butler') || str_contains($t, 'privatif')) {
        return "Évadez-vous dans un écrin de luxe absolu où chaque détail a été pensé pour votre bien-être : "
             . "suite panoramique, spa privatif et service personnalisé 24h/24 vous attendent. "
             . "Une expérience d'exception réservée aux voyageurs les plus exigeants.";
    }

    // ── HÔTEL / PISCINE / HÉBERGEMENT ──
    if (str_contains($t, 'piscine') || str_contains($t, 'hôtel') || str_contains($t, 'hotel')
        || str_contains($t, 'hébergement') || str_contains($t, 'chambre') || str_contains($t, 'resort')
        || str_contains($t, 'séjour') || str_contains($t, 'nuit') || str_contains($t, 'pension')
        || str_contains($t, 'résidence') || str_contains($t, 'lodge')) {
        return "Plongez dans une oasis de sérénité où des prestations cinq étoiles redéfinissent l'art du séjour. "
             . "Chaque espace a été conçu pour allier confort absolu et esthétique raffinée. "
             . "Un refuge d'élégance où chaque instant devient un souvenir impérissable.";
    }

    // ── AVENTURE / NATURE / SPORT ──
    if (str_contains($t, 'aventure') || str_contains($t, 'trek') || str_contains($t, 'montagne')
        || str_contains($t, 'randonnée') || str_contains($t, 'safari') || str_contains($t, 'jungle')
        || str_contains($t, 'désert') || str_contains($t, 'escalade') || str_contains($t, 'surf')
        || str_contains($t, 'plongée') || str_contains($t, 'kayak') || str_contains($t, 'nature')
        || str_contains($t, 'forêt') || str_contains($t, 'volcan') || str_contains($t, 'expédition')) {
        return "Partez à la conquête de paysages grandioses avec nos expéditions d'exception, "
             . "guidées par des experts passionnés et équipées pour votre confort total en pleine nature. "
             . "L'aventure ultime, vécue avec le raffinement qui vous est dû.";
    }

    // ── FAMILLE / ENFANTS ──
    if (str_contains($t, 'famille') || str_contains($t, 'enfant') || str_contains($t, 'kids')
        || str_contains($t, 'baby') || str_contains($t, 'club') || str_contains($t, 'animé')
        || str_contains($t, 'animation') || str_contains($t, 'crèche') || str_contains($t, 'mini-club')
        || str_contains($t, 'tout compris') || str_contains($t, 'all inclusive')
        || str_contains($t, 'toboggan') || str_contains($t, 'parc')) {
        return "Offrez à votre famille des souvenirs inoubliables dans un cadre idyllique "
             . "alliant confort premium et activités sur mesure pour petits et grands. "
             . "Le bonheur partagé, élevé au rang d'art de vivre.";
    }

    // ── VOL / TRANSPORT ──
    if (str_contains($t, 'vol') || str_contains($t, 'avion') || str_contains($t, 'billet')
        || str_contains($t, 'aéroport') || str_contains($t, 'low cost') || str_contains($t, 'classe')
        || str_contains($t, 'business') || str_contains($t, 'first class') || str_contains($t, 'escale')
        || str_contains($t, 'transfert') || str_contains($t, 'navette') || str_contains($t, 'limousine')
        || str_contains($t, 'chauffeur') || str_contains($t, 'vip transfer')) {
        return "Voyagez dans un confort absolu à bord de nos vols et transferts premium, "
             . "où chaque détail de votre trajet est orchestré avec précision et élégance. "
             . "De votre départ à votre arrivée, l'excellence vous accompagne.";
    }

    // ── PLAGE / MER / SOLEIL ──
    if (str_contains($t, 'plage') || str_contains($t, 'mer') || str_contains($t, 'soleil')
        || str_contains($t, 'côte') || str_contains($t, 'méditerranée') || str_contains($t, 'caraïbes')
        || str_contains($t, 'tropical') || str_contains($t, 'île') || str_contains($t, 'lagon')
        || str_contains($t, 'sable') || str_contains($t, 'yacht') || str_contains($t, 'croisière')) {
        return "Laissez-vous bercer par le doux murmure des vagues dans notre sélection de destinations balnéaires d'exception. "
             . "Sable fin, eaux cristallines et soleil généreux composent le décor parfait "
             . "d'un séjour où le temps suspend son cours.";
    }

    // ── CULTURE / CITY TRIP ──
    if (str_contains($t, 'culture') || str_contains($t, 'ville') || str_contains($t, 'musée')
        || str_contains($t, 'patrimoine') || str_contains($t, 'visite') || str_contains($t, 'découverte')
        || str_contains($t, 'city') || str_contains($t, 'médina') || str_contains($t, 'gastronomie')
        || str_contains($t, 'architecture') || str_contains($t, 'histoire') || str_contains($t, 'art')) {
        return "Immergez-vous dans l'âme authentique de destinations chargées d'histoire et de culture, "
             . "guidé par des experts locaux passionnés qui vous révèlent les trésors cachés de chaque lieu. "
             . "Un voyage qui éveille les sens et enrichit l'esprit.";
    }

    // ── ROMANTIQUE / COUPLE ──
    if (str_contains($t, 'romantique') || str_contains($t, 'couple') || str_contains($t, 'amoureux')
        || str_contains($t, 'lune de miel') || str_contains($t, 'honeymoon') || str_contains($t, 'mariage')
        || str_contains($t, 'saint-valentin') || str_contains($t, 'intime') || str_contains($t, 'escapade')) {
        return "Offrez-vous une escapade romantique d'exception dans un cadre enchanteur, "
             . "où dîners aux chandelles, suites intimistes et attentions personnalisées "
             . "transforment chaque moment en un souvenir éternel à deux.";
    }

    // ── GASTRONOMIE / CUISINE ──
    if (str_contains($t, 'gastronom') || str_contains($t, 'cuisine') || str_contains($t, 'chef')
        || str_contains($t, 'restaurant') || str_contains($t, 'dégustation') || str_contains($t, 'vin')
        || str_contains($t, 'saveur') || str_contains($t, 'terroir') || str_contains($t, 'menu')) {
        return "Embarquez pour un voyage culinaire d'exception orchestré par des chefs de renom, "
             . "où chaque plat raconte l'histoire d'un terroir et d'un savoir-faire ancestral. "
             . "La gastronomie élevée au rang d'expérience sensorielle inoubliable.";
    }

    // ── BIEN-ÊTRE / DÉTENTE / RELAXATION ──
    if (str_contains($t, 'bien-être') || str_contains($t, 'détente') || str_contains($t, 'relaxation')
        || str_contains($t, 'massage') || str_contains($t, 'yoga') || str_contains($t, 'méditation')
        || str_contains($t, 'hammam') || str_contains($t, 'thalasso') || str_contains($t, 'retraite')
        || str_contains($t, 'ressourcement') || str_contains($t, 'zen')) {
        return "Offrez-vous une parenthèse de bien-être absolu dans notre sanctuaire de sérénité, "
             . "où massages signature, soins exclusifs et atmosphère apaisante "
             . "vous invitent à une régénération profonde du corps et de l'esprit.";
    }

    // ── ÉCONOMIQUE / PROMO / BUDGET ──
    if (str_contains($t, 'promo') || str_contains($t, 'budget') || str_contains($t, 'réduction')
        || str_contains($t, 'discount') || str_contains($t, 'offre') || str_contains($t, 'remise')
        || str_contains($t, 'pas cher') || str_contains($t, 'abordable') || str_contains($t, 'économique')) {
        return "Profitez de notre sélection d'offres privilèges soigneusement négociées "
             . "pour vous garantir le meilleur rapport qualité-prestige du marché. "
             . "L'excellence du voyage, accessible à ceux qui savent saisir les meilleures opportunités.";
    }

    // ── LOCAL / ODD 8 ──
    if (str_contains($t, 'local') || str_contains($t, 'artisan') || str_contains($t, 'terroir')
        || str_contains($t, 'communauté') || str_contains($t, 'solidaire') || str_contains($t, 'durable')
        || str_contains($t, 'responsable') || str_contains($t, 'éco') || str_contains($t, 'circuit court')) {
        return "Vivez une expérience de voyage authentique et responsable au cœur des communautés locales, "
             . "où artisans passionnés et guides du terroir vous ouvrent les portes d'une culture vivante. "
             . "Un tourisme qui fait du bien, à vous comme aux destinations que vous visitez.";
    }

    // ── GÉNÉRIQUE — aucun mot-clé détecté ──
    return "Laissez-vous séduire par cette offre d'exception, soigneusement orchestrée "
         . "pour vous offrir une expérience de voyage inoubliable alliant prestige et authenticité. "
         . "Chaque détail a été pensé pour transformer votre escapade en un moment hors du temps.";
}
}