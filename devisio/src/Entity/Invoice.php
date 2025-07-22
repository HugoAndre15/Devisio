<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
class Invoice
{
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

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
    #[Assert\NotBlank(message: 'La date de facture est requise.')]
    private ?\DateTimeInterface $invoiceDate = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date d\'échéance est requise.')]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $subtotal = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $vatAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $total = '0.00';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToOne(inversedBy: 'invoice')]
    private ?Quote $quote = null;

    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceItem::class, cascade: ['persist', 'remove'])]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceReminder::class)]
    private Collection $reminders;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    private ?DiscountCode $discountCode = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $discountAmount = '0.00';


    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->reminders = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->invoiceDate = new \DateTime();
        $this->dueDate = new \DateTime('+30 days');
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

    public function getInvoiceDate(): ?\DateTimeInterface
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(\DateTimeInterface $invoiceDate): static
    {
        $this->invoiceDate = $invoiceDate;
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
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

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): static
    {
        $this->paymentTerms = $paymentTerms;
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

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;
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

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): static
    {
        $this->quote = $quote;
        return $this;
    }

    /**
     * @return Collection<int, InvoiceItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setInvoice($this);
        }
        return $this;
    }

    public function removeItem(InvoiceItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getInvoice() === $this) {
                $item->setInvoice(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, InvoiceReminder>
     */
    public function getReminders(): Collection
    {
        return $this->reminders;
    }

    public function addReminder(InvoiceReminder $reminder): static
    {
        if (!$this->reminders->contains($reminder)) {
            $this->reminders->add($reminder);
            $reminder->setInvoice($this);
        }
        return $this;
    }

    public function removeReminder(InvoiceReminder $reminder): static
    {
        if ($this->reminders->removeElement($reminder)) {
            if ($reminder->getInvoice() === $this) {
                $reminder->setInvoice(null);
            }
        }
        return $this;
    }

    private function generateNumber(): void
    {
        $this->number = 'FAC-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SENT => 'Envoyée',
            self::STATUS_PAID => 'Payée',
            self::STATUS_OVERDUE => 'En retard',
            self::STATUS_CANCELLED => 'Annulée',
            default => 'Inconnu'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SENT => 'blue',
            self::STATUS_PAID => 'green',
            self::STATUS_OVERDUE => 'red',
            self::STATUS_CANCELLED => 'orange',
            default => 'gray'
        };
    }

    public static function getStatusChoices(): array
    {
        return [
            'Brouillon' => self::STATUS_DRAFT,
            'Envoyée' => self::STATUS_SENT,
            'Payée' => self::STATUS_PAID,
            'En retard' => self::STATUS_OVERDUE,
            'Annulée' => self::STATUS_CANCELLED,
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

    public function canBePaid(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_OVERDUE]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_OVERDUE]);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_SENT && $this->dueDate < new \DateTime();
    }

    public function markAsSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTime();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsPaid(): void
    {
        $this->status = self::STATUS_PAID;
        $this->paidAt = new \DateTime();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsOverdue(): void
    {
        $this->status = self::STATUS_OVERDUE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsCancelled(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getDaysUntilDue(): int
    {
        $now = new \DateTime();
        $interval = $now->diff($this->dueDate);
        return $interval->invert ? -$interval->days : $interval->days;
    }

    public function createFromQuote(Quote $quote): void
    {
        $this->quote = $quote;
        $this->subject = $quote->getSubject();
        $this->description = $quote->getDescription();
        $this->customer = $quote->getCustomer();
        $this->company = $quote->getCompany();
        $this->createdBy = $quote->getCreatedBy();
        
        // Copy items from quote
        foreach ($quote->getItems() as $quoteItem) {
            $invoiceItem = new InvoiceItem();
            $invoiceItem->setProductName($quoteItem->getProductName());
            $invoiceItem->setDescription($quoteItem->getDescription());
            $invoiceItem->setUnitPrice($quoteItem->getUnitPrice());
            $invoiceItem->setQuantity($quoteItem->getQuantity());
            $invoiceItem->setUnit($quoteItem->getUnit());
            $invoiceItem->setProduct($quoteItem->getProduct());
            $this->addItem($invoiceItem);
        }
        
        $this->calculateTotals();
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