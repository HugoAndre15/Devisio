<?php

namespace App\Service;

use App\Entity\Quote;
use App\Entity\Invoice;
use App\Entity\InvoiceReminder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class EmailService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private PdfGeneratorService $pdfGenerator;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        PdfGeneratorService $pdfGenerator
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->pdfGenerator = $pdfGenerator;
    }

    public function sendQuoteToCustomer(Quote $quote): void
    {
        $company = $quote->getCompany();
        $customer = $quote->getCustomer();

        $email = (new Email())
            ->from(new Address($company->getEmail() ?: 'noreply@devisio.com', $company->getName()))
            ->to($customer->getEmail())
            ->subject('Devis ' . $quote->getNumber() . ' - ' . $quote->getSubject())
            ->html($this->twig->render('email/quote.html.twig', [
                'quote' => $quote,
                'customer' => $customer,
                'company' => $company,
            ]));

        // Attach PDF
        $pdf = $this->pdfGenerator->generateQuotePdf($quote);
        $email->attach($pdf, 'devis-' . $quote->getNumber() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    public function sendInvoiceToCustomer(Invoice $invoice): void
    {
        $company = $invoice->getCompany();
        $customer = $invoice->getCustomer();

        $email = (new Email())
            ->from(new Address($company->getEmail() ?: 'noreply@devisio.com', $company->getName()))
            ->to($customer->getEmail())
            ->subject('Facture ' . $invoice->getNumber() . ' - ' . $invoice->getSubject())
            ->html($this->twig->render('email/invoice.html.twig', [
                'invoice' => $invoice,
                'customer' => $customer,
                'company' => $company,
            ]));

        // Attach PDF
        $pdf = $this->pdfGenerator->generateInvoicePdf($invoice);
        $email->attach($pdf, 'facture-' . $invoice->getNumber() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    public function sendInvoiceReminder(InvoiceReminder $reminder): void
    {
        $invoice = $reminder->getInvoice();
        $company = $invoice->getCompany();
        $customer = $invoice->getCustomer();

        $email = (new Email())
            ->from(new Address($company->getEmail() ?: 'noreply@devisio.com', $company->getName()))
            ->to($customer->getEmail())
            ->subject($reminder->getSubject())
            ->html($this->twig->render('email/invoice_reminder.html.twig', [
                'reminder' => $reminder,
                'invoice' => $invoice,
                'customer' => $customer,
                'company' => $company,
            ]));

        // Attach PDF
        $pdf = $this->pdfGenerator->generateInvoicePdf($invoice);
        $email->attach($pdf, 'facture-' . $invoice->getNumber() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    public function sendWelcomeEmail(string $recipientEmail, string $recipientName, string $companyName): void
    {
        $email = (new Email())
            ->from(new Address('noreply@devisio.com', 'Devisio'))
            ->to($recipientEmail)
            ->subject('Bienvenue sur Devisio !')
            ->html($this->twig->render('email/welcome.html.twig', [
                'recipientName' => $recipientName,
                'companyName' => $companyName,
            ]));

        $this->mailer->send($email);
    }
}