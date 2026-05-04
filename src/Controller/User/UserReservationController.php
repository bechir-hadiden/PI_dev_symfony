<?php

namespace App\Controller\User;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/mes-reservations', name: 'mes_reservations_')]
class UserReservationController extends AbstractController
{
    // ── LIST (filtered by email) ─────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $repo, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $email = $request->query->get('email', '');
        
        $queryBuilder = $repo->createQueryBuilder('r')
            ->where('r.emailClient = :email')
            ->setParameter('email', $email)
            ->orderBy('r.dateReservation', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            5 // 5 réservations par page
        );

        return $this->render('FrontOffice/reservation/index.html.twig', [
            'pagination' => $pagination,
            'email'        => $email,
        ]);
    }

    // ── CANCEL ──────────────────────────────────────────────────────────────
    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        $email = $request->request->get('email', '');

        if ($this->isCsrfTokenValid('cancel_reservation_' . $reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatut(Reservation::STATUT_ANNULEE);
            $em->flush();
            $this->addFlash('success', 'Votre réservation a été annulée.');
        }

        return $this->redirectToRoute('mes_reservations_index', ['email' => $email]);
    }

    // ── DOWNLOAD PDF TICKET ──────────────────────────────────────────────────
    #[Route('/{id}/ticket', name: 'download_ticket', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadTicket(Reservation $reservation): Response
    {
        // Security check: cannot print cancelled ticket
        if ($reservation->getStatut() === Reservation::STATUT_ANNULEE) {
            $this->addFlash('error', 'Impossible de télécharger un billet annulé.');
            return $this->redirectToRoute('mes_reservations_index', ['email' => $reservation->getEmailClient()]);
        }

        $html = $this->renderView('FrontOffice/reservation/ticket_pdf.html.twig', [
            'reservation' => $reservation
        ]);

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Billet_' . str_replace(' ', '_', $reservation->getTransport()->getCompagnie()) . '_' . $reservation->getId() . '.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}
