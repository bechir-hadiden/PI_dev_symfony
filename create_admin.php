<?php
require 'vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__).'/PI_dev_symfony/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$admin = new User();
$admin->setEmail('marambousteni37@gmail.com');
$admin->setRoles(['ROLE_ADMIN']);
$admin->setName('Admin');
$admin->setFaceRegistered(false);
$admin->setWalletBalance(10000);
$admin->setLoyaltyPoints(500);
$admin->setPays('Tunisie');
$admin->setEstBloque(false);
$admin->setPassword('$2y$13$y0hK2QgvlNAJsfINsvFGBeg26gxxtRfBUqr2ybwGM2psLOra0Tu9i');

$em->persist($admin);
$em->flush();

echo "Admin user created successfully: admin@smarttrip.com / admin123\n";
