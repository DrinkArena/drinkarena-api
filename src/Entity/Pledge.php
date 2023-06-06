<?php

namespace App\Entity;

use App\Repository\PledgeRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PledgeRepository::class)]
class Pledge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pledge:base', 'pledge:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['pledge:base', 'pledge:detail', 'pledge:create'])]
    private ?string $title = null;

    #[ORM\Column(length: 50)]
    #[Groups(['pledge:base', 'pledge:detail'])]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'pledges')]
    #[Groups(['pledge:detail'])]
    private ?User $owner = null;

    #[ORM\Column]
    #[Groups(['pledge:base', 'pledge:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->type = 'ACTION';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
