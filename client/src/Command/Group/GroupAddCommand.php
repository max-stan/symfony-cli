<?php

namespace App\Command\Group;

use App\Adapter\ServerAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

#[AsCommand(
    name: 'group:add',
    description: 'Add a group entity with specific data',
)]
class GroupAddCommand extends Command
{
    /**
     * @param ServerAdapter $serverAdapter
     * @param ValidatorInterface $validator
     * @param string|null $name
     */
    public function __construct(
        protected ServerAdapter $serverAdapter,
        protected ValidatorInterface $validator,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

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

        $result = $this->askQuestions($io);

        $response = $this->serverAdapter->save($result, 'groups');

        $id = $response['id'];

        $io->success("You have successfully created a new group (ID: $id)");

        return Command::SUCCESS;
    }

    /**
     * @param SymfonyStyle $io
     * @param array $data
     * @return array
     */
    protected function askQuestions(SymfonyStyle $io, array $data = []): array
    {
        $questions = [];

        $questions['name'] = [
            'message' => 'Please, enter a group\'s name',
            'validator' => function (string|null $value) {
                $list = $this->validator->validate($value, new NotBlank());
                if ($list->count()) {
                    throw new RuntimeException($list->get(0)->getMessage());
                }

                return $value;
            },
            'default' => $data['name'] ?? null
        ];

        $result = [];
        foreach ($questions as $key => $question) {
            $result[$key] = $io->ask($question['message'], $question['default'], $question['validator']);
        }

        return $result;
    }
}
