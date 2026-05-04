<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Add a short description for your command',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $user = new User();
        $user->setEmail('marambousteni37@gmail.com');
        $user->setName('Admin SmartTrip');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setFaceRegistered(false);
        
        $hashedPassword = $this->hasher->hashPassword($user, 'admin123');
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Admin user created successfully: marambousteni37@gmail.com / admin123');

        return Command::SUCCESS;
    }
}
