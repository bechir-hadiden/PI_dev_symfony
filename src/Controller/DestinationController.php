<?php

namespace App\Controller;

use App\Entity\Destination;
use App\Form\DestinationType;
use App\Repository\DestinationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\QRCodeService;
use App\Service\YouTubeService;

#[Route('/destinations', name: 'destination_')]
class DestinationController extends AbstractController
{
    public function __construct(
        #[Autowire('%images_directory%')]
        private string $imagesDirectory,
        #[Autowire(env: 'YOUTUBE_API_KEY')]
        private string $youtubeApiKey,
        private HttpClientInterface $httpClient,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, DestinationRepository $repo): Response
    {
        $q = $request->query->get('q');
        $destinations = $q ? $repo->search($q) : $repo->findWithVoyages();
        return $this->render('destination/index.html.twig', [
            'destinations' => $destinations,
            'query'        => $q,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $destination = new Destination();
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUploads($form, $destination, $slugger);
            $em->persist($destination);
            $em->flush();
            $this->addFlash('success', '✅ Destination « ' . $destination->getNom() . ' » créée !');
            return $this->redirectToRoute('destination_index');
        }

        return $this->render('destination/form.html.twig', [
            'form'        => $form->createView(),
            'destination' => $destination,
            'mode'        => 'new',
            'youtubeKey'  => $this->youtubeApiKey,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Destination $destination, QRCodeService $qrCodeService): Response
    {
        // Equivalent de QRCodeService.genererQRCodeDestination(id, nomVille, taille) en JavaFX
        $qrCodeUrl = $qrCodeService->genererQRCodeDestination(
            $destination->getId(),
            $destination->getNom() . ' ' . $destination->getPays(),
            180
        );

        return $this->render('destination/show.html.twig', [
            'destination' => $destination,
            'qrCodeUrl'   => $qrCodeUrl,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Destination $destination, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // En mode edit : on garde les images existantes et on ajoute les nouvelles
            $this->handleImageUploads($form, $destination, $slugger, keepExisting: true);
            $em->flush();
            $this->addFlash('success', '✅ Destination mise à jour !');
            return $this->redirectToRoute('destination_index');
        }

        return $this->render('destination/form.html.twig', [
            'form'        => $form->createView(),
            'destination' => $destination,
            'mode'        => 'edit',
            'youtubeKey'  => $this->youtubeApiKey,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Destination $destination, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_destination_' . $destination->getId(), $request->request->get('_token'))) {
            // Supprimer tous les fichiers images du serveur
            foreach ($destination->getAllImages() as $img) {
                $path = $this->imagesDirectory . '/' . basename($img);
                if (file_exists($path)) unlink($path);
            }
            $em->remove($destination);
            $em->flush();
            $this->addFlash('success', '🗑️ Destination supprimée.');
        }
        return $this->redirectToRoute('destination_index');
    }

    // ── YouTube proxy (utilise YouTubeService) ───────────────────
    #[Route('/api/youtube-search', name: 'youtube_search', methods: ['GET'])]
    public function youtubeSearch(Request $request, YouTubeService $youtubeService): JsonResponse
    {
        $q = $request->query->get('q', '');
        if (!$q) return $this->json([]);

        return $this->json($youtubeService->searchVideos($q, 6));
    }

    #[Route('/api/list', name: 'api_list', methods: ['GET'])]
    public function apiList(DestinationRepository $repo): JsonResponse
    {
        $data = array_map(fn(Destination $d) => [
            'id'   => $d->getId(),
            'nom'  => $d->getNom(),
            'pays' => $d->getPays(),
        ], $repo->findAll());
        return $this->json($data);
    }

    // ── Helpers ───────────────────────────────────────────────────
    private function handleImageUploads($form, Destination $destination, SluggerInterface $slugger, bool $keepExisting = false): void
    {
        // Garde les images existantes en mode edit
        $newImages = $keepExisting ? $destination->getAllImages() : [];

        // Récupère tous les fichiers uploadés (input multiple)
        $files = $form->get('imageFiles')->getData();

        // getData() retourne un seul fichier ou un tableau selon le navigateur
        if ($files && !is_array($files)) {
            $files = [$files];
        }

        foreach ((array) $files as $file) {
            if (!$file) continue;
            $safe      = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $filename  = $safe . '-' . uniqid() . '.' . $file->guessExtension();
            $file->move($this->imagesDirectory, $filename);
            $newImages[] = '/uploads/destinations/' . $filename;
        }

        // Sauvegarde tout dans une seule colonne séparée par |
        $destination->setAllImages($newImages);
    }
}