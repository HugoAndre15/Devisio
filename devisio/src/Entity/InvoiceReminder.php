<?php

namespace App\Entity;

use App\Repository\InvoiceReminderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceReminderRepository::class)]
class InvoiceReminder
{
    const TYPE_FIRST = 'first';
    const TYPE_SECOND = 'second';
    const TYPE_FINAL = 'final';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de relance est requis.')]
    #[Assert\Choice(choices: [self::TYPE_FIRST, self::TYPE_SECOND, self::TYPE_FINAL], message: 'Le type de relance sélectionné n\'est pas valide.')]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le sujet est requis.')]
    private ?string $subject = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Le message est requis.')]
    private ?string $message = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column]
    private bool $isSent = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'reminders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Invoice $invoice = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
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

    public function isSent(): bool
    {
        return $this->isSent;
    }

    public function setIsSent(bool $isSent): static
    {
        $this->isSent = $isSent;
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

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        $this->invoice = $invoice;
        return $this;
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_FIRST => 'Première relance',
            self::TYPE_SECOND => 'Deuxième relance',
            self::TYPE_FINAL => 'Mise en demeure',
            default => 'Inconnu'
        };
    }

    public static function getTypeChoices(): array
    {
        return [
            'Première relance' => self::TYPE_FIRST,
            'Deuxième relance' => self::TYPE_SECOND,
            'Mise en demeure' => self::TYPE_FINAL,
        ];
    }

    public function markAsSent(): void
    {
        $this->isSent = true;
        $this->sentAt = new \DateTime();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getDaysAfterDue(): int
    {
        return match($this->type) {
            self::TYPE_FIRST => 7,
            self::TYPE_SECOND => 14,
            self::TYPE_FINAL => 30,
            default => 0
        };
    }

    public function shouldBeSent(): bool
    {
        if ($this->isSent || !$this->invoice->isOverdue()) {
            return false;
        }

        $daysOverdue = abs($this->invoice->getDaysUntilDue());
        $requiredDays = $this->getDaysAfterDue();

        return $daysOverdue >= $requiredDays;
    }
}