<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\User;
use App\Entity\Abonnement;
use App\Entity\Paiement;

// Mock objects for testing relations
$user = new User();
$user->setEmail("test@example.com");

$abonnement = new Abonnement();
$abonnement->setType("Mensuel");
$abonnement->setPrix(29.99);
$abonnement->setStatus("Actif");
$user->addAbonnement($abonnement); // Use helper to update both sides

$paiement = new Paiement();
$paiement->setAmount(29.99);
$paiement->setStatus("Effectué");
$paiement->setAbonnement($abonnement);

echo "Checking User relation:\n";
echo "User has " . count($user->getAbonnements()) . " abonnements.\n";

echo "\nChecking Abonnement relation:\n";
echo "Abonnement type: " . $abonnement->getType() . "\n";
echo "Abonnement user: " . $abonnement->getUser()->getEmail() . "\n";

echo "\nChecking Paiement relation:\n";
echo "Paiement amount: " . $paiement->getAmount() . "\n";
echo "Paiement linked to abonnement type: " . $paiement->getAbonnement()->getType() . "\n";

if ($user->getActiveAbonnement() === $abonnement) {
    echo "\nSUCCESS: Active abonnement correctly retrieved from User.\n";
} else {
    echo "\nFAILURE: Active abonnement not found.\n";
}
