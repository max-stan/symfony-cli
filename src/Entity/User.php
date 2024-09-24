<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => 'user:item']),
        new GetCollection(normalizationContext: ['groups' => 'user:list']),
        new Post(normalizationContext: ['groups' => 'user:post']),
        new Put(normalizationContext: ['groups' => 'user:put']),
        new Delete(normalizationContext: ['groups' => 'user:delete']),
    ]
)]
class User implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:item', 'user:list', 'user:put', 'user:post', 'user:delete'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:item', 'user:list', 'user:put', 'user:post', 'user:delete'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Email(
        message: 'The email {{ value }} is not a valid email.',
    )]
    #[Groups(['user:item', 'user:list', 'user:put', 'user:post', 'user:delete', 'group:list'])]
    private ?string $email = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(['user:item', 'user:list', 'user:put', 'user:post', 'user:delete'])]
    private ?Group $usersGroup = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
        ];
    }

    public function getUsersGroup(): ?Group
    {
        return $this->usersGroup;
    }

    public function setUsersGroup(?Group $usersGroup): static
    {
        $this->usersGroup = $usersGroup;

        return $this;
    }
}
