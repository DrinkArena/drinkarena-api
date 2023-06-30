<?php

namespace App\Entity;

use App\Repository\GameRoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GameRoomRepository::class)]
class GameRoom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['room:base', 'room:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['room:create'])]
    #[Assert\NotNull(groups: ['room:create'])]
    #[Assert\Length(
        min: 3,
        max: 24,
        minMessage: 'Your username must be at least {{ limit }} characters long',
        maxMessage: 'Your username cannot be longer than {{ limit }} characters',
        groups: ['room:create']
    )]
    #[Groups(['room:create', 'room:base', 'room:detail'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'ownedRooms')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['room:base', 'room:detail'])]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Choice(choices: ['WAITING_PLAYER', 'STARTED', 'FINISHED'])]
    #[Groups(['room:base', 'room:detail'])]
    private ?string $state = null;

    #[ORM\Column]
    #[Groups(['room:base', 'room:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'playedRooms')]
    #[Groups(['room:detail'])]
    private Collection $participants;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: PlayedPledge::class)]
    private Collection $playedPledges;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->state = 'WAITING_PLAYER';
        $this->participants = new ArrayCollection();
        $this->playedPledges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

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

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(User $participant): self
    {
        $this->participants->removeElement($participant);

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
            $playedPledge->setRoom($this);
        }

        return $this;
    }

    public function removePlayedPledge(PlayedPledge $playedPledge): self
    {
        if ($this->playedPledges->removeElement($playedPledge)) {
            // set the owning side to null (unless already changed)
            if ($playedPledge->getRoom() === $this) {
                $playedPledge->setRoom(null);
            }
        }

        return $this;
    }
}
