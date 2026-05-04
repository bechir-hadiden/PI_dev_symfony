<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\Transport;
use App\Entity\TransportType;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

// 1. Clear existing data
echo "Nettoyage de la base de données...\n";
$connection = $em->getConnection();
$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
$connection->executeStatement('TRUNCATE TABLE transport');
$connection->executeStatement('TRUNCATE TABLE transport_type');
$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

// 2. Define Transport Types
$typesData = [
    ['nom' => 'Avion', 'prix' => 150.0, 'image' => 'airplane.jpg'],
    ['nom' => 'Bus', 'prix' => 15.0, 'image' => 'bus.jpg'],
    ['nom' => 'Train', 'prix' => 45.0, 'image' => 'train.jpg'],
    ['nom' => 'Covoiturage', 'prix' => 10.0, 'image' => 'car.jpg'],
];

$typesIdentities = [];

foreach ($typesData as $data) {
    $type = new TransportType();
    $type->setNom($data['nom']);
    $type->setPrixDepart($data['prix']);
    $type->setImage($data['image']);
    $em->persist($type);
    $typesIdentities[$data['nom']] = $type;
}

// 3. Define Transports
$transportsData = [
    [
        'compagnie' => 'Tunisair',
        'numero' => 'TU720',
        'capacite' => 180,
        'prix' => 350.0,
        'type' => 'Avion',
        'desc' => 'Vol direct confortable vers Paris.'
    ],
    [
        'compagnie' => 'Nouvelair',
        'numero' => 'BJ512',
        'capacite' => 160,
        'prix' => 300.0,
        'type' => 'Avion',
        'desc' => 'Compagnie privée offrant des tarifs compétitifs.'
    ],
    [
        'compagnie' => 'SNTRI',
        'numero' => 'EXP-88',
        'capacite' => 50,
        'prix' => 25.0,
        'type' => 'Bus',
        'desc' => 'Bus national grand confort.'
    ],
    [
        'compagnie' => 'FlixBus',
        'numero' => 'FL-404',
        'capacite' => 55,
        'prix' => 20.0,
        'type' => 'Bus',
        'desc' => 'Transporteur européen moderne avec Wi-Fi.'
    ],
    [
        'compagnie' => 'SNCFT',
        'numero' => 'TR-X',
        'capacite' => 200,
        'prix' => 45.0,
        'type' => 'Train',
        'desc' => 'Train express climatisé.'
    ],
    [
        'compagnie' => 'Alstom Express',
        'numero' => 'AX-900',
        'capacite' => 150,
        'prix' => 55.0,
        'type' => 'Train',
        'desc' => 'Service rapide et haut de gamme.'
    ],
];

foreach ($transportsData as $data) {
    if (isset($typesIdentities[$data['type']])) {
        $transport = new Transport();
        $transport->setCompagnie($data['compagnie']);
        $transport->setNumero($data['numero']);
        $transport->setCapacite($data['capacite']);
        $transport->setPrix($data['prix']);
        $transport->setDescription($data['desc']);
        $transport->setTransportType($typesIdentities[$data['type']]);
        $transport->setImageUrl($typesIdentities[$data['type']]->getImage()); // Use type image as default
        $em->persist($transport);
    }
}

$em->flush();

echo "Base de données transport remplie avec succès !\n";
echo "Types créés : " . count($typesData) . "\n";
echo "Transports créés : " . count($transportsData) . "\n";
