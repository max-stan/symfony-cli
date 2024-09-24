<?php

namespace App\Command\User;

use App\Adapter\ServerAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

#[AsCommand(
    name: 'user:remove',
    description: 'Remove user entity',
)]
class UserRemoveCommand extends Command
{
    public function __construct(
        protected ServerAdapter $serverAdapter,
        protected DecoderInterface $decoder,
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
        $response = $this->serverAdapter->delete($id, 'users');

        $io->success("You have successfully deleted a user (ID: $id)");

        return Command::SUCCESS;
    }
}
