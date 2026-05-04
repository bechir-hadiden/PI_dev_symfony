<?php

use App\Entity\User;
use App\Kernel;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

require_once __DIR__ . '/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    if ($container->has('test.service_container')) {
        $container = $container->get('test.service_container');
    }
    
    $em = $container->get('doctrine.orm.entity_manager');
    $hasher = $container->get('security.user_password_hasher');
    
    $user = new User();
    $user->setEmail('marambousteni37@gmail.com');
    $user->setName('Admin SmartTrip');
    $user->setRoles(['ROLE_ADMIN']);
    $user->setFaceRegistered(false);
    
    $hashedPassword = $hasher->hashPassword($user, 'admin123'); // Hardcoded password for the user to use
    $user->setPassword($hashedPassword);
    
    $em->persist($user);
    $em->flush();
    
    echo "Admin user created: marambousteni37@gmail.com / admin123\n";
    
    return $kernel;
};
