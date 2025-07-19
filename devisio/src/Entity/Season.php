<?php

namespace App\Entity;

use App\Repository\SeasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
class Season
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la saison est requis.')]
    private ?string $name = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de début est requise.')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de fin est requise.')]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Assert\NotBlank(message: 'Le multiplicateur est requis.')]
    #[Assert\Positive(message: 'Le multiplicateur doit être positif.')]
    private ?string $multiplier = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'seasons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\OneToMany(mappedBy: 'season', targetEntity: ProductPrice::class)]
    private Collection $productPrices;

    public function __construct()
    {
        $this->productPrices = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getMultiplier(): ?string
    {
        return $this->multiplier;
    }

    public function setMultiplier(string $multiplier): static
    {
        $this->multiplier = $multiplier;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return Collection<int, ProductPrice>
     */
    public function getProductPrices(): Collection
    {
        return $this->productPrices;
    }

    public function addProductPrice(ProductPrice $productPrice): static
    {
        if (!$this->productPrices->contains($productPrice)) {
            $this->productPrices->add($productPrice);
            $productPrice->setSeason($this);
        }
        return $this;
    }

    public function removeProductPrice(ProductPrice $productPrice): static
    {
        if ($this->productPrices->removeElement($productPrice)) {
            if ($productPrice->getSeason() === $this) {
                $productPrice->setSeason(null);
            }
        }
        return $this;
    }

    public function isActiveForDate(\DateTimeInterface $date): bool
    {
        return $this->isActive && 
               $date >= $this->startDate && 
               $date <= $this->endDate;
    }

    public function getDateRange(): string
    {
        return $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y');
    }
}