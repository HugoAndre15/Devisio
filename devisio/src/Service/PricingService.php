<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Product;
use App\Entity\Season;
use App\Entity\DiscountCode;
use App\Entity\Quote;
use App\Entity\Invoice;
use App\Repository\SeasonRepository;
use App\Repository\DiscountCodeRepository;
use Doctrine\ORM\EntityManagerInterface;

class PricingService
{
    private SeasonRepository $seasonRepository;
    private DiscountCodeRepository $discountCodeRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SeasonRepository $seasonRepository,
        DiscountCodeRepository $discountCodeRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->seasonRepository = $seasonRepository;
        $this->discountCodeRepository = $discountCodeRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Calcule le prix d'un produit pour une date donnée
     */
    public function calculateProductPrice(Product $product, \DateTimeInterface $date): float
    {
        $basePrice = (float) $product->getPrice();
        
        // Vérifier s'il y a un prix spécifique pour la saison
        $season = $this->seasonRepository->findSeasonForDate($product->getCompany(), $date);
        
        if (!$season) {
            return $basePrice;
        }

        // Chercher un prix spécifique pour ce produit et cette saison
        foreach ($product->getPrices() as $productPrice) {
            if ($productPrice->getSeason() === $season && $productPrice->isActive()) {
                return (float) $productPrice->getPrice();
            }
        }

        // Sinon, appliquer le multiplicateur de saison
        return $basePrice * (float) $season->getMultiplier();
    }

    /**
     * Recalcule tous les totaux d'un devis avec tarifs saisonniers
     */
    public function recalculateQuote(Quote $quote): void
    {
        $subtotal = 0;
        $quoteDate = $quote->getQuoteDate();

        // Recalculer chaque item avec les prix saisonniers
        foreach ($quote->getItems() as $item) {
            if ($item->getProduct()) {
                $seasonalPrice = $this->calculateProductPrice($item->getProduct(), $quoteDate);
                $item->setUnitPrice(number_format($seasonalPrice, 2, '.', ''));
            }
            $subtotal += (float) $item->getTotal();
        }

        $quote->setSubtotal(number_format($subtotal, 2, '.', ''));
        
        // Recalculer avec réduction si applicable
        $this->applyDiscountToQuote($quote);
    }

    /**
     * Applique une réduction à un devis
     */
    public function applyDiscountToQuote(Quote $quote): void
    {
        $subtotal = (float) $quote->getSubtotal();
        $vatRate = $quote->getCompany() ? (float) $quote->getCompany()->getVatRate() : 20;
        
        // Calculer la réduction
        $discountAmount = 0;
        if ($quote->getDiscountCode() && $quote->getDiscountCode()->canBeUsed()) {
            $discountAmount = $quote->getDiscountCode()->calculateDiscount($subtotal);
        }
        
        $quote->setDiscountAmount(number_format($discountAmount, 2, '.', ''));
        
        // Calculer les totaux finaux
        $finalSubtotal = $subtotal - $discountAmount;
        $vatAmount = $finalSubtotal * ($vatRate / 100);
        
        $quote->setVatAmount(number_format($vatAmount, 2, '.', ''));
        $quote->setTotal(number_format($finalSubtotal + $vatAmount, 2, '.', ''));
    }

    /**
     * Applique une réduction à une facture
     */
    public function applyDiscountToInvoice(Invoice $invoice): void
    {
        $subtotal = (float) $invoice->getSubtotal();
        $vatRate = $invoice->getCompany() ? (float) $invoice->getCompany()->getVatRate() : 20;
        
        // Calculer la réduction
        $discountAmount = 0;
        if ($invoice->getDiscountCode() && $invoice->getDiscountCode()->canBeUsed()) {
            $discountAmount = $invoice->getDiscountCode()->calculateDiscount($subtotal);
        }
        
        $invoice->setDiscountAmount(number_format($discountAmount, 2, '.', ''));
        
        // Calculer les totaux finaux
        $finalSubtotal = $subtotal - $discountAmount;
        $vatAmount = $finalSubtotal * ($vatRate / 100);
        
        $invoice->setVatAmount(number_format($vatAmount, 2, '.', ''));
        $invoice->setTotal(number_format($finalSubtotal + $vatAmount, 2, '.', ''));
    }

