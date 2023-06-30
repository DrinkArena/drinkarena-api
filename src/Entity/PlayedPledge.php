<?php

namespace App\Entity;

use App\Repository\PlayedPledgeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayedPledgeRepository::class)]
class PlayedPledge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'playedPledges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GameRoom $room = null;

    #[ORM\ManyToOne(inversedBy: 'playedPledges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pledge $pledge = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): ?GameRoom
    {
        return $this->room;
    }

    public function setRoom(?GameRoom $room): self
    {
        $this->room = $room;

        return $this;
    }

    public function getPledge(): ?Pledge
    {
        return $this->pledge;
    }

    public function setPledge(?Pledge $pledge): self
    {
        $this->pledge = $pledge;

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
}
