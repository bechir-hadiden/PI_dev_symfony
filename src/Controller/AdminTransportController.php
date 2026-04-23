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
    public function index(TransportRepository $transportRepository, VehiculeRepository $vehiculeRepository): Response
    {
        return $this->render('backoffice/transport/index.html.twig', [
            'transports' => $transportRepository->findAll(),
            'vehicules' => $vehiculeRepository->findAll(),
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
}
