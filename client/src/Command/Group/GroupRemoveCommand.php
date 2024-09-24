<?php

namespace App\Command\Group;

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
    name: 'group:remove',
    description: 'Remove group entity',
)]
class GroupRemoveCommand extends Command
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

        $groups = $this->serverAdapter->getAll('groups');

        $result = [];
        foreach ($groups['member'] as $group) {
            $result[$group['name']] = $group['id'];
        }

        $onlyNames = array_keys($result);

        $question = new Question('Please, enter a group\'s name');
        $question->setAutocompleterValues($onlyNames)
            ->setValidator(function (string|null $value) use ($onlyNames) {
                if (!$value) {
                    throw new RuntimeException('The group\'s name is invalid.');
                }

                return $value;
            });

        $userEmail = $io->askQuestion($question);

        $id = (int)$result[$userEmail];
        $this->serverAdapter->delete($id, 'groups');

        $io->success("You have successfully deleted a group (ID: $id)");

        return Command::SUCCESS;
    }
}
