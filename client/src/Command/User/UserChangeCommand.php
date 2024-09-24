<?php

namespace App\Command\User;

use App\Command\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

#[AsCommand(
    name: 'user:change',
    description: 'Change user entity data',
)]
class UserChangeCommand extends User\UserAddCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->serverAdapter->getAll('users');

        $result = [];
        foreach ($users['member'] as $user) {
            $result[$user['email']] = $user['id'];
        }

        $onlyEmails = array_keys($result);

        $question = new Question('Please, enter a user\'s email');
        $question->setAutocompleterValues($onlyEmails)
            ->setValidator(function (string|null $value) use ($onlyEmails) {
                if (!$value || !in_array($value, $onlyEmails)) {
                    throw new RuntimeException('The user\'s email address is invalid.');
                }

                return $value;
            });

        $userEmail = $io->askQuestion($question);

        $id = (int)$result[$userEmail];

        $data = $this->serverAdapter->getById($id, 'users');

        $result = $this->askQuestions($io, $data);

        $this->serverAdapter->save(
            array_replace($data, $result),
            'users'
        );

        $io->success("You have successfully saved a user (ID: $id)");

        return Command::SUCCESS;
    }
}
