<?php

namespace App\Controller;

use App\Entity\Voyage;
use App\Form\VoyageType;
use App\Repository\DestinationRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/voyages', name: 'voyage_')]
class VoyageController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, VoyageRepository $repo, DestinationRepository $destRepo): Response
    {
        $q             = $request->query->get('q');
        $destinationId = $request->query->getInt('destination') ?: null;

        if ($q) {
            $voyages = $repo->search($q);
        } else {
            $voyages = $repo->findDisponibles($destinationId);
        }

        return $this->render('voyage/index.html.twig', [
            'voyages'      => $voyages,
            'destinations' => $destRepo->findAll(),
            'query'        => $q,
            'filtreDestId' => $destinationId,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $voyage = new Voyage();
        $form   = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($voyage);
            $em->flush();
            $this->addFlash('success', '✅ Voyage « ' . $voyage->getTitre() . ' » créé avec succès !');
            return $this->redirectToRoute('voyage_index');
        }

        return $this->render('voyage/form.html.twig', [
            'form'   => $form->createView(),
            'voyage' => $voyage,
            'mode'   => 'new',
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Voyage $voyage): Response
    {
        return $this->render('voyage/show.html.twig', [
            'voyage' => $voyage,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Voyage $voyage, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '✅ Voyage mis à jour !');
            return $this->redirectToRoute('voyage_index');
        }

        return $this->render('voyage/form.html.twig', [
            'form'   => $form->createView(),
            'voyage' => $voyage,
            'mode'   => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Voyage $voyage, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_voyage_' . $voyage->getId(), $request->request->get('_token'))) {
            $em->remove($voyage);
            $em->flush();
            $this->addFlash('success', '🗑️ Voyage supprimé.');
        }
        return $this->redirectToRoute('voyage_index');
    }
}