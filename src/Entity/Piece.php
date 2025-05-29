<?php

namespace App\Entity;

use App\Repository\PieceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PieceRepository::class)]
class Piece
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Image = null;

    #[ORM\Column(length: 255)]
    private ?string $Description = null;

    #[ORM\Column(nullable: true)]
    private ?bool $Exchange = null;

    #[ORM\Column(nullable: true)]
    private ?float $Price = null;

    #[ORM\ManyToOne(inversedBy: 'category')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'pieces')]
    private ?Category $category = null;

    /**
     * @var Collection<int, Annonce>
     */
    #[ORM\OneToMany(targetEntity: Annonce::class, mappedBy: 'piece')]
    private Collection $annonces;


    #[ORM\Column (nullable: true)]
    private ?\DateTime $update_at = null;

    #[ORM\Column (nullable: true)]
    private ?\DateTime $created_at = null;

    //#[ORM\Column(type: 'json')] case à cocher échange ou vente
    //private array $type = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): static
    {
        $this->Name = $Name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->Image;
    }

    public function setImage(?string $Image): static
    {
        $this->Image = $Image;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): static
    {
        $this->Description = $Description;

        return $this;
    }

    public function isExchange(): ?bool
    {
        return $this->Exchange;
    }

    public function setExchange(bool $Exchange): static
    {
        $this->Exchange = $Exchange;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->Price;
    }

    public function setPrice(float $Price): static
    {
        $this->Price = $Price;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getAnnonces(): Collection
    {
        return $this->annonces;
    }

    public function addAnnonce(Annonce $annonce): static
    {
        if (!$this->annonces->contains($annonce)) {
            $this->annonces->add($annonce);
        }

        return $this;
    }

    public function removeAnnonce(Annonce $annonce): static
    {
        if ($this->annonces->removeElement($annonce)) {
            
            if ($annonce->getSender() === $this) {
                $annonce->setSender(null);
            }
        }

        return $this;
    }

    public function getUpdateAt(): ?\DateTime
    {
        return $this->update_at;
    }

    public function setUpdateAt(?\DateTime $updateAt): static
    {
        $this->update_at = $updateAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

}
