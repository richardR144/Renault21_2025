<?php

namespace App\Entity;
// src/Entity/User.php
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
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $pseudo = null;

   
    #[ORM\OneToMany(targetEntity: Piece::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true)]
    private Collection $pieces;

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

    public function __construct()
    {
        $this->joinColumn = new ArrayCollection();
        $this->isRead = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
        $this->pieces = new ArrayCollection();
    }

    public function createUser(string $pseudo, string $email, string $passwordHashed, string $role = 'ROLE_USER'): void
    {
        $this->pseudo = $pseudo;
        $this->email = $email;
        $this->password = $passwordHashed;
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
}
