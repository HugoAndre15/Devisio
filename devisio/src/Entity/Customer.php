<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    const TYPE_INDIVIDUAL = 'individual';
    const TYPE_COMPANY = 'company';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de client est requis.')]
    #[Assert\Choice(choices: [self::TYPE_INDIVIDUAL, self::TYPE_COMPANY], message: 'Le type de client sélectionné n\'est pas valide.')]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est requis.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de famille est requis.')]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'email est requis.')]
    #[Assert\Email(message: 'L\'email n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'adresse est requise.')]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le code postal est requis.')]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La ville est requise.')]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le pays est requis.')]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vatNumber = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'customers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Quote::class)]
    private Collection $quotes;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Invoice::class)]
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;
        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber;
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
            $quote->setCustomer($this);
        }
        return $this;
    }

    public function removeQuote(Quote $quote): static
    {
        if ($this->quotes->removeElement($quote)) {
            if ($quote->getCustomer() === $this) {
                $quote->setCustomer(null);
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
            $invoice->setCustomer($this);
        }
        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            if ($invoice->getCustomer() === $this) {
                $invoice->setCustomer(null);
            }
        }
        return $this;
    }

    public function getFullName(): string
    {
        if ($this->type === self::TYPE_COMPANY && $this->companyName) {
            return $this->companyName . ' (' . $this->firstName . ' ' . $this->lastName . ')';
        }
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getDisplayName(): string
    {
        return $this->type === self::TYPE_COMPANY && $this->companyName 
            ? $this->companyName 
            : $this->firstName . ' ' . $this->lastName;
    }

    public function getFullAddress(): string
    {
        return $this->address . ', ' . $this->postalCode . ' ' . $this->city . ', ' . $this->country;
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_INDIVIDUAL => 'Particulier',
            self::TYPE_COMPANY => 'Entreprise',
            default => 'Inconnu'
        };
    }

    public static function getTypeChoices(): array
    {
        return [
            'Particulier' => self::TYPE_INDIVIDUAL,
            'Entreprise' => self::TYPE_COMPANY,
        ];
    }

    public function isCompany(): bool
    {
        return $this->type === self::TYPE_COMPANY;
    }

    public function isIndividual(): bool
    {
        return $this->type === self::TYPE_INDIVIDUAL;
    }

    public function getQuoteCount(): int
    {
        return $this->quotes->count();
    }

    public function getInvoiceCount(): int
    {
        return $this->invoices->count();
    }

    public function getTotalQuoteAmount(): float
    {
        $total = 0;
        foreach ($this->quotes as $quote) {
            $total += (float) $quote->getTotal();
        }
        return $total;
    }

    public function getTotalInvoiceAmount(): float
    {
        $total = 0;
        foreach ($this->invoices as $invoice) {
            $total += (float) $invoice->getTotal();
        }
        return $total;
    }

    public function getPaidInvoiceAmount(): float
    {
        $total = 0;
        foreach ($this->invoices as $invoice) {
            if ($invoice->getStatus() === 'paid') {
                $total += (float) $invoice->getTotal();
            }
        }
        return $total;
    }

    public function hasActiveQuotes(): bool
    {
        foreach ($this->quotes as $quote) {
            if (in_array($quote->getStatus(), ['draft', 'sent'])) {
                return true;
            }
        }
        return false;
    }

    public function hasUnpaidInvoices(): bool
    {
        foreach ($this->invoices as $invoice) {
            if (in_array($invoice->getStatus(), ['sent', 'overdue'])) {
                return true;
            }
        }
        return false;
    }

    public function getLastQuoteDate(): ?\DateTimeInterface
    {
        $lastQuote = null;
        foreach ($this->quotes as $quote) {
            if (!$lastQuote || $quote->getCreatedAt() > $lastQuote->getCreatedAt()) {
                $lastQuote = $quote;
            }
        }
        return $lastQuote ? $lastQuote->getCreatedAt() : null;
    }

    public function isNewCustomer(): bool
    {
        return $this->quotes->count() === 0 && $this->invoices->count() === 0;
    }

    public function getConversionRate(): float
    {
        if ($this->quotes->count() === 0) {
            return 0;
        }
        return ($this->invoices->count() / $this->quotes->count()) * 100;
    }
}