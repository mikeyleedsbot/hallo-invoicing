<?php

namespace App\Http\Controllers;

use App\Models\InvoiceTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TemplateController extends Controller
{
    /**
     * Display a listing of templates.
     */
    public function index()
    {
        $templates = InvoiceTemplate::orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template.
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
            'background' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
            'page_size' => 'nullable|string|in:A4,Letter',
        ]);

        // Handle logo upload
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        // Handle background upload
        $backgroundPath = null;
        if ($request->hasFile('background')) {
            $backgroundPath = $request->file('background')->store('backgrounds', 'public');
        }

        $template = InvoiceTemplate::create([
            'name' => $validated['name'],
            'is_default' => $request->boolean('is_default'),
            'logo_path' => $logoPath,
            'background_path' => $backgroundPath,
            'page_size' => $validated['page_size'] ?? 'A4',
            'field_positions' => null, // Will be set in editor
        ]);

        // If marked as default, unset other defaults
        if ($template->is_default) {
            InvoiceTemplate::where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

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
            'is_default' => 'boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
            'background' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
            'page_size' => 'nullable|string|in:A4,Letter',
            'remove_logo' => 'boolean',
            'remove_background' => 'boolean',
        ]);

        // Handle logo removal
        if ($request->boolean('remove_logo') && $template->logo_path) {
            Storage::disk('public')->delete($template->logo_path);
            $template->logo_path = null;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($template->logo_path) {
                Storage::disk('public')->delete($template->logo_path);
            }
            $template->logo_path = $request->file('logo')->store('logos', 'public');
        }

        // Handle background removal
        if ($request->boolean('remove_background') && $template->background_path) {
            Storage::disk('public')->delete($template->background_path);
            $template->background_path = null;
        }

        // Handle background upload
        if ($request->hasFile('background')) {
            // Delete old background
            if ($template->background_path) {
                Storage::disk('public')->delete($template->background_path);
            }
            $template->background_path = $request->file('background')->store('backgrounds', 'public');
        }

        // Update basic fields
        $template->name = $validated['name'];
        $template->page_size = $validated['page_size'] ?? 'A4';
        $template->is_default = $request->boolean('is_default');
        $template->save();

        // If marked as default, unset other defaults
        if ($template->is_default) {
            InvoiceTemplate::where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template bijgewerkt!');
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
        if ($template->is_default && InvoiceTemplate::count() === 1) {
            return back()->with('error', 'Kan de enige template niet verwijderen!');
        }

        // Delete associated files
        if ($template->logo_path) {
            Storage::disk('public')->delete($template->logo_path);
        }
        if ($template->background_path) {
            Storage::disk('public')->delete($template->background_path);
        }

        $template->delete();

        // If this was the default, make another one default
        if ($template->is_default) {
            $newDefault = InvoiceTemplate::first();
            if ($newDefault) {
                $newDefault->setAsDefault();
            }
        }

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template verwijderd!');
    }

    /**
     * Set a template as default.
     */
    public function setDefault(InvoiceTemplate $template)
    {
        $template->setAsDefault();

        return back()->with('success', 'Template is nu de standaard!');
    }

    /**
     * Upload logo via AJAX vanuit de editor.
     */
    public function uploadLogo(Request $request, InvoiceTemplate $template)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        // Verwijder oud logo
        if ($template->logo_path) {
            Storage::disk('public')->delete($template->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $template->logo_path = $path;
        $template->save();

        return response()->json([
            'success' => true,
            'url'     => asset('storage/' . $path),
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

        $mockData = [
            'company_name'    => 'Hallo ICT B.V.',
            'company_address' => "Teststraat 123\n1234 AB Amsterdam",
            'company_email'   => 'info@hallo.test',
            'company_phone'   => '+31 20 123 4567',
            'client_name'     => 'Test Klant B.V.',
            'client_address'  => "Klantenweg 456\n5678 CD Rotterdam",
            'client_email'    => 'contact@testklant.nl',
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
}
