<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:test-mail')]
class TestMailCommand extends Command
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Test d\'envoi d\'email...</info>');
        
        try {
            $email = (new Email())
                ->from('wathekzidi68@gmail.com')
                ->to('wathekzidi68@gmail.com') // Changez si besoin
                ->subject('Test Symfony Mailer')
                ->text('Ceci est un test de configuration mailer')
                ->html('<h1>Test</h1><p>Ceci est un test HTML</p>');

            $this->mailer->send($email);
            
            $output->writeln('<info>✅ Email envoyé avec succès !</info>');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $output->writeln('<error>❌ Erreur: ' . $e->getMessage() . '</error>');
            $output->writeln('<comment>Détails: ' . get_class($e) . '</comment>');
            return Command::FAILURE;
        }
    }
}