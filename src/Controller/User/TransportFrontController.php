<?php

namespace App\Controller\User;

use App\Entity\Reservation;
use App\Entity\Transport;
use App\Form\ReservationFormType;
use App\Repository\TransportRepository;
use App\Repository\TransportTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\WeatherApiService;

#[Route('/transports', name: 'transport_')]
class TransportFrontController extends AbstractController
{
    // ── BROWSE ALL TRANSPORTS ────────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, TransportRepository $repo, TransportTypeRepository $typeRepo, WeatherApiService $weatherApi, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $type  = $request->query->get('type', '');
        $q     = $request->query->get('q', '');
        $sort  = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'ASC');

        $queryBuilder = $repo->createQueryBuilder('t');

        if ($type) {
            $queryBuilder->innerJoin('t.transportType', 'tt')
                         ->where('tt.nom = :type')
                         ->setParameter('type', $type);
        } elseif ($q) {
            $queryBuilder->where('t.compagnie LIKE :q OR t.numero LIKE :q')
                         ->setParameter('q', '%'.$q.'%');
        }

        $sortField = in_array($sort, ['id', 'compagnie', 'numero', 'capacite', 'prix']) ? $sort : 'id';
        $orderDir = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $queryBuilder->orderBy('t.' . $sortField, $orderDir);

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            6 // 6 transports par page
        );

        // Inject live weather for transport advice (API B)
        $weather = $weatherApi->getTravelAdvice();

        return $this->render('FrontOffice/transport/index.html.twig', [
            'pagination' => $pagination,
            'types'      => $typeRepo->findAll(),
            'activeType' => $type,
            'q'          => $q,
            'sort'       => $sort,
            'order'      => $order,
            'weather'    => $weather,
        ]);
    }

    // ── SHOW DETAIL + RESERVATION FORM ───────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(Request $request, Transport $transport, EntityManagerInterface $em, \Symfony\Component\Mailer\MailerInterface $mailer): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationFormType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $demandePlaces = $reservation->getNombrePlaces();
            $placesRestantes = $transport->getPlacesDisponibles();

            if ($demandePlaces > $placesRestantes) {
                $this->addFlash('danger', "Erreur : il ne reste que $placesRestantes place(s) disponible(s) sur ce véhicule.");
                return $this->redirectToRoute('transport_show', ['id' => $transport->getId()]);
            }

            $reservation->setTransport($transport);
            $reservation->setDateReservation(new \DateTime());
            $em->persist($reservation);
            $em->flush();

            // ── Envoi de l'Email de Confirmation ──
            if ($reservation->getEmailClient()) {
                $email = (new \Symfony\Component\Mime\Email())
                    ->from('marambousteni37@gmail.com')
                    ->to($reservation->getEmailClient())
                    ->subject('Votre réservation est en attente - SmartTrip')
                    ->html(sprintf(
                        '<div style="margin:0;padding:0;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;background-color:#f1f5f9;padding:50px 20px;">
                            <center>
                                <div style="max-width:600px;background-color:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 15px 35px rgba(0,0,0,0.1);border:1px solid #e2e8f0;">
                                    <!-- Header -->
                                    <div style="background-color:#0d1b2a;padding:35px 0;text-align:center;">
                                        <h1 style="color:#ffffff;margin:0;font-size:28px;letter-spacing:2px;text-transform:uppercase;font-weight:800;">Smart<span style="color:#c9a84c;">Trip</span></h1>
                                        <div style="width:50px;height:3px;background:#c9a84c;margin:15px auto 0;"></div>
                                    </div>
                                    
                                    <!-- Content -->
                                    <div style="padding:45px 40px;text-align:left;">
                                        <div style="display:inline-block;padding:8px 15px;background:#fff8e1;color:#b45309;border-radius:8px;font-size:12px;font-weight:700;margin-bottom:20px;text-transform:uppercase;letter-spacing:1px;">Demande Reçue</div>
                                        
                                        <h2 style="color:#0d1b2a;font-size:24px;margin-bottom:15px;font-weight:700;">Bonjour %s,</h2>
                                        <p style="color:#475569;font-size:16px;line-height:1.6;margin-bottom:30px;">
                                            Nous avons bien reçu votre demande de réservation. Nos équipes vérifient actuellement les disponibilités pour vous offrir le meilleur service possible.
                                        </p>

                                        <!-- Summary Box -->
                                        <div style="background-color:#f8fafc;border-radius:16px;padding:25px;border:1px solid #edf2f7;">
                                            <h3 style="color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:2px;margin:0 0 20px 0;font-weight:800;">Récapitulatif de la demande</h3>
                                            
                                            <div style="margin-bottom:12px;display:flex;justify-content:space-between;">
                                                <span style="color:#1e293b;font-size:14px;font-weight:600;">%s</span>
                                                <span style="color:#64748b;font-size:14px;">Véhicule</span>
                                            </div>
                                            <div style="margin-bottom:12px;display:flex;justify-content:space-between;">
                                                <span style="color:#1e293b;font-size:14px;font-weight:600;">%d place(s)</span>
                                                <span style="color:#64748b;font-size:14px;">Capacité</span>
                                            </div>
                                            <div style="padding-top:12px;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:baseline;">
                                                <span style="color:#0d1b2a;font-size:20px;font-weight:800;">%.2f DT</span>
                                                <span style="color:#64748b;font-size:14px;">Montant Total</span>
                                            </div>
                                        </div>

                                        <div style="margin-top:35px;background:#e0f2fe;padding:20px;border-radius:12px;border-left:4px solid #3b82f6;">
                                            <p style="margin:0;color:#0369a1;font-size:14px;font-weight:600;">
                                                🔎 Un administrateur va valider votre demande dans les plus brefs délais. Vous recevrez un e-mail de confirmation finale très bientôt.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Footer -->
                                    <div style="background-color:#f8fafc;padding:25px;text-align:center;border-top:1px solid #f1f5f9;">
                                        <p style="margin:0;font-size:12px;color:#94a3b8;">
                                            Ceci est une notification automatique de SmartTrip.<br>
                                            Merci de ne pas répondre directement à cet e-mail.
                                        </p>
                                    </div>
                                </div>
                            </center>
                        </div>',
                        htmlspecialchars($reservation->getNomClient() ?? 'Voyageur'),
                        htmlspecialchars($transport->getCompagnie()),
                        $reservation->getNombrePlaces(),
                        $reservation->getMontantTotal()
                    ));
                try {
                    $mailer->send($email);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'La réservation est enregistrée, mais l\'email n\'a pas pu être envoyé.');
                }
            }

            $this->addFlash('success', sprintf(
                'Réservation confirmée pour %d place(s) sur %s ! Total : %.2f DT',
                $reservation->getNombrePlaces(),
                $transport->getCompagnie(),
                $reservation->getMontantTotal()
            ));

            return $this->redirectToRoute('mes_reservations_index', [
                'email' => $reservation->getEmailClient(),
            ]);
        }

        return $this->render('FrontOffice/transport/show.html.twig', [
            'transport' => $transport,
            'form'      => $form->createView(),
        ]);
    }
}
