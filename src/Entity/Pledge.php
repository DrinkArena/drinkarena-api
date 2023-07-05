<?php

namespace App\Entity;

use App\Repository\PledgeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PledgeRepository::class)]
class Pledge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pledge:base', 'pledge:detail', 'room:current-pledge'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['pledge:base', 'pledge:detail', 'pledge:create', 'room:current-pledge'])]
    private ?string $title = null;

    #[ORM\Column(length: 50)]
    #[Groups(['pledge:base', 'pledge:detail', 'room:current-pledge'])]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'pledges')]
    #[Groups(['pledge:detail', 'room:current-pledge'])]
    private ?User $owner = null;

    #[ORM\Column]
    #[Groups(['pledge:base', 'pledge:detail', 'room:current-pledge'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'pledge', targetEntity: PlayedPledge::class)]
    private Collection $playedPledges;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->type = 'ACTION';
        $this->playedPledges = new ArrayCollection();
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

    /**
     * @return Collection<int, PlayedPledge>
     */
    public function getPlayedPledges(): Collection
    {
        return $this->playedPledges;
    }

    public function addPlayedPledge(PlayedPledge $playedPledge): self
    {
        if (!$this->playedPledges->contains($playedPledge)) {
            $this->playedPledges->add($playedPledge);
            $playedPledge->setPledge($this);
        }

        return $this;
    }

    public function removePlayedPledge(PlayedPledge $playedPledge): self
    {
        if ($this->playedPledges->removeElement($playedPledge)) {
            // set the owning side to null (unless already changed)
            if ($playedPledge->getPledge() === $this) {
                $playedPledge->setPledge(null);
            }
        }

        return $this;
    }
}
