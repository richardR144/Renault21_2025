<?php

namespace App\Entity;

use App\Entity\Piece;
use App\Entity\Message;
use App\Entity\Annonce;
use App\Entity\Image;
use App\Entity\Description;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\Table(name: 'user')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $pseudo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender')]
    private Collection $joinColumn;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'receiver')]
    private Collection $isRead;

    /**
     * @var Collection<int, Piece>
     */
    #[ORM\OneToMany(targetEntity: Piece::class, mappedBy: 'user')]
    private Collection $pieces;

    public function __construct()
    {
        $this->joinColumn = new ArrayCollection();
        $this->isRead = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
        $this->pieces = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->isActive = true;
    }

    public function createUser(string $pseudo, string $email, string $passwordHashed, string $role = 'ROLE_USER'): void
    {
        $this->pseudo = $pseudo;
        $this->email = $email;
        $this->password = $passwordHashed; // Peut être null pour Google
        $this->roles = is_array($role) ? $role : [$role];
    }



    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
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

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPieces(): Collection
    {
        return $this->pieces;
    }


    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }


    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getJoinColumn(): Collection
    {
        return $this->joinColumn;
    }

    public function addJoinColumn(Message $joinColumn): static
    {
        if (!$this->joinColumn->contains($joinColumn)) {
            $this->joinColumn->add($joinColumn);
            $joinColumn->setSender($this);
        }

        return $this;
    }

    public function removeJoinColumn(Message $joinColumn): static
    {
        if ($this->joinColumn->removeElement($joinColumn)) {
            // set the owning side to null (unless already changed)
            if ($joinColumn->getSender() === $this) {
                $joinColumn->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getIsRead(): Collection
    {
        return $this->isRead;
    }

    public function addIsRead(Message $isRead): static
    {
        if (!$this->isRead->contains($isRead)) {
            $this->isRead->add($isRead);
            $isRead->setReceiver($this);
        }

        return $this;
    }

    public function removeIsRead(Message $isRead): static
    {
        if ($this->isRead->removeElement($isRead)) {
            // set the owning side to null (unless already changed)
            if ($isRead->getReceiver() === $this) {
                $isRead->setReceiver(null);
            }
        }

        return $this;
    }


    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }


    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getMemberSince(): string
    {
        if (!$this->createdAt) {
            return 'Date inconnue';
        }

        return 'Membre depuis ' . $this->createdAt->format('F Y');
    }

    public function getLastActivity(): string
    {
        if (!$this->lastLoginAt) {
            return 'Jamais connecté';
        }

        $now = new \DateTime();
        $diff = $now->diff($this->lastLoginAt);

        if ($diff->days === 0 && $diff->h === 0 && $diff->i < 5) {
            return 'En ligne';
        } elseif ($diff->days === 0 && $diff->h === 0) {
            return 'Il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        } elseif ($diff->days === 0) {
            return 'Il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->days === 1) {
            return 'Hier';
        } else {
            return 'Il y a ' . $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
        }
    }

    public function isOnline(): bool
    {
        if (!$this->lastLoginAt) {
            return false;
        }

        $now = new \DateTime();
        $diff = $now->diff($this->lastLoginAt);

        return ($diff->days === 0 && $diff->h === 0 && $diff->i < 5);
    }
}
