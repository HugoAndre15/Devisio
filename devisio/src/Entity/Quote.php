<?php

namespace App\Entity;

use App\Repository\QuoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
class Quote
{
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $number = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le sujet est requis.')]
    private ?string $subject = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date du devis est requise.')]
    private ?\DateTimeInterface $quoteDate = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de validité est requise.')]
    private ?\DateTimeInterface $validUntil = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $subtotal = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $vatAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $total = '0.00';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $terms = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $acceptedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $rejectedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteItem::class, cascade: ['persist', 'remove'])]
    private Collection $items;

    #[ORM\OneToOne(mappedBy: 'quote', cascade: ['persist', 'remove'])]
    private ?Invoice $invoice = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->quoteDate = new \DateTime();
        $this->validUntil = new \DateTime('+30 days');
        $this->generateNumber();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
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

    public function getQuoteDate(): ?\DateTimeInterface
    {
        return $this->quoteDate;
    }

    public function setQuoteDate(\DateTimeInterface $quoteDate): static
    {
        $this->quoteDate = $quoteDate;
        return $this;
    }

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(\DateTimeInterface $validUntil): static
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function getSubtotal(): ?string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getVatAmount(): ?string
    {
        return $this->vatAmount;
    }

    public function setVatAmount(string $vatAmount): static
    {
        $this->vatAmount = $vatAmount;
        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): static
    {
        $this->terms = $terms;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getAcceptedAt(): ?\DateTimeInterface
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeInterface $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;
        return $this;
    }

    public function getRejectedAt(): ?\DateTimeInterface
    {
        return $this->rejectedAt;
    }

    public function setRejectedAt(?\DateTimeInterface $rejectedAt): static
    {
        $this->rejectedAt = $rejectedAt;
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

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, QuoteItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(QuoteItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setQuote($this);
        }
        return $this;
    }

    public function removeItem(QuoteItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getQuote() === $this) {
                $item->setQuote(null);
            }
        }
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        if ($invoice === null && $this->invoice !== null) {
            $this->invoice->setQuote(null);
        }

        if ($invoice !== null && $invoice->getQuote() !== $this) {
            $invoice->setQuote($this);
        }

        $this->invoice = $invoice;
        return $this;
    }

    private function generateNumber(): void
    {
        $this->number = 'DEV-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SENT => 'Envoyé',
            self::STATUS_ACCEPTED => 'Accepté',
            self::STATUS_REJECTED => 'Refusé',
            self::STATUS_EXPIRED => 'Expiré',
            default => 'Inconnu'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SENT => 'blue',
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'orange',
            default => 'gray'
        };
    }

    public static function getStatusChoices(): array
    {
        return [
            'Brouillon' => self::STATUS_DRAFT,
            'Envoyé' => self::STATUS_SENT,
            'Accepté' => self::STATUS_ACCEPTED,
            'Refusé' => self::STATUS_REJECTED,
            'Expiré' => self::STATUS_EXPIRED,
        ];
    }

    public function canBeModified(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeSent(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->items->count() > 0;
    }

    public function canBeAccepted(): bool
    {
        return $this->status === self::STATUS_SENT && $this->validUntil >= new \DateTime();
    }

    public function canBeRejected(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function canBeConvertedToInvoice(): bool
    {
        return $this->status === self::STATUS_ACCEPTED && $this->invoice === null;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_SENT && $this->validUntil < new \DateTime();
    }

    public function markAsSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTime();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsAccepted(): void
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->acceptedAt = new \DateTime();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsRejected(): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->rejectedAt = new \DateTime();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsExpired(): void
    {
        $this->status = self::STATUS_EXPIRED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function hasInvoice(): bool
    {
        return $this->invoice !== null;
    }

    public function getFormattedTotal(): string
    {
        return number_format((float) $this->total, 2, ',', ' ') . ' €';
    }

    public function getFormattedSubtotal(): string
    {
        return number_format((float) $this->subtotal, 2, ',', ' ') . ' €';
    }

    public function getFormattedVatAmount(): string
    {
        return number_format((float) $this->vatAmount, 2, ',', ' ') . ' €';
    }

    public function getDaysUntilExpiration(): int
    {
        $now = new \DateTime();
        $interval = $now->diff($this->validUntil);
        return $interval->invert ? -$interval->days : $interval->days;
    }

    public function isNearExpiration(int $days = 7): bool
    {
        return $this->getDaysUntilExpiration() <= $days && $this->status === self::STATUS_SENT;
    }

    public function getProgressPercentage(): int
    {
        return match($this->status) {
            self::STATUS_DRAFT => 20,
            self::STATUS_SENT => 50,
            self::STATUS_ACCEPTED => 100,
            self::STATUS_REJECTED => 0,
            self::STATUS_EXPIRED => 0,
            default => 0
        };
    }

    public function getStatusIcon(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'fas fa-edit',
            self::STATUS_SENT => 'fas fa-paper-plane',
            self::STATUS_ACCEPTED => 'fas fa-check-circle',
            self::STATUS_REJECTED => 'fas fa-times-circle',
            self::STATUS_EXPIRED => 'fas fa-clock',
            default => 'fas fa-question-circle'
        };
    }

    public function getTags(): array
    {
        $tags = [];
        
        if ($this->isHighValue()) {
            $tags[] = 'Gros montant';
        }
        
        if ($this->isNearExpiration()) {
            $tags[] = 'Expire bientôt';
        }
        
        if ($this->isOld()) {
            $tags[] = 'Ancien';
        }
        
        if ($this->hasInvoice()) {
            $tags[] = 'Facturé';
        }
        
        return $tags;
    }

    public function isHighValue(float $threshold = 5000.0): bool
    {
        return $this->getTotalFloat() >= $threshold;
    }

    public function getTotalFloat(): float
    {
        return (float) $this->total;
    }

    public function getQuoteAge(): int
    {
        $now = new \DateTime();
        $interval = $now->diff($this->createdAt);
        return $interval->days;
    }

    public function isOld(int $days = 90): bool
    {
        return $this->getQuoteAge() > $days;
    }

    public function getValidityDays(): int
    {
        $interval = $this->quoteDate->diff($this->validUntil);
        return $interval->days;
    }

    public function getItemsCount(): int
    {
        return $this->items->count();
    }

    public function getMainProduct(): ?string
    {
        if ($this->items->isEmpty()) {
            return null;
        }
        
        return $this->items->first()->getProductName();
    }

    public function getCustomerType(): string
    {
        return $this->customer ? $this->customer->getTypeLabel() : 'Inconnu';
    }

    #[ORM\ManyToOne(inversedBy: 'quotes')]
private ?DiscountCode $discountCode = null;

#[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
private ?string $discountAmount = '0.00';

// Méthodes getter/setter à ajouter :
public function getDiscountCode(): ?DiscountCode
{
    return $this->discountCode;
}

public function setDiscountCode(?DiscountCode $discountCode): static
{
    $this->discountCode = $discountCode;
    return $this;
}

public function getDiscountAmount(): ?string
{
    return $this->discountAmount;
}

public function setDiscountAmount(?string $discountAmount): static
{
    $this->discountAmount = $discountAmount ?: '0.00';
    return $this;
}

// Méthode calculateTotals() mise à jour :
public function calculateTotals(): void
{
    $subtotal = 0;
    foreach ($this->items as $item) {
        $subtotal += (float) $item->getTotal();
    }
    
    $this->subtotal = number_format($subtotal, 2, '.', '');
    $vatRate = $this->company ? (float) $this->company->getVatRate() : 20;
    $this->vatAmount = number_format($subtotal * ($vatRate / 100), 2, '.', '');
    
    // Application des tarifs saisonniers
    $seasonalSubtotal = $this->calculateSeasonalPricing($subtotal);
    
    // Application de la réduction
    $discountAmount = 0;
    if ($this->discountCode && $this->discountCode->canBeUsed()) {
        $discountAmount = $this->discountCode->calculateDiscount($seasonalSubtotal);
    }
    $this->discountAmount = number_format($discountAmount, 2, '.', '');
    
    $finalSubtotal = $seasonalSubtotal - $discountAmount;
    $finalVatAmount = $finalSubtotal * ($vatRate / 100);
    
    $this->vatAmount = number_format($finalVatAmount, 2, '.', '');
    $this->total = number_format($finalSubtotal + $finalVatAmount, 2, '.', '');
}

// Nouvelle méthode pour calculer les tarifs saisonniers :
private function calculateSeasonalPricing(float $baseAmount): float
{
    if (!$this->company) {
        return $baseAmount;
    }

    // Trouver la saison active pour la date du devis
    $activeSeason = null;
    foreach ($this->company->getSeasons() as $season) {
        if ($season->isActiveForDate($this->quoteDate)) {
            $activeSeason = $season;
            break;
        }
    }

    if (!$activeSeason || $activeSeason->getMultiplier() == 1.0) {
        return $baseAmount;
    }

    return $baseAmount * (float) $activeSeason->getMultiplier();
}

// Méthodes d'affichage formatées :
public function getFormattedDiscountAmount(): string
{
    return number_format((float) $this->discountAmount, 2, ',', ' ') . ' €';
}

public function hasDiscount(): bool
{
    return $this->discountAmount && (float) $this->discountAmount > 0;
}

public function applyDiscountCode(DiscountCode $discountCode): bool
{
    if (!$discountCode->canBeUsed()) {
        return false;
    }

    $currentSubtotal = (float) $this->subtotal;
    if (!$discountCode->isValidForAmount($currentSubtotal)) {
        return false;
    }

    $this->discountCode = $discountCode;
    $this->calculateTotals();
    
    // Incrémenter l'utilisation du code
    $discountCode->incrementUsage();
    
    return true;
}

// Méthode pour supprimer le code de réduction :
public function removeDiscountCode(): void
{
    $this->discountCode = null;
    $this->discountAmount = '0.00';
    $this->calculateTotals();
}
}