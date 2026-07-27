<?php

namespace App\Http\Controllers;

use App\Models\InvoiceTemplate;
use App\Services\TemplatePresets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TemplateController extends Controller
{
    /**
     * Display a listing of templates.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = InvoiceTemplate::orderBy('is_default_invoice', 'desc')
            ->orderBy('is_default_quote', 'desc')
            ->orderBy('created_at', 'desc');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $templates = $query->get();

        return view('templates.index', compact('templates', 'search'));
    }

    /**
     * Show the form for creating a new template.
     */
    public function create()
    {
        $presets = TemplatePresets::all();

        return view('templates.create', compact('presets'));
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default_invoice' => 'boolean',
            'is_default_quote' => 'boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
            'background' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
            'page_size' => 'nullable|string|in:A4,Letter',
            'preset' => ['nullable', 'string', Rule::in(TemplatePresets::keys())],
        ]);

        // Handle logo upload (private storage)
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('template-files/logos');
        }

        // Handle background upload (private storage)
        $backgroundPath = null;
        if ($request->hasFile('background')) {
            $backgroundPath = $request->file('background')->store('template-files/backgrounds');
        }

        $template = InvoiceTemplate::create([
            'name' => $validated['name'],
            'is_default_invoice' => $request->boolean('is_default_invoice'),
            'is_default_quote' => $request->boolean('is_default_quote'),
            'logo_path' => $logoPath,
            'background_path' => $backgroundPath,
            'page_size' => $validated['page_size'] ?? 'A4',
            // Gekozen stijlsjabloon direct toepassen (fijn te tunen in de editor)
            'field_positions' => TemplatePresets::positions($validated['preset'] ?? 'klassiek'),
        ]);

        $this->clearOtherDefaults($template);

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template aangemaakt!');
    }

    /**
     * Show the form for editing the template.
     */
    public function edit(InvoiceTemplate $template)
    {
        return view('templates.edit', compact('template'));
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, InvoiceTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default_invoice' => 'boolean',
            'is_default_quote' => 'boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
            'background' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
            'page_size' => 'nullable|string|in:A4,Letter',
            'remove_logo' => 'boolean',
            'remove_background' => 'boolean',
        ]);

        // Handle logo removal
        if ($request->boolean('remove_logo') && $template->logo_path) {
            Storage::delete($template->logo_path);
            $template->logo_path = null;
        }

        // Handle logo upload (private storage)
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($template->logo_path) {
                Storage::delete($template->logo_path);
            }
            $template->logo_path = $request->file('logo')->store('template-files/logos');
        }

        // Handle background removal
        if ($request->boolean('remove_background') && $template->background_path) {
            Storage::delete($template->background_path);
            $template->background_path = null;
        }

        // Handle background upload (private storage)
        if ($request->hasFile('background')) {
            // Delete old background
            if ($template->background_path) {
                Storage::delete($template->background_path);
            }
            $template->background_path = $request->file('background')->store('template-files/backgrounds');
        }

        // Update basic fields
        $template->name = $validated['name'];
        $template->page_size = $validated['page_size'] ?? 'A4';
        $template->is_default_invoice = $request->boolean('is_default_invoice');
        $template->is_default_quote = $request->boolean('is_default_quote');
        $template->save();

        $this->clearOtherDefaults($template);

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template bijgewerkt!');
    }

    /**
     * Er kan per documentsoort maar één standaard zijn: haal het vinkje bij
     * de overige templates weg voor de soorten die deze template claimt.
     */
    private function clearOtherDefaults(InvoiceTemplate $template): void
    {
        foreach ([InvoiceTemplate::TYPE_INVOICE, InvoiceTemplate::TYPE_QUOTE] as $type) {
            if ($template->isDefaultFor($type)) {
                InvoiceTemplate::where('id', '!=', $template->id)
                    ->update([InvoiceTemplate::defaultColumn($type) => false]);
            }
        }
    }

    /**
     * Show the template editor.
     */
    public function editor(InvoiceTemplate $template)
    {
        return view('templates.editor', compact('template'));
    }

    /**
     * Save field positions from editor.
     */
    public function savePositions(Request $request, InvoiceTemplate $template)
    {
        $validated = $request->validate([
            'field_positions' => 'required|array',
        ]);

        $template->field_positions = $validated['field_positions'];
        $template->save();

        return response()->json([
            'success' => true,
            'message' => 'Positions saved successfully'
        ]);
    }

    /**
     * Remove the specified template.
     */
    public function destroy(InvoiceTemplate $template)
    {
        // Don't allow deleting the default template if it's the only one
        if (InvoiceTemplate::count() === 1) {
            return back()->with('error', 'Kan de enige template niet verwijderen!');
        }

        // Delete associated files (private storage)
        if ($template->logo_path) {
            Storage::delete($template->logo_path);
        }
        if ($template->background_path) {
            Storage::delete($template->background_path);
        }

        $template->delete();

        // Was dit de standaard voor een soort? Dan een andere aanwijzen,
        // zodat er altijd een template klaarstaat.
        foreach ([InvoiceTemplate::TYPE_INVOICE, InvoiceTemplate::TYPE_QUOTE] as $type) {
            if (! $template->isDefaultFor($type)) {
                continue;
            }

            $newDefault = InvoiceTemplate::first();
            if ($newDefault) {
                $newDefault->setAsDefaultFor($type);
            }
        }

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template verwijderd!');
    }

    /**
     * Maak deze template standaard voor facturen, offertes of beide.
     */
    public function setDefault(Request $request, InvoiceTemplate $template)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in([
                InvoiceTemplate::TYPE_INVOICE,
                InvoiceTemplate::TYPE_QUOTE,
                'both',
            ])],
        ]);

        $types = $validated['type'] === 'both'
            ? [InvoiceTemplate::TYPE_INVOICE, InvoiceTemplate::TYPE_QUOTE]
            : [$validated['type']];

        foreach ($types as $type) {
            $template->setAsDefaultFor($type);
        }

        $labels = [
            InvoiceTemplate::TYPE_INVOICE => 'facturen',
            InvoiceTemplate::TYPE_QUOTE   => 'offertes',
        ];
        $what = $validated['type'] === 'both'
            ? 'facturen en offertes'
            : $labels[$validated['type']];

        return back()->with('success', "'{$template->name}' is nu de standaard voor {$what}.");
    }

    /**
     * Dupliceer een template, inclusief lay-out en afbeeldingen.
     */
    public function duplicate(InvoiceTemplate $template)
    {
        $copy = $template->replicate(['is_default_invoice', 'is_default_quote']);
        $copy->name = $this->copyName($template->name);

        // Een kopie neemt de standaard-status niet over
        $copy->is_default_invoice = false;
        $copy->is_default_quote = false;

        // Bestanden fysiek kopiëren: bij hergebruik van hetzelfde pad zou het
        // verwijderen van de ene template het logo van de andere weggooien.
        $copy->logo_path = $this->copyStoredFile($template->logo_path, 'template-files/logos');
        $copy->background_path = $this->copyStoredFile($template->background_path, 'template-files/backgrounds');

        $copy->save();

        return redirect()
            ->route('templates.index')
            ->with('success', "Template gedupliceerd als '{$copy->name}'.");
    }

    /**
     * Unieke naam voor een kopie: "Naam (kopie)", daarna "(kopie 2)" enz.
     */
    private function copyName(string $name): string
    {
        $base = preg_replace('/ \(kopie(?: \d+)?\)$/', '', $name);
        $candidate = $base . ' (kopie)';

        $counter = 2;
        while (InvoiceTemplate::where('name', $candidate)->exists()) {
            $candidate = $base . ' (kopie ' . $counter . ')';
            $counter++;
        }

        return mb_substr($candidate, 0, 255);
    }

    /**
     * Kopieer een opgeslagen bestand naar een nieuw pad in dezelfde map.
     */
    private function copyStoredFile(?string $path, string $directory): ?string
    {
        if (! $path || ! Storage::exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $target = $directory . '/' . \Illuminate\Support\Str::random(40) . ($extension ? '.' . $extension : '');

        Storage::copy($path, $target);

        return $target;
    }

    /**
     * Upload logo via AJAX vanuit de editor.
     */
    public function uploadLogo(Request $request, InvoiceTemplate $template)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        // Verwijder oud logo (private storage)
        if ($template->logo_path) {
            Storage::delete($template->logo_path);
        }

        $path = $request->file('logo')->store('template-files/logos');
        $template->logo_path = $path;
        $template->save();

        return response()->json([
            'success' => true,
            'url'     => route('templates.serve-file', [$template, 'logo']),
        ]);
    }

    /**
     * Generate test PDF with mockup data.
     */
    public function testPdf(Request $request, InvoiceTemplate $template)
    {
        $rows = $request->query('rows', 'short');

        $shortItems = [
            ['description' => 'Webhosting Premium', 'quantity' => 1, 'price' => 49.95],
            ['description' => 'E-mail accounts (10x)', 'quantity' => 10, 'price' => 2.50],
            ['description' => 'SSL Certificaat', 'quantity' => 1, 'price' => 29.95],
        ];

        $longItems = [];
        $products = [
            'Webhosting Premium', 'E-mail account', 'SSL Certificaat', 'Domeinnaam registratie',
            'Support (uur)', 'Backup service', 'Firewall configuratie', 'VPN instelling',
            'Office 365 licentie', 'Antivirus licentie', 'Remote monitoring', 'Server onderhoud',
            'Network scan', 'Helpdesk (uur)', 'Training (dagdeel)', 'Software update',
            'Security audit', 'Cloud opslag (100GB)', 'Printer instelling', 'Laptop configuratie',
            'Switch installatie', 'UPS batterij', 'Kabelwerk (m)', 'Patch panel',
            'Documentatie opstellen',
        ];
        foreach ($products as $i => $name) {
            $longItems[] = [
                'description' => $name,
                'quantity'    => rand(1, 10),
                'price'       => round(rand(500, 25000) / 100, 2),
            ];
        }

        $items = $rows === 'long' ? $longItems : $shortItems;
        $subtotal = array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $items));
        $tax      = round($subtotal * 0.21, 2);
        $total    = $subtotal + $tax;

        // Kredietbeperking in testdata (alleen als ingeschakeld in instellingen)
        $appSettings = \App\Models\AppSetting::get();
        $creditSurchargeData = [];
        if ($appSettings->credit_surcharge_enabled) {
            $surcharge = $appSettings->creditSurchargeAmount((float) $total);
            $creditSurchargeData = [
                'credit_surcharge'         => '€ ' . number_format($surcharge, 2, ',', '.'),
                'credit_surcharge_percent' => $appSettings->credit_surcharge_percent . '%',
                'total_with_surcharge'     => '€ ' . number_format($total + $surcharge, 2, ',', '.'),
            ];
        }

        $mockData = $creditSurchargeData + [
            'company_name'        => 'Hallo ICT B.V.',
            'company_address'     => 'Teststraat 123',
            'company_postal_code' => '1234 AB',
            'company_city'        => 'Amsterdam',
            'company_email'       => 'info@hallo.test',
            'company_phone'       => '+31 20 123 4567',
            'company_website'     => 'www.hallo.test',
            'company_kvk'         => '12345678',
            'company_vat'         => 'NL123456789B01',
            'company_iban'        => 'NL12 BANK 0123 4567 89',
            'company_bic'         => 'BANKNL2A',
            'company_bank'        => 'Testbank',
            'notes'               => 'Dit is een voorbeeld-opmerking bij het document.',
            'client_name'         => 'Test Klant B.V.',
            'client_address'      => 'Klantenweg 456',
            'client_postal_code'  => '5678 CD',
            'client_city'         => 'Rotterdam',
            'client_email'        => 'contact@testklant.nl',
            'invoice_number'  => $rows === 'long' ? 'INV-2026-002' : 'INV-2026-001',
            'invoice_date'    => now()->format('d-m-Y'),
            'due_date'        => now()->addDays(30)->format('d-m-Y'),
            'invoice_reference' => 'REF-12345',
            'items_table'     => $items,
            'subtotal'        => '€ ' . number_format($subtotal, 2, ',', '.'),
            'tax'             => '€ ' . number_format($tax, 2, ',', '.'),
            'total'           => '€ ' . number_format($total, 2, ',', '.'),
            'payment_terms'   => 'Betaling binnen 30 dagen op bankrekeningnummer NL12 BANK 0123 4567 89',
        ];

        // Generate PDF using InvoicePdfGenerator
        $pdfGenerator = new \App\Services\InvoicePdfGenerator();
        $pdf = $pdfGenerator->generateFromTemplate($template, $mockData);

        return $pdf->stream('test-invoice.pdf');
    }

    /**
     * Serve private template files (logo/background) via authenticated route.
     * BelongsToUser scope zorgt ervoor dat alleen eigen templates toegankelijk zijn.
     */
    public function serveFile(InvoiceTemplate $template, string $type)
    {
        $path = match ($type) {
            'logo'       => $template->logo_path,
            'background' => $template->background_path,
            default      => null,
        };

        if (! $path || ! Storage::exists($path)) {
            abort(404);
        }

        return Storage::response($path);
    }
}
