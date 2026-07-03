<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Invoice;
use App\Models\Quote;

/**
 * Stelt de e-mailtekst voor factuur- en offertemails samen op basis van de
 * (opmaakbare) templates in de instellingen van de gebruiker.
 *
 * Levert per document drie varianten:
 * - subject : onderwerpregel
 * - html    : volledige HTML-mail (voor verzenden via de mailverbinding)
 * - text    : platte tekst (voor de mailto-fallback naar de lokale mailclient)
 *
 * Beschikbare placeholders: {contactpersoon}, {klantnaam}, {nummer},
 * {bedrag}, {vervaldatum} (factuur), {geldig_tot} (offerte), {bedrijfsnaam}.
 */
class DocumentEmailComposer
{
    private ?AppSetting $settings = null;

    private function settings(): AppSetting
    {
        return $this->settings ??= AppSetting::get();
    }

    public function forInvoice(Invoice $invoice): array
    {
        $customer = $invoice->customer;

        $vars = [
            '{contactpersoon}' => $customer->contact_person ?: $customer->name,
            '{klantnaam}'      => $customer->name,
            '{nummer}'         => $invoice->invoice_number,
            '{bedrag}'         => '€ ' . number_format($invoice->total, 2, ',', '.'),
            '{vervaldatum}'    => $invoice->due_date?->format('d-m-Y') ?? '',
            '{bedrijfsnaam}'   => $this->companyName(),
        ];

        return $this->compose(
            $this->settings()->invoiceEmailSubject(),
            $this->settings()->invoiceEmailBody(),
            $vars,
        );
    }

    public function forQuote(Quote $quote): array
    {
        $customer = $quote->customer;

        $vars = [
            '{contactpersoon}' => $customer->contact_person ?: $customer->name,
            '{klantnaam}'      => $customer->name,
            '{nummer}'         => $quote->quote_number,
            '{bedrag}'         => '€ ' . number_format($quote->total, 2, ',', '.'),
            '{geldig_tot}'     => $quote->valid_until ? \Carbon\Carbon::parse($quote->valid_until)->format('d-m-Y') : '',
            '{bedrijfsnaam}'   => $this->companyName(),
        ];

        return $this->compose(
            $this->settings()->quoteEmailSubject(),
            $this->settings()->quoteEmailBody(),
            $vars,
        );
    }

    private function companyName(): string
    {
        $user = auth()->user();

        return $user?->company_name ?: ($user?->name ?? '');
    }

    private function compose(string $subjectTemplate, string $bodyTemplate, array $vars): array
    {
        // Onderwerp: platte tekst.
        $subject = strtr($subjectTemplate, $vars);

        // HTML-body: placeholder-waarden escapen zodat klantnamen met
        // bijzondere tekens de opmaak niet breken.
        $escaped  = array_map(fn ($v) => e($v), $vars);
        $bodyHtml = strtr($bodyTemplate, $escaped);

        return [
            'subject' => $subject,
            'html'    => view('emails.document', ['bodyHtml' => $bodyHtml])->render(),
            'text'    => $this->toPlainText(strtr($bodyTemplate, $vars)),
        ];
    }

    /**
     * Zet de HTML-body om naar nette platte tekst voor de mailto-fallback.
     */
    private function toPlainText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $text);
        $text = preg_replace('/<\/?(p|div|ul|ol)[^>]*>/i', "\n", $text);
        $text = preg_replace('/<li[^>]*>/i', '- ', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Meer dan twee opeenvolgende regeleindes terugbrengen naar twee.
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}
