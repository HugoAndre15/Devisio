<?php

namespace App\Service;

use App\Entity\Quote;
use App\Entity\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGeneratorService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function generateQuotePdf(Quote $quote): string
    {
        $html = $this->twig->render('pdf/quote.html.twig', [
            'quote' => $quote,
        ]);

        return $this->generatePdf($html);
    }

    public function generateInvoicePdf(Invoice $invoice): string
    {
        $html = $this->twig->render('pdf/invoice.html.twig', [
            'invoice' => $invoice,
        ]);

        return $this->generatePdf($html);
    }

    private function generatePdf(string $html): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}