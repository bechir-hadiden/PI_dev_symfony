<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservations', name: 'admin_reservation_')]
class ReservationAdminController extends AbstractController
{
    // ── INDEX ───────────────────────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $repo, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $query = $repo->createQueryBuilder('r')
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('BackOffice/reservation/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    // ── SHOW ────────────────────────────────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('BackOffice/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    // ── UPDATE STATUT ───────────────────────────────────────────────────────
    #[Route('/{id}/statut', name: 'update_statut', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatut(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $em,
        \Symfony\Component\Mailer\MailerInterface $mailer
    ): Response {
        $allowed = [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_CANCELLED,
            Reservation::STATUS_COMPLETED,
        ];

        $status = $request->request->get('status');

        if (in_array($status, $allowed, true)) {
            $reservation->setStatus($status);
            $em->flush();
            $this->addFlash('success', 'Statut mis à jour : ' . $reservation->getStatusLabel());

            if ($status === Reservation::STATUS_CONFIRMED && $reservation->getEmail()) {
                $email = (new \Symfony\Component\Mime\Email())
                    ->from('marambousteni37@gmail.com')
                    ->to($reservation->getEmail())
                    ->subject('Bonne nouvelle ! Votre réservation est CONFIRMÉE !')
                    ->html(sprintf(
                        '<div style="margin:0;padding:0;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;background-color:#0d1b2a;padding:50px 20px;">
                            <center>
                                <div style="max-width:600px;background-color:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,0.4);border:1px solid rgba(201,168,76,0.2);">
                                    <div style="background:linear-gradient(45deg,#0d1b2a 0%%,#1a2f4a 100%%);padding:40px 0;text-align:center;border-bottom:5px solid #c9a84c;">
                                        <h1 style="color:#ffffff;margin:0;font-size:32px;letter-spacing:2px;text-transform:uppercase;font-weight:800;">Smart<span style="color:#c9a84c;">Trip</span></h1>
                                        <p style="color:#c9a84c;margin-top:5px;font-size:14px;letter-spacing:3px;text-transform:uppercase;">Luxury & Efficiency</p>
                                    </div>
                                    <div style="padding:50px 40px;text-align:left;">
                                        <h2 style="color:#0d1b2a;font-size:28px;margin-bottom:20px;font-weight:700;">Félicitations, %s !</h2>
                                        <p style="color:#4a5568;font-size:17px;line-height:1.7;margin-bottom:30px;">
                                            Votre voyage avec <strong>SmartTrip</strong> commence ici. Nous sommes ravis de vous confirmer que votre réservation a été validée avec succès.
                                        </p>
                                        <div style="background-color:#f8fafc;border:2px solid #edf2f7;border-radius:20px;padding:30px;position:relative;">
                                            <div style="position:absolute;top:-10px;left:30px;background:#c9a84c;color:#0d1b2a;padding:4px 15px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;">Réservation Confirmée</div>
                                            <div style="margin-bottom:25px;border-bottom:1px dashed #cbd5e0;padding-bottom:15px;">
                                                <div style="font-size:13px;color:#718096;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Destination</div>
                                                <div style="font-size:18px;color:#0d1b2a;font-weight:700;">%s</div>
                                            </div>
                                            <div style="display:table;width:100%%;">
                                                <div style="display:table-cell;width:50%%;">
                                                    <p style="margin:0;font-size:12px;color:#a0aec0;text-transform:uppercase;letter-spacing:1px;">Vol</p>
                                                    <p style="margin:5px 0 0;font-size:20px;color:#0d1b2a;font-weight:700;">%s</p>
                                                </div>
                                                <div style="display:table-cell;width:50%%;text-align:right;">
                                                    <p style="margin:0;font-size:12px;color:#a0aec0;text-transform:uppercase;letter-spacing:1px;">Passagers</p>
                                                    <p style="margin:5px 0 0;font-size:18px;color:#bc952e;font-weight:700;">%d</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="margin-top:40px;text-align:center;">
                                            <p style="color:#718096;font-size:15px;font-style:italic;">Merci de présenter cet email lors de votre départ.</p>
                                            <div style="margin-top:30px;padding:15px 30px;background:#c9a84c;color:#0d1b2a;display:inline-block;border-radius:12px;font-weight:800;text-transform:uppercase;letter-spacing:1px;">Bon voyage !</div>
                                        </div>
                                    </div>
                                    <div style="background-color:#f1f5f9;padding:30px;text-align:center;">
                                        <p style="margin:0;font-size:13px;color:#94a3b8;line-height:1.6;">
                                            &copy; 2026 SmartTrip Excellence. Tous droits réservés.<br>
                                            Besoin d\'aide ? Contactez notre support 24/7.
                                        </p>
                                    </div>
                                </div>
                            </center>
                        </div>',
                        htmlspecialchars($reservation->getNomClient() ?? 'Voyageur'),
                        htmlspecialchars($reservation->getDestination() ?? 'N/A'),
                        htmlspecialchars($reservation->getFlightNumber() ?? 'N/A'),
                        $reservation->getNumberOfPassengers()
                    ));

                try {
                    $mailer->send($email);
                    $this->addFlash('success', 'L\'e-mail de confirmation a été envoyé au client.');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'L\'e-mail automatique n\'a pas pu être envoyé.');
                }
            }
        }

        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
    }

    // ── DELETE ──────────────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_reservation_' . $reservation->getId(), $request->request->get('_token'))) {
            $em->remove($reservation);
            $em->flush();
            $this->addFlash('success', 'Réservation supprimée.');
        }

        return $this->redirectToRoute('admin_reservation_index');
    }
}