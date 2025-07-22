<?php

namespace App\Entity;

use App\Repository\DiscountCodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DiscountCodeRepository::class)]
class DiscountCode
{
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED_AMOUNT = 'fixed_amount';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le code est requis.')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'Le code doit faire au moins 3 caractères.')]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est requis.')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le type de réduction est requis.')]
    #[Assert\Choice(choices: [self::TYPE_PERCENTAGE, self::TYPE_FIXED_AMOUNT], message: 'Type de réduction invalide.')]
    private ?string $type = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'La valeur de réduction est requise.')]
    #[Assert\Positive(message: 'La valeur de réduction doit être positive.')]
    private ?string $value = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\Positive(message: 'Le montant minimum doit être positif.')]
    private ?string $minimumAmount = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\Positive(message: 'Le montant maximum doit être positif.')]
    private ?string $maximumDiscount = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $validFrom = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $validUntil = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'Le nombre d\'utilisations doit être positif.')]
    private ?int $usageLimit = null;

    #[ORM\Column(type: 'integer')]
    private int $usageCount = 0;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'discountCodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

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

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_PERCENTAGE => 'Pourcentage',
            self::TYPE_FIXED_AMOUNT => 'Montant fixe',
            default => 'Inconnu'
        };
    }

    public static function getTypeChoices(): array
    {
        return [
            'Pourcentage' => self::TYPE_PERCENTAGE,
            'Montant fixe' => self::TYPE_FIXED_AMOUNT,
        ];
    }

    public function isValidForDate(\DateTimeInterface $date): bool
    {
        if ($this->validFrom && $date < $this->validFrom) {
            return false;
        }

        if ($this->validUntil && $date > $this->validUntil) {
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

    public function canBeUsed(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->usageLimit && $this->usageCount >= $this->usageLimit) {
            return false;
        }

        $now = new \DateTime();
        if (!$this->isValidForDate($now)) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if (!$this->isValidForAmount($amount)) {
            return 0.0;
        }

        $discount = 0.0;

        if ($this->type === self::TYPE_PERCENTAGE) {
            $discount = $amount * ((float) $this->value / 100);
        } elseif ($this->type === self::TYPE_FIXED_AMOUNT) {
            $discount = (float) $this->value;
        }

        // Appliquer la limite de réduction maximale
        if ($this->maximumDiscount && $discount > (float) $this->maximumDiscount) {
            $discount = (float) $this->maximumDiscount;
        }

        // Ne pas dépasser le montant total
        if ($discount > $amount) {
            $discount = $amount;
        }

        return $discount;
    }

    public function incrementUsage(): void
    {
        $this->usageCount++;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getFormattedValue(): string
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return $this->value . '%';
        }
        
        return number_format((float) $this->value, 2, ',', ' ') . ' €';
    }

    public function getUsageText(): string
    {
        if ($this->usageLimit) {
            return $this->usageCount . '/' . $this->usageLimit;
        }
        
        return (string) $this->usageCount;
    }

    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->validUntil) {
            return null;
        }

        $now = new \DateTime();
        $interval = $now->diff($this->validUntil);
        
        return $interval->invert ? -$interval->days : $interval->days;
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        $daysUntilExpiration = $this->getDaysUntilExpiration();
        
        return $daysUntilExpiration !== null && $daysUntilExpiration <= $days && $daysUntilExpiration >= 0;
    }
}