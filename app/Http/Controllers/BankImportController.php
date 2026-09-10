<?php

namespace App\Http\Controllers;

use App\Models\BankImportSession;
use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\BankImport\BankImportException;
use App\Services\BankImport\BankImportService;
use App\Services\BankImport\StatementReader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Bankafschriften importeren en koppelen aan verkoopfacturen.
 *
 * Elk model gebruikt BelongsToUser, dus route-binding vindt alleen records van
 * de ingelogde gebruiker; een id van een ander bedrijf geeft simpelweg 404.
 */
class BankImportController extends Controller
{
    public function __construct(private BankImportService $service = new BankImportService())
    {
    }

    public function index()
    {
        $sessions = BankImportSession::withCount('transactions')
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        return view('bank.index', [
            'sessions' => $sessions,
            'openSession' => BankImportSession::where('status', BankImportSession::STATUS_OPEN)->latest()->first(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'statement' => [
                'required', 'file',
                'max:' . (int) (StatementReader::MAX_BYTES / 1024),
                'mimes:csv,txt,xml,sta,940,zip',
            ],
        ], [], ['statement' => 'Bankafschrift']);

        $file = $request->file('statement');

        try {
            $session = $this->service->import(
                $file->getRealPath(),
                $file->getClientOriginalName(),
                auth()->id()
            );
        } catch (BankImportException $e) {
            return back()->withErrors(['statement' => $e->getMessage()]);
        }

        $auto = $this->service->autoMatch($session);

        $message = $session->imported_count . ' transacties ingelezen (' . $session->formatLabel() . ')';
        $message .= $session->skipped_count > 0 ? ', ' . $session->skipped_count . ' al eerder geïmporteerd' : '';
        $message .= $auto > 0 ? '. ' . $auto . ' automatisch gekoppeld.' : '.';

        return redirect()->route('bank.show', $session)->with('success', $message);
    }

    public function show(BankImportSession $session)
    {
        $suggestions = $this->service->suggestions($session);

        $openInvoices = Invoice::whereNotIn('status', ['cancelled', 'draft'])
            ->with('customer', 'payments')
            ->orderByDesc('invoice_date')
            ->get()
            ->filter(fn (Invoice $i) => $i->outstandingAmount() > 0.004)
            ->values();

        // Eén keer meegeven aan de zoekdropdowns, zodat de facturenlijst niet
        // per transactie in de HTML herhaald wordt
        $invoiceOptions = $openInvoices->map(fn (Invoice $i) => [
            'id' => (string) $i->id,
            'number' => (string) $i->invoice_number,
            'customer' => (string) ($i->customer?->company_name ?: $i->customer?->name),
            'label' => $i->invoice_number . ' — '
                . ($i->customer?->company_name ?: $i->customer?->name)
                . ' — openstaand € ' . number_format($i->outstandingAmount(), 2, ',', '.'),
        ])->values();

        return view('bank.show', [
            'session' => $session,
            'suggestions' => $suggestions,
            'matched' => InvoicePayment::with('invoice.customer', 'transaction')
                ->whereHas('transaction', fn ($q) => $q->where('bank_import_session_id', $session->id))
                ->get(),
            'openInvoices' => $openInvoices,
            'invoiceOptions' => $invoiceOptions,
        ]);
    }

    public function link(Request $request, BankTransaction $transaction)
    {
        $validated = $request->validate([
            // Rule::exists met user_id: een factuur van een ander account bestaat hier niet
            'invoice_id' => ['required', Rule::exists('invoices', 'id')->where('user_id', auth()->id())],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ], [], [
            'invoice_id' => 'Factuur',
            'amount' => 'Bedrag',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        try {
            $this->service->link($transaction, $invoice, (float) $validated['amount']);
        } catch (BankImportException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', 'Transactie gekoppeld aan factuur ' . $invoice->invoice_number . '.');
    }

    public function unlink(InvoicePayment $payment)
    {
        $number = $payment->invoice?->invoice_number;

        $this->service->unlink($payment);

        return back()->with('success', 'Koppeling met factuur ' . $number . ' ongedaan gemaakt.');
    }

    public function complete(BankImportSession $session)
    {
        if (! $session->isOpen()) {
            return back()->with('info', 'Deze import was al afgerond.');
        }

        $removed = $this->service->complete($session);

        return redirect()->route('bank.index')->with(
            'success',
            'Import afgerond. ' . $removed . ' niet-gekoppelde transacties zijn verwijderd.'
        );
    }

    public function destroy(BankImportSession $session)
    {
        $this->service->discard($session);

        return redirect()->route('bank.index')
            ->with('success', 'Import weggegooid; er zijn geen transacties van bewaard.');
    }
}
