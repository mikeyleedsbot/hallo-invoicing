<?php

namespace App\Services\BankImport;

use App\Models\BankImportSession;
use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Het importeren en koppelen van bankafschriften.
 *
 * Alles draait om één regel: transacties die nergens aan hangen worden bij het
 * afronden weggegooid. Zolang dat niet gebeurd is, blijft de import "open" en
 * ziet de gebruiker daar een melding van.
 */
class BankImportService
{
    public function __construct(
        private StatementReader $reader = new StatementReader(),
        private InvoiceMatcher $matcher = new InvoiceMatcher(),
    ) {
    }

    /**
     * Lees een geüpload bestand in en zet de transacties klaar. Regels die al
     * eerder geïmporteerd zijn (zelfde vingerafdruk) worden overgeslagen.
     */
    public function import(string $path, string $originalName, int $userId): BankImportSession
    {
        $parsed = $this->reader->read($path, $originalName);

        return DB::transaction(function () use ($parsed, $originalName, $userId) {
            $session = BankImportSession::create([
                'user_id' => $userId,
                'original_filename' => $originalName,
                'format' => $parsed['format'],
                'account_iban' => $parsed['iban'],
                'period_from' => $parsed['from'],
                'period_to' => $parsed['to'],
                'status' => BankImportSession::STATUS_OPEN,
            ]);

            $imported = 0;
            $skipped = 0;
            $recognised = [];

            foreach ($parsed['transactions'] as $t) {
                $fingerprint = $t->fingerprint();

                // Zelfde regel als eerder? Dan niet opnieuw opslaan, maar wel
                // onthouden zodat het matchscherm kan tonen dat hij er al is
                $existing = BankTransaction::withoutGlobalScope('belongs_to_user')
                    ->where('user_id', $userId)
                    ->where('fingerprint', $fingerprint)
                    ->first();

                if ($existing !== null) {
                    $skipped++;
                    $recognised[] = $existing->id;
                    continue;
                }

                BankTransaction::create([
                    'user_id' => $userId,
                    'bank_import_session_id' => $session->id,
                    'booking_date' => $t->bookingDate,
                    'value_date' => $t->valueDate,
                    'amount' => $t->amount,
                    'currency' => $t->currency,
                    'counterparty_name' => $t->counterpartyName,
                    'counterparty_iban' => $t->counterpartyIban,
                    'description' => $t->description,
                    'bank_reference' => $t->bankReference,
                    'fingerprint' => $fingerprint,
                ]);

                $imported++;
            }

            $session->update([
                'imported_count' => $imported,
                'skipped_count' => $skipped,
                'recognised_transaction_ids' => $recognised,
            ]);

            return $session->fresh();
        });
    }

    /**
     * Koppel automatisch wat overduidelijk bij elkaar hoort.
     *
     * @return int aantal automatisch gekoppelde transacties
     */
    public function autoMatch(BankImportSession $session): int
    {
        $matched = 0;

        foreach ($session->transactions()->orderBy('booking_date')->get() as $transaction) {
            if (! $transaction->isIncoming() || $transaction->isFullyAllocated()) {
                continue;
            }

            $candidates = $this->matcher->candidatesFor($transaction, $this->openInvoices($session->user_id));
            $best = $this->matcher->autoMatch($candidates);

            if ($best === null) {
                continue;
            }

            $this->link($transaction, $best['invoice'], $best['amount'], InvoicePayment::BY_AUTO);
            $matched++;
        }

        return $matched;
    }

    /**
     * Suggesties per nog niet (volledig) gekoppelde bijschrijving.
     *
     * @return array<int, array{transaction: BankTransaction, candidates: array}>
     */
    public function suggestions(BankImportSession $session): array
    {
        $invoices = $this->openInvoices($session->user_id);
        $rows = [];

        foreach ($session->transactions()->orderBy('booking_date')->get() as $transaction) {
            if (! $transaction->isIncoming() || $transaction->isFullyAllocated()) {
                continue;
            }

            $rows[] = [
                'transaction' => $transaction,
                'candidates' => $this->matcher->candidatesFor($transaction, $invoices),
            ];
        }

        return $rows;
    }

    /**
     * Koppel een bedrag van een transactie aan een factuur.
     *
     * Er wordt nooit meer toegewezen dan er nog van de transactie over is, en
     * nooit meer dan er op de factuur openstaat.
     */
    public function link(BankTransaction $transaction, Invoice $invoice, float $amount, string $by = InvoicePayment::BY_MANUAL): InvoicePayment
    {
        if ($transaction->user_id !== $invoice->user_id) {
            throw new BankImportException('Deze transactie hoort niet bij deze factuur.');
        }

        $amount = round(min($amount, $transaction->unallocatedAmount(), $invoice->outstandingAmount()), 2);

        if ($amount <= 0.004) {
            throw new BankImportException('Er is niets meer toe te wijzen op deze factuur of transactie.');
        }

        return DB::transaction(function () use ($transaction, $invoice, $amount, $by) {
            $payment = InvoicePayment::create([
                'user_id' => $transaction->user_id,
                'invoice_id' => $invoice->id,
                'bank_transaction_id' => $transaction->id,
                'amount' => $amount,
                'matched_by' => $by,
            ]);

            $invoice->refresh()->refreshPaymentStatus();

            return $payment;
        });
    }

    /** Maak een koppeling ongedaan; de factuur gaat terug naar openstaand. */
    public function unlink(InvoicePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            $payment->delete();

            $invoice?->refresh()->refreshPaymentStatus();
        });
    }

    /**
     * Rond de import af: alles wat niet aan een factuur hangt wordt gewist.
     * Dat is de kern van de afspraak dat er geen bankgegevens blijven staan
     * die nergens voor nodig zijn.
     *
     * @return int aantal verwijderde transacties
     */
    public function complete(BankImportSession $session): int
    {
        return DB::transaction(function () use ($session) {
            $removed = 0;

            foreach ($session->transactions()->get() as $transaction) {
                if ($transaction->payments()->exists()) {
                    continue;
                }

                $transaction->delete();
                $removed++;
            }

            $session->update([
                'status' => BankImportSession::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return $removed;
        });
    }

    /**
     * Gooi een hele import weg, inclusief de koppelingen die eruit voortkwamen.
     * De betrokken facturen gaan daardoor terug naar openstaand.
     */
    public function discard(BankImportSession $session): void
    {
        DB::transaction(function () use ($session) {
            // Eerst onthouden welke facturen hierdoor geraakt worden; na het
            // verwijderen is die koppeling weg
            $invoiceIds = InvoicePayment::whereIn(
                'bank_transaction_id',
                $session->transactions()->select('id')
            )->pluck('invoice_id')->unique();

            $session->delete();   // transacties en koppelingen volgen via cascade

            Invoice::whereIn('id', $invoiceIds)->get()
                ->each(fn (Invoice $invoice) => $invoice->refreshPaymentStatus());
        });
    }

    /** Openstaande verkoopfacturen van deze gebruiker. */
    private function openInvoices(int $userId): Collection
    {
        return Invoice::withoutGlobalScope('belongs_to_user')
            ->where('user_id', $userId)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->with('customer', 'payments')
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->outstandingAmount() > 0.004)
            ->values();
    }
}
