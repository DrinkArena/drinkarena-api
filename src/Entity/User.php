<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use OpenApi\Attributes as OA;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:base', 'room:base', 'room:detail', 'pledge:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 24, unique: true)]
    #[Groups(['user:base', 'user:register', 'room:base', 'room:detail', 'pledge:detail'])]
    #[Assert\Length(
        min: 2,
        max: 24,
        minMessage: 'Your username must be at least {{ limit }} characters long',
        maxMessage: 'Your username cannot be longer than {{ limit }} characters',
    )]
    private ?string $username = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:base'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))]
    private array $roles = [];

    #[ORM\Column]
    #[Groups(['user:register', 'user:recover-password'])]
    private ?string $password = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    #[Groups(['user:base', 'room:base', 'room:detail', 'pledge:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:base', 'user:register', 'user:recover-password'])]
    #[Assert\Email(message: 'The email {{ value }} is not valid.')]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private ?string $email = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['user:recover-password'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private ?string $recoveryCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $recoveryCodeExpiration = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: GameRoom::class)]
    private Collection $ownedRooms;

    #[ORM\ManyToMany(targetEntity: GameRoom::class, mappedBy: 'participants')]
    private Collection $playedRooms;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Pledge::class)]
    private Collection $pledges;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->ownedRooms = new ArrayCollection();
        $this->playedRooms = new ArrayCollection();
        $this->pledges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getRecoveryCode(): ?string
    {
        return $this->recoveryCode;
    }

    public function setRecoveryCode(?string $recoveryCode): self
    {
        $this->recoveryCode = $recoveryCode;

        return $this;
    }

    public function getRecoveryCodeExpiration(): ?\DateTimeImmutable
    {
        return $this->recoveryCodeExpiration;
    }

    public function setRecoveryCodeExpiration(?\DateTimeImmutable $recoveryCodeExpiration): self
    {
        $this->recoveryCodeExpiration = $recoveryCodeExpiration;

        return $this;
    }

    /**
     * @return Collection<int, GameRoom>
     */
    public function getOwnedRooms(): Collection
    {
        return $this->ownedRooms;
    }

    public function addOwnedRoom(GameRoom $gameRoom): self
    {
        if (!$this->ownedRooms->contains($gameRoom)) {
            $this->ownedRooms->add($gameRoom);
            $gameRoom->setOwner($this);
        }

        return $this;
    }

    public function removeOwnedRoom(GameRoom $gameRoom): self
    {
        if ($this->ownedRooms->removeElement($gameRoom)) {
            // set the owning side to null (unless already changed)
            if ($gameRoom->getOwner() === $this) {
                $gameRoom->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GameRoom>
     */
    public function getPlayedRooms(): Collection
    {
        return $this->playedRooms;
    }

    public function addPlayedRoom(GameRoom $playedRoom): self
    {
        if (!$this->playedRooms->contains($playedRoom)) {
            $this->playedRooms->add($playedRoom);
            $playedRoom->addParticipant($this);
        }

        return $this;
    }

    public function removePlayedRoom(GameRoom $playedRoom): self
    {
        if ($this->playedRooms->removeElement($playedRoom)) {
            $playedRoom->removeParticipant($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Pledge>
     */
    public function getPledges(): Collection
    {
        return $this->pledges;
    }

    public function addPledge(Pledge $pledge): self
    {
        if (!$this->pledges->contains($pledge)) {
            $this->pledges->add($pledge);
            $pledge->setOwner($this);
        }

        return $this;
    }

    public function removePledge(Pledge $pledge): self
    {
        if ($this->pledges->removeElement($pledge)) {
            // set the owning side to null (unless already changed)
            if ($pledge->getOwner() === $this) {
                $pledge->setOwner(null);
            }
        }

        return $this;
    }
}