    /**
     * Valide et applique un code de réduction
     */
    public function validateAndApplyDiscountCode(Quote|Invoice $document, string $code): array
    {
        $company = $document->getCompany();
        $discountCode = $this->discountCodeRepository->findValidCodeByCompany($company, $code);

        if (!$discountCode) {
            return [
                'success' => false,
                'message' => 'Code de réduction invalide ou inexistant.'
            ];
        }

        if (!$discountCode->canBeUsed()) {
            $message = 'Ce code de réduction ';
            if (!$discountCode->isActive()) {
                $message .= 'est désactivé.';
            } elseif ($discountCode->getUsageLimit() && $discountCode->getUsageCount() >= $discountCode->getUsageLimit()) {
                $message .= 'a atteint sa limite d\'utilisation.';
            } elseif (!$discountCode->isValidForDate(new \DateTime())) {
                $message .= 'n\'est plus valide.';
            } else {
                $message .= 'ne peut pas être utilisé.';
            }

            return [
                'success' => false,
                'message' => $message
            ];
        }

        $currentSubtotal = (float) $document->getSubtotal();
        if (!$discountCode->isValidForAmount($currentSubtotal)) {
            $minimumAmount = number_format((float) $discountCode->getMinimumAmount(), 2, ',', ' ');
            return [
                'success' => false,
                'message' => "Montant minimum requis : {$minimumAmount} €"
            ];
        }

        // Appliquer le code
        $document->setDiscountCode($discountCode);
        
        if ($document instanceof Quote) {
            $this->applyDiscountToQuote($document);
        } else {
            $this->applyDiscountToInvoice($document);
        }

        $discountAmount = (float) $document->getDiscountAmount();

        return [
            'success' => true,
            'message' => 'Code de réduction appliqué avec succès.',
            'discount_code' => [
                'id' => $discountCode->getId(),
                'name' => $discountCode->getName(),
                'code' => $discountCode->getCode(),
                'value' => $discountCode->getFormattedValue(),
                'discount_amount' => $discountAmount,
                'formatted_discount' => number_format($discountAmount, 2, ',', ' ') . ' €'
            ]
        ];
    }

    /**
     * Retire un code de réduction d'un document
     */
    public function removeDiscountCode(Quote|Invoice $document): void
    {
        $document->setDiscountCode(null);
        $document->setDiscountAmount('0.00');
        
        if ($document instanceof Quote) {
            $this->applyDiscountToQuote($document);
        } else {
            $this->applyDiscountToInvoice($document);
        }
    }

    /**
     * Génère un rapport des économies par code de réduction
     */
    public function getDiscountCodeStats(Company $company): array
    {
        $discountCodes = $this->discountCodeRepository->findBy(['company' => $company]);
        $stats = [];

        foreach ($discountCodes as $code) {
            $totalSavings = 0;
            $usageCount = 0;

            // Calculer les économies des devis
            foreach ($code->getQuotes() as $quote) {
                if ($quote->getDiscountAmount()) {
                    $totalSavings += (float) $quote->getDiscountAmount();
                    $usageCount++;
                }
            }

            // Calculer les économies des factures
            foreach ($code->getInvoices() as $invoice) {
                if ($invoice->getDiscountAmount()) {
                    $totalSavings += (float) $invoice->getDiscountAmount();
                    $usageCount++;
                }
            }

            $stats[] = [
                'code' => $code->getCode(),
                'name' => $code->getName(),
                'total_savings' => $totalSavings,
                'usage_count' => $usageCount,
                'is_active' => $code->isActive(),
                'expires_soon' => $code->isExpiringSoon()
            ];
        }

        return $stats;
    }

    /**
     * Vérifie et met à jour les prix saisonniers pour tous les produits d'une entreprise
     */
    public function updateSeasonalPrices(Company $company): int
    {
        $currentSeason = $this->seasonRepository->findCurrentSeason($company);
        
        if (!$currentSeason) {
            return 0;
        }

        $updatedCount = 0;
        $products = $company->getProducts();

        foreach ($products as $product) {
            if (!$product->isActive()) {
                continue;
            }

            // Vérifier s'il existe déjà un prix pour cette saison
            $existingPrice = null;
            foreach ($product->getPrices() as $productPrice) {
                if ($productPrice->getSeason() === $currentSeason) {
                    $existingPrice = $productPrice;
                    break;
                }
            }

            // Si pas de prix spécifique et multiplicateur différent de 1, en créer un
            if (!$existingPrice && $currentSeason->getMultiplier() != 1.0) {
                $basePrice = (float) $product->getPrice();
                $seasonalPrice = $basePrice * (float) $currentSeason->getMultiplier();

                $productPrice = new \App\Entity\ProductPrice();
                $productPrice->setProduct($product);
                $productPrice->setSeason($currentSeason);
                $productPrice->setPrice(number_format($seasonalPrice, 2, '.', ''));
                
                $this->entityManager->persist($productPrice);
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
        }

        return $updatedCount;
    }
}