<?php

namespace App\Command\User;

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
    name: 'user:add',
    description: 'Add a user entity with specific data',
)]
class UserAddCommand extends Command
{
    /**
     * @var array
     */
    protected ?array $groups = null;

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
        if (!$this->getGroups()) {
            throw new RuntimeException('To create user to need to create at least one group');
        }

        $io = new SymfonyStyle($input, $output);

        $result = $this->askQuestions($io);

        $response = $this->serverAdapter->save($result, 'users');

        $id = $response['id'] ?? null;

        if (!$id) {
            $io->error('Something went wrong during user creation process');

            return Command::SUCCESS;
        }

        $io->success("You have successfully created a new user (ID: $id)");

        return Command::SUCCESS;
    }

    /**
     * @return array
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function getGroups(): array
    {
        if (!is_null($this->groups)) {
            return $this->groups;
        }

        $response = $this->serverAdapter->getAll('groups');

        $result = [];
        foreach ($response['member'] as $item) {
            $result[$item['@id']] = $item['name'];
        }

        $this->groups = $result;

        return $this->groups;
    }

    /**
     * @param SymfonyStyle $io
     * @return array
     */
    protected function askQuestions(SymfonyStyle $io, array $data = []): array
    {
        $questions = [];

        $questions['name'] = [
            'message' => 'Please, enter a user\'s name',
            'validator' => function (string|null $value) {
                $list = $this->validator->validate($value, new NotBlank());
                if ($list->count()) {
                    throw new RuntimeException($list->get(0)->getMessage());
                }

                return $value;
            },
            'default' => $data['name'] ?? null
        ];

        $questions['email'] = [
            'message' => 'Please, enter a user\'s email',
            'validator' => function (string|null $value) {
                $list = $this->validator->validate($value, [new NotBlank(), new Email()]);
                if ($list->count()) {
                    throw new RuntimeException($list->get(0)->getMessage());
                }

                return $value;
            },
            'default' => $data['email'] ?? null
        ];

        $result = [];
        foreach ($questions as $key => $question) {
            $result[$key] = $io->ask($question['message'], $question['default'], $question['validator']);
        }

        $groups = array_flip($this->getGroups());
        $group = $io->choice('Please, choose a user\'s group', array_keys($groups), $data['usersGroup'] ?? null);

        $result['usersGroup'] = $groups[$group];

        return $result;
    }
}
