<?php

namespace App\Entity;

use App\Repository\InvoiceItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceItemRepository::class)]
class InvoiceItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est requis.')]
    private ?string $productName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix unitaire est requis.')]
    #[Assert\Positive(message: 'Le prix unitaire doit être positif.')]
    private ?string $unitPrice = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'La quantité est requise.')]
    #[Assert\Positive(message: 'La quantité doit être positive.')]
    private ?string $quantity = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'L\'unité est requise.')]
    private ?string $unit = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Invoice $invoice = null;

    #[ORM\ManyToOne(inversedBy: 'invoiceItems')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;
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

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        $this->calculateTotal();
        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;
        $this->calculateTotal();
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

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        
        if ($product) {
            $this->productName = $product->getName();
            $this->description = $product->getDescription();
            $this->unitPrice = $product->getPrice();
            $this->unit = $product->getUnit();
            $this->calculateTotal();
        }
        
        return $this;
    }

    private function calculateTotal(): void
    {
        if ($this->unitPrice && $this->quantity) {
            $this->total = number_format((float) $this->unitPrice * (float) $this->quantity, 2, '.', '');
        }
    }

    public function getFormattedUnitPrice(): string
    {
        return number_format((float) $this->unitPrice, 2, ',', ' ') . ' €';
    }

    public function getFormattedTotal(): string
    {
        return number_format((float) $this->total, 2, ',', ' ') . ' €';
    }
}