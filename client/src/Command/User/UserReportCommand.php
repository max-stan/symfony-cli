<?php

namespace App\Command\User;

use App\Adapter\ServerAdapter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'user:report',
    description: 'Display table of the users assigned to specific group',
)]
class UserReportCommand extends Command
{
    public function __construct(
        protected ServerAdapter $serverAdapter,
    ) {
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $groups = $this->serverAdapter->getAll('groups');

        $table = new Table($output);
        $table->setHeaders(['Group', 'Users']);

        $rows = [];

        foreach ($groups['member'] as $group) {
            $users = array_column($group['users'], 'email');

            $rows[] = [$group['name'], implode(', ', $users)];
        }

        $table->setRows($rows)
            ->render();

        return Command::SUCCESS;
    }
}
