<?php
require 'vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$user = new App\Entity\User();
$user->setEmail('test@smarttrip.com');
$user->setPassword('password');
$user->setRoles(['ROLE_USER']);
$user->setWalletBalance(500);

$em->persist($user);
$em->flush();
echo "User created";
