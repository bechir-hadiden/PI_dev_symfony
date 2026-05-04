<?php

namespace App\Controller\Admin;

use App\Entity\TransportType;
use App\Form\TransportTypeFormType;
use App\Repository\TransportTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/transport-types', name: 'admin_transport_type_')]
class TransportTypeController extends AbstractController
{
    private string $uploadDir;

    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../../../public/uploads/transport-types/';
    }

    // ── INDEX ───────────────────────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, TransportTypeRepository $repo, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $query = $repo->createQueryBuilder('tt')
            ->orderBy('tt.id', 'ASC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('BackOffice/transport_type/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    // ── NEW ─────────────────────────────────────────────────────────────────
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $type = new TransportType();
        $form = $this->createForm(TransportTypeFormType::class, $type);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $type->setImage($this->uploadImage($imageFile, $slugger));
            }

            $em->persist($type);
            $em->flush();
            $this->addFlash('success', 'Type « ' . $type->getNom() . ' » créé avec succès !');
            return $this->redirectToRoute('admin_transport_type_index');
        }

        return $this->render('BackOffice/transport_type/form.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
            'mode' => 'new',
        ]);
    }

    // ── EDIT ────────────────────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, TransportType $type, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(TransportTypeFormType::class, $type);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $type->setImage($this->uploadImage($imageFile, $slugger));
            }

            $em->flush();
            $this->addFlash('success', 'Type mis à jour avec succès !');
            return $this->redirectToRoute('admin_transport_type_index');
        }

        return $this->render('BackOffice/transport_type/form.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
            'mode' => 'edit',
        ]);
    }

    // ── DELETE ──────────────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, TransportType $type, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_transport_type_' . $type->getId(), $request->request->get('_token'))) {
            $em->remove($type);
            $em->flush();
            $this->addFlash('success', 'Type supprimé.');
        }
        return $this->redirectToRoute('admin_transport_type_index');
    }

    // ── PRIVATE ─────────────────────────────────────────────────────────────
    private function uploadImage(UploadedFile $file, SluggerInterface $slugger): string
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename  = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        $file->move($this->uploadDir, $newFilename);
        return '/uploads/transport-types/' . $newFilename;
    }
}
