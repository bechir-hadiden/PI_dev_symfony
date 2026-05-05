<?php

namespace App\Controller;

use App\Entity\Transport;
use App\Entity\Vehicule;
use App\Repository\TransportRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/transport')]
class AdminTransportController extends AbstractController
{
    #[Route('/', name: 'app_admin_transport_index')]
    public function index(Request $request, TransportRepository $transportRepository, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $q = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'p.id');
        $order = $request->query->get('order', 'ASC');

        $qb = $transportRepository->createQueryBuilder('p');
        
        if ($q) {
            $qb->andWhere('p.trajet LIKE :q OR p.id LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        // Simulation Météo pour le template
        $weather = [
            'status' => 'Success',
            'color' => 'success',
            'message' => 'Conditions optimales pour les départs aujourd\'hui.',
            'temperature' => 24,
            'windspeed' => 12
        ];

        return $this->render('backoffice/transport/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
            'weather' => $weather
        ]);
    }

    #[Route('/vehicule/nouveau', name: 'app_admin_vehicule_new', methods: ['POST'])]
    public function newVehicule(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vehicule = new Vehicule();
        $vehicule->setMarque($request->request->get('marque'));
        $vehicule->setMatricule($request->request->get('matricule'));
        $vehicule->setType($request->request->get('type'));
        $vehicule->setCapacite((int) $request->request->get('capacite'));

        $entityManager->persist($vehicule);
        $entityManager->flush();

        $this->addFlash('success', 'Véhicule ajouté à la flotte.');
        return $this->redirectToRoute('app_admin_transport_index');
    }

    #[Route('/nouveau', name: 'app_admin_transport_new', methods: ['POST'])]
    public function newTransport(Request $request, VehiculeRepository $vehiculeRepository, EntityManagerInterface $entityManager): Response
    {
        $vehicule = $vehiculeRepository->find($request->request->get('vehicule_id'));
        if (!$vehicule) {
            $this->addFlash('error', 'Véhicule non trouvé.');
            return $this->redirectToRoute('app_admin_transport_index');
        }

        $transport = new Transport();
        $transport->setTrajet($request->request->get('trajet'));
        $transport->setPrix((float) $request->request->get('prix'));
        $transport->setDateHeure(new \DateTime($request->request->get('date_heure')));
        $transport->setVehicule($vehicule);

        $entityManager->persist($transport);
        $entityManager->flush();

        $this->addFlash('success', 'Offre de transport créée.');
        return $this->redirectToRoute('app_admin_transport_index');
    }
    #[Route('/{id}/modifier', name: 'app_admin_transport_edit', methods: ['GET', 'POST'])]
    public function edit(Transport $transport, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $transport->setCompagnie($request->request->get('compagnie'));
            $transport->setNumero($request->request->get('numero'));
            $transport->setCapacite((int) $request->request->get('capacite'));
            $transport->setPrix((float) $request->request->get('prix'));
            $transport->setTrajet($request->request->get('trajet'));
            
            $entityManager->flush();
            $this->addFlash('success', 'Véhicule mis à jour.');
            return $this->redirectToRoute('app_admin_transport_index');
        }

        return $this->render('backoffice/transport/edit.html.twig', [
            'transport' => $transport,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_transport_delete', methods: ['POST'])]
    public function delete(Transport $transport, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($transport);
        $entityManager->flush();
        $this->addFlash('success', 'Véhicule supprimé avec succès.');
        return $this->redirectToRoute('app_admin_transport_index');
    }
}
