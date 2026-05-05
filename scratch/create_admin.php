<?php

use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use App\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$passwordHasher = $container->get('security.user_password_hasher');

$email = 'admin@smarttrip.tn';
$password = 'admin123';

$user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

if (!$user) {
    $user = new User();
    $user->setEmail($email);
    $user->setRoles(['ROLE_ADMIN']);
    $user->setName('Administrateur');
    $user->setWalletBalance(1000.0);
    $user->setLoyaltyPoints(500);
    
    $hashedPassword = $passwordHasher->hashPassword($user, $password);
    $user->setPassword($hashedPassword);
    
    $entityManager->persist($user);
    $entityManager->flush();
    
    echo "Compte Admin créé avec succès !\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
} else {
    // Update existing user to be admin just in case
    $user->setRoles(['ROLE_ADMIN']);
    $hashedPassword = $passwordHasher->hashPassword($user, $password);
    $user->setPassword($hashedPassword);
    $entityManager->flush();
    echo "Compte existant mis à jour en Admin.\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
}
