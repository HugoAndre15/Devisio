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

    #[ORM\Column(nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(nullable: true)]
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

    public function calculateTotals(): void
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += (float) $item->getTotal();
        }
        
        $this->subtotal = number_format($subtotal, 2, '.', '');
        $vatRate = $this->company ? (float) $this->company->getVatRate() : 20;
        $this->vatAmount = number_format($subtotal * ($vatRate / 100), 2, '.', '');
        $this->total = number_format($subtotal + (float) $this->vatAmount, 2, '.', '');
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
}