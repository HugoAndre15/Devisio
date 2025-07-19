<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    const TYPE_ACCOMMODATION = 'accommodation';
    const TYPE_TRANSPORT = 'transport';
    const TYPE_ACTIVITY = 'activity';
    const TYPE_PACKAGE = 'package';
    const TYPE_INSURANCE = 'insurance';
    const TYPE_GUIDE = 'guide';
    const TYPE_MEAL = 'meal';
    const TYPE_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est requis.')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de produit est requis.')]
    #[Assert\Choice(choices: [
        self::TYPE_ACCOMMODATION,
        self::TYPE_TRANSPORT,
        self::TYPE_ACTIVITY,
        self::TYPE_PACKAGE,
        self::TYPE_INSURANCE,
        self::TYPE_GUIDE,
        self::TYPE_MEAL,
        self::TYPE_OTHER
    ], message: 'Le type de produit sélectionné n\'est pas valide.')]
    private ?string $type = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix est requis.')]
    #[Assert\Positive(message: 'Le prix doit être positif.')]
    private ?string $price = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'L\'unité est requise.')]
    private ?string $unit = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: QuoteItem::class)]
    private Collection $quoteItems;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: InvoiceItem::class)]
    private Collection $invoiceItems;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductPrice::class)]
    private Collection $prices;

    public function __construct()
    {
        $this->quoteItems = new ArrayCollection();
        $this->invoiceItems = new ArrayCollection();
        $this->prices = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;
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
     * @return Collection<int, QuoteItem>
     */
    public function getQuoteItems(): Collection
    {
        return $this->quoteItems;
    }

    public function addQuoteItem(QuoteItem $quoteItem): static
    {
        if (!$this->quoteItems->contains($quoteItem)) {
            $this->quoteItems->add($quoteItem);
            $quoteItem->setProduct($this);
        }
        return $this;
    }

    public function removeQuoteItem(QuoteItem $quoteItem): static
    {
        if ($this->quoteItems->removeElement($quoteItem)) {
            if ($quoteItem->getProduct() === $this) {
                $quoteItem->setProduct(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, InvoiceItem>
     */
    public function getInvoiceItems(): Collection
    {
        return $this->invoiceItems;
    }

    public function addInvoiceItem(InvoiceItem $invoiceItem): static
    {
        if (!$this->invoiceItems->contains($invoiceItem)) {
            $this->invoiceItems->add($invoiceItem);
            $invoiceItem->setProduct($this);
        }
        return $this;
    }

    public function removeInvoiceItem(InvoiceItem $invoiceItem): static
    {
        if ($this->invoiceItems->removeElement($invoiceItem)) {
            if ($invoiceItem->getProduct() === $this) {
                $invoiceItem->setProduct(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ProductPrice>
     */
    public function getPrices(): Collection
    {
        return $this->prices;
    }

    public function addPrice(ProductPrice $price): static
    {
        if (!$this->prices->contains($price)) {
            $this->prices->add($price);
            $price->setProduct($this);
        }
        return $this;
    }

    public function removePrice(ProductPrice $price): static
    {
        if ($this->prices->removeElement($price)) {
            if ($price->getProduct() === $this) {
                $price->setProduct(null);
            }
        }
        return $this;
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_ACCOMMODATION => 'Hébergement',
            self::TYPE_TRANSPORT => 'Transport',
            self::TYPE_ACTIVITY => 'Activité',
            self::TYPE_PACKAGE => 'Forfait',
            self::TYPE_INSURANCE => 'Assurance',
            self::TYPE_GUIDE => 'Guide',
            self::TYPE_MEAL => 'Repas',
            self::TYPE_OTHER => 'Autre',
            default => 'Inconnu'
        };
    }

    public static function getTypeChoices(): array
    {
        return [
            'Hébergement' => self::TYPE_ACCOMMODATION,
            'Transport' => self::TYPE_TRANSPORT,
            'Activité' => self::TYPE_ACTIVITY,
            'Forfait' => self::TYPE_PACKAGE,
            'Assurance' => self::TYPE_INSURANCE,
            'Guide' => self::TYPE_GUIDE,
            'Repas' => self::TYPE_MEAL,
            'Autre' => self::TYPE_OTHER,
        ];
    }

    public function getPriceForPeriod(\DateTimeInterface $date): ?string
    {
        foreach ($this->prices as $price) {
            if ($price->isActiveForDate($date)) {
                return $price->getPrice();
            }
        }
        return $this->price;
    }
}