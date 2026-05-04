<?php
// /tmp/seed_payments.php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\Paiement;
use App\Entity\Reservation;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$reservations = $em->getRepository(Reservation::class)->findAll();

if (empty($reservations)) {
    echo "Aucune réservation trouvée. Veuillez d'abord créer une réservation.\n";
    exit;
}

foreach ($reservations as $res) {
    if ($res->getStatut() === 'confirmee') {
        $p = new Paiement();
        $p->setReservationId($res->getId());
        $p->setAmount($res->getMontantTotal());
        $p->setStatus('Effectué');
        $p->setMethodePaiement('Stripe');
        $em->persist($p);
    }
}

$em->flush();
echo "Paiements générés avec succès pour les réservations confirmées.\n";
