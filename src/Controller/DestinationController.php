<?php

namespace App\Controller;

use App\Entity\Destination;
use App\Form\DestinationType;
use App\Repository\DestinationRepository;
use App\Service\QRCodeService;
use App\Service\YouTubeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/destinations', name: 'admin_destination_')]
class DestinationController extends AbstractController
{
    public function __construct(
        #[Autowire('%images_directory%')]
        private string $imagesDirectory,
    ) {}

    // ── INDEX ──────────────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, DestinationRepository $repo): Response
    {
        $query = $request->query->get('q', '');

        $destinations = $query
            ? $repo->createQueryBuilder('d')
                ->where('d.nom LIKE :q OR d.pays LIKE :q')
                ->setParameter('q', '%' . $query . '%')
                ->orderBy('d.order', 'ASC')
                ->getQuery()->getResult()
            : $repo->findBy([], ['order' => 'ASC']);

        return $this->render('destination/index.html.twig', [
            'destinations' => $destinations,
            'query'        => $query,
        ]);
    }

    // ── NEW ────────────────────────────────────────────────────────
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $destination = new Destination();
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ On lit les fichiers directement depuis $request (pas depuis le form)
            $this->handleImageUploads($request, $destination);

            $em->persist($destination);
            $em->flush();

            $this->addFlash('success', 'Destination « ' . $destination->getNom() . ' » créée avec succès !');
            return $this->redirectToRoute('admin_destination_show', ['id' => $destination->getId()]);
        }

        return $this->render('destination/form.html.twig', [
            'form'        => $form->createView(),
            'destination' => $destination,
            'mode'        => 'new',
        ]);
    }

    // ── SHOW ───────────────────────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Destination $destination, QRCodeService $qrCodeService): Response
    {
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

    // ── EDIT ───────────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Destination $destination, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ On lit les fichiers directement depuis $request (pas depuis le form)
            $this->handleImageUploads($request, $destination);

            $em->flush();

            $this->addFlash('success', 'Destination mise à jour avec succès !');
            return $this->redirectToRoute('destination_show', ['id' => $destination->getId()]);
        }

        return $this->render('destination/form.html.twig', [
            'form'        => $form->createView(),
            'destination' => $destination,
            'mode'        => 'edit',
        ]);
    }

    // ── DELETE ─────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Destination $destination, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_destination_' . $destination->getId(), $request->request->get('_token'))) {
            $em->remove($destination);
            $em->flush();
            $this->addFlash('success', 'Destination supprimée.');
        }

        return $this->redirectToRoute('destination_index');
    }

    // ── YOUTUBE SEARCH ─────────────────────────────────────────────
    #[Route('/api/youtube-search', name: 'youtube_search', methods: ['GET'])]
    public function youtubeSearch(Request $request, YouTubeService $youtubeService): JsonResponse
    {
        $q = $request->query->get('q', '');
        if (!$q) return $this->json([]);

        return $this->json($youtubeService->searchVideos($q, 6));
    }

    // ── API LIST (JSON) ────────────────────────────────────────────
    #[Route('/api/list', name: 'api_list', methods: ['GET'])]
    public function apiList(DestinationRepository $repo): JsonResponse
    {
        $destinations = $repo->findAll();
        $data = array_map(fn($d) => [
            'id'   => $d->getId(),
            'nom'  => $d->getNom(),
            'pays' => $d->getPays(),
        ], $destinations);

        return $this->json($data);
    }

    // ── PRIVATE : upload images depuis $request directement ────────
    /**
     * ✅ Lit les fichiers depuis $request->files (pas depuis le form Symfony)
     * car form_widget génère un input sans [] qui écrase le tableau.
     * $request->files->get('destination')['imageFiles'] retourne bien
     * un tableau quand le name HTML est "destination[imageFiles][]"
     */
    private function handleImageUploads(Request $request, Destination $destination): void
    {
        // Récupère le tableau de fichiers depuis la requête brute
        $destFiles = $request->files->get('destination');

        if (!$destFiles || !isset($destFiles['imageFiles'])) {
            return;
        }

        $files = $destFiles['imageFiles'];

        // Normalise en tableau (au cas où un seul fichier)
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) continue;
            if (!$file->isValid()) continue;

            // Nom unique pour éviter les collisions
            $filename = uniqid('dest_') . '.' . $file->guessExtension();

            // Déplace dans le dossier uploads
            $file->move($this->imagesDirectory, $filename);

            // Chemin relatif accessible depuis public/
            $relativePath = '/uploads/destinations/' . $filename;

            // AJOUTE à la liste existante (ne remplace pas !)
            $destination->addImage($relativePath);
        }
    }
}