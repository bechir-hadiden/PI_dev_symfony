<?php

namespace App\Command;

use App\Service\ReservationTimeoutService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-reservations',
    description: 'Annule les réservations de transport non payées après 15 minutes.',
)]
class CleanupReservationsCommand extends Command
{
    private ReservationTimeoutService $timeoutService;

    public function __construct(ReservationTimeoutService $timeoutService)
    {
        parent::__construct();
        $this->timeoutService = $timeoutService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Nettoyage des Réservations Expirées');

        $count = $this->timeoutService->cleanupExpiredReservations();

        if ($count > 0) {
            $io->success(sprintf('%d réservations ont été annulées avec succès.', $count));
        } else {
            $io->info('Aucune réservation expirée trouvée.');
        }

        return Command::SUCCESS;
    }
}
