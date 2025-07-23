<?php

namespace App\Entity;

use App\Repository\DiscountCodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DiscountCodeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DiscountCode
{
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_PERCENTAGE, self::TYPE_FIXED_AMOUNT])]
    private ?string $type = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $value = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $minimumAmount = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $maximumDiscount = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $validFrom = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $validUntil = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $usageLimit = null;

    #[ORM\Column(type: 'integer')]
    private int $usageCount = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'discountCode', targetEntity: Quote::class)]
    private Collection $quotes;

    #[ORM\OneToMany(mappedBy: 'discountCode', targetEntity: Invoice::class)]
    private Collection $invoices;

    public function __construct()
    {
        $this->quotes = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper(trim($code));
        return $this;
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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getMinimumAmount(): ?string
    {
        return $this->minimumAmount;
    }

    public function setMinimumAmount(?string $minimumAmount): static
    {
        $this->minimumAmount = $minimumAmount;
        return $this;
    }

    public function getMaximumDiscount(): ?string
    {
        return $this->maximumDiscount;
    }

    public function setMaximumDiscount(?string $maximumDiscount): static
    {
        $this->maximumDiscount = $maximumDiscount;
        return $this;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTimeInterface $validFrom): static
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(?\DateTimeInterface $validUntil): static
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usageLimit;
    }

    public function setUsageLimit(?int $usageLimit): static
    {
        $this->usageLimit = $usageLimit;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        return $this;
    }

    public function incrementUsageCount(): static
    {
        $this->usageCount++;
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

    /**
     * @return Collection<int, Quote>
     */
    public function getQuotes(): Collection
    {
        return $this->quotes;
    }

    public function addQuote(Quote $quote): static
    {
        if (!$this->quotes->contains($quote)) {
            $this->quotes->add($quote);
            $quote->setDiscountCode($this);
        }

        return $this;
    }

    public function removeQuote(Quote $quote): static
    {
        if ($this->quotes->removeElement($quote)) {
            if ($quote->getDiscountCode() === $this) {
                $quote->setDiscountCode(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setDiscountCode($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            if ($invoice->getDiscountCode() === $this) {
                $invoice->setDiscountCode(null);
            }
        }

        return $this;
    }

    // Méthodes utilitaires

    public function canBeUsed(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = new \DateTime();

        // Vérifier la date de début
        if ($this->validFrom && $this->validFrom > $now) {
            return false;
        }

        // Vérifier la date de fin
        if ($this->validUntil && $this->validUntil < $now) {
            return false;
        }

        // Vérifier la limite d'utilisation
        if ($this->usageLimit && $this->usageCount >= $this->usageLimit) {
            return false;
        }

        return true;
    }

    public function isValidForAmount(float $amount): bool
    {
        if ($this->minimumAmount && $amount < (float) $this->minimumAmount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if (!$this->canBeUsed() || !$this->isValidForAmount($amount)) {
            return 0;
        }

        $discount = 0;

        if ($this->type === self::TYPE_PERCENTAGE) {
            $discount = $amount * ((float) $this->value / 100);
            
            // Appliquer la limite maximale si définie
            if ($this->maximumDiscount && $discount > (float) $this->maximumDiscount) {
                $discount = (float) $this->maximumDiscount;
            }
        } elseif ($this->type === self::TYPE_FIXED_AMOUNT) {
            $discount = min((float) $this->value, $amount);
        }

        return round($discount, 2);
    }

    public function getFormattedValue(): string
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return (float) $this->value . '%';
        }

        return number_format((float) $this->value, 2, ',', ' ') . ' €';
    }

    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->validUntil) {
            return null;
        }

        $now = new \DateTime();
        $diff = $now->diff($this->validUntil);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    public static function getTypeChoices(): array
    {
        return [
            'Pourcentage' => self::TYPE_PERCENTAGE,
            'Montant fixe' => self::TYPE_FIXED_AMOUNT,
        ];
    }

    public function __toString(): string
    {
        return $this->code . ' - ' . $this->name;
    }


    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_PERCENTAGE => 'Pourcentage',
            self::TYPE_FIXED_AMOUNT => 'Montant fixe',
            default => 'Inconnu'
        };
    }

    public function getFormattedDescription(): string
    {
        $label = $this->getTypeLabel();
        $value = $this->getFormattedValue();
        
        $description = "{$label} : {$value}";
        
        if ($this->minimumAmount) {
            $description .= " (min. " . number_format((float) $this->minimumAmount, 2, ',', ' ') . " €)";
        }
        
        if ($this->type === self::TYPE_PERCENTAGE && $this->maximumDiscount) {
            $description .= " (max. " . number_format((float) $this->maximumDiscount, 2, ',', ' ') . " €)";
        }
        
        return $description;
    }

    
}