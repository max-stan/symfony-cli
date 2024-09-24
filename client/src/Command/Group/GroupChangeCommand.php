<?php

namespace App\Command\Group;

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
    name: 'group:change',
    description: 'Change group entity data',
)]
class GroupChangeCommand extends GroupAddCommand
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

        $groups = $this->serverAdapter->getAll('groups');

        $result = [];
        foreach ($groups['member'] as $group) {
            $result[$group['name']] = $group['id'];
        }

        $onlyEmails = array_keys($result);

        $question = new Question('Please, enter a group\'s name');
        $question->setAutocompleterValues($onlyEmails)
            ->setValidator(function (string|null $value) use ($onlyEmails) {
                if (!$value || !in_array($value, $onlyEmails)) {
                    throw new RuntimeException('The group\'s name is invalid.');
                }

                return $value;
            });

        $userEmail = $io->askQuestion($question);

        $id = (int)$result[$userEmail];

        $data = $this->serverAdapter->getById($id, 'groups');

        $result = $this->askQuestions($io, $data);

        $this->serverAdapter->save(
            array_replace($data, $result),
            'groups'
        );

        $io->success("You have successfully saved a group (ID: $id)");

        return Command::SUCCESS;
    }
}
