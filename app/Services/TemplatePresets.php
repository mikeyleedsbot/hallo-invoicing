<?php

namespace App\Services;

/**
 * Kant-en-klare stijlvarianten voor factuur/offerte-templates.
 *
 * Elke preset bevat complete field_positions (incl. kleuren, decoratieve
 * vlakken en tabelstijl) zodat een nieuwe template er direct verzorgd
 * uitziet — gebruiker hoeft alleen nog logo/briefpapier toe te voegen.
 *
 * Wordt gebruikt door:
 * - TemplateController@create/store (sjabloonkeuze bij aanmaken)
 * - templates/editor.blade.php (stijl toepassen in de editor, via @json)
 * - InvoicePdfGenerator::getDefaultPositions() (fallback = 'klassiek')
 */
class TemplatePresets
{
    public static function all(): array
    {
        return [
            'klassiek'     => self::klassiek(),
            'modern_blauw' => self::modernBlauw(),
            'terracotta'   => self::terracotta(),
            'minimalist'   => self::minimalist(),
            'fris_groen'   => self::frisGroen(),
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function positions(string $key): array
    {
        return self::all()[$key]['positions'] ?? self::klassiek()['positions'];
    }

    /**
     * Basisindeling die alle presets delen (strak uitgelijnd, regelafstand 26px).
     * Presets passen hier kleuren/posities/tabelstijl op aan.
     */
    private static function base(): array
    {
        // Canvas 850×1200; marge links én rechts 50 → content loopt van x=50 tot x=800.
        return [
            'company_name'        => ['x' => 50,  'y' => 50,  'width' => 450, 'height' => 48,  'fontSize' => 28, 'fontFamily' => 'inherit', 'align' => 'left', 'fontWeight' => 'bold'],
            'company_address'     => ['x' => 50,  'y' => 98,  'width' => 320, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_postal_code' => ['x' => 50,  'y' => 126, 'width' => 90,  'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_city'        => ['x' => 145, 'y' => 126, 'width' => 225, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_email'       => ['x' => 50,  'y' => 154, 'width' => 320, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_phone'       => ['x' => 50,  'y' => 182, 'width' => 320, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],

            'static_text_lbl_invoice_number' => ['x' => 450, 'y' => 154, 'width' => 145, 'height' => 24, 'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Factuurnummer:', 'label' => 'Factuurnummer:'],
            'static_text_lbl_invoice_date'   => ['x' => 450, 'y' => 182, 'width' => 145, 'height' => 24, 'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Factuurdatum:', 'label' => 'Factuurdatum:'],
            'static_text_lbl_due_date'       => ['x' => 450, 'y' => 210, 'width' => 145, 'height' => 24, 'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Vervaldatum:', 'label' => 'Vervaldatum:'],
            'invoice_number'      => ['x' => 605, 'y' => 154, 'width' => 195, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'invoice_date'        => ['x' => 605, 'y' => 182, 'width' => 195, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'due_date'            => ['x' => 605, 'y' => 210, 'width' => 195, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],

            'static_text_lbl_client' => ['x' => 50, 'y' => 272, 'width' => 320, 'height' => 24, 'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left', 'fontWeight' => 'bold', 'staticText' => 'Aan:', 'label' => 'Aan:'],
            'client_name'         => ['x' => 50,  'y' => 300, 'width' => 320, 'height' => 26,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left', 'fontWeight' => 'bold'],
            'client_address'      => ['x' => 50,  'y' => 330, 'width' => 320, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_postal_code'  => ['x' => 50,  'y' => 358, 'width' => 90,  'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_city'         => ['x' => 145, 'y' => 358, 'width' => 225, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_email'        => ['x' => 50,  'y' => 386, 'width' => 320, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],

            'items_table'         => ['x' => 50,  'y' => 440, 'width' => 750, 'height' => 300, 'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],

            'static_text_lbl_subtotal' => ['x' => 430, 'y' => 765, 'width' => 165, 'height' => 24, 'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Totaal excl. BTW:', 'label' => 'Totaal excl. BTW:'],
            'static_text_lbl_tax'      => ['x' => 430, 'y' => 793, 'width' => 165, 'height' => 24, 'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'BTW:', 'label' => 'BTW:'],
            'static_text_lbl_total'    => ['x' => 400, 'y' => 824, 'width' => 195, 'height' => 30, 'fontSize' => 18, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Totaal incl. BTW:', 'label' => 'Totaal incl. BTW:'],
            'subtotal'            => ['x' => 605, 'y' => 765, 'width' => 195, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'right'],
            'tax'                 => ['x' => 605, 'y' => 793, 'width' => 195, 'height' => 24,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'right'],
            'total'               => ['x' => 605, 'y' => 824, 'width' => 195, 'height' => 30,  'fontSize' => 18, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold'],

            // Verplichte vermelding bij verlegde BTW. Verschijnt alleen op facturen
            // met BTW verlegd, maar is net als elk ander veld te verplaatsen en
            // op te maken. Positie komt overeen met waar de vermelding voorheen
            // vast stond, tussen het totaal en de betalingsvoorwaarden.
            'reverse_charge_note' => ['x' => 50,  'y' => 862, 'width' => 734, 'height' => 30,  'fontSize' => 13, 'fontFamily' => 'inherit', 'align' => 'left', 'fontWeight' => 'bold', 'color' => '#78350f', 'backgroundColor' => '#fffbeb', 'pageVisibility' => 'last', 'label' => 'BTW verlegd-vermelding'],

            'payment_terms'       => ['x' => 50,  'y' => 900, 'width' => 750, 'height' => 90,  'fontSize' => 14, 'fontFamily' => 'inherit', 'align' => 'left'],
        ];
    }

    /** Hulpfunctie: past overrides toe op (een subset van) de basisvelden. */
    private static function withOverrides(array $overrides): array
    {
        $positions = self::base();
        foreach ($overrides as $field => $props) {
            $positions[$field] = array_merge($positions[$field] ?? [], $props);
        }
        return $positions;
    }

    private static function klassiek(): array
    {
        return [
            'name'        => 'Klassiek',
            'description' => 'Tijdloos zwart-wit met grijze tabelkop. Past overal bij.',
            'colors'      => ['#111827', '#6b7280', '#f0f0f0'],
            'positions'   => self::withOverrides([
                'items_table' => ['headerBg' => '#f0f0f0', 'headerColor' => '#111827', 'borderStyle' => 'full', 'borderColor' => '#cccccc', 'zebra' => true],
            ]),
        ];
    }

    private static function modernBlauw(): array
    {
        $positions = self::withOverrides([
            // Bedrijfsblok in witte tekst binnen de blauwe band
            'company_name'        => ['y' => 38,  'height' => 50, 'color' => '#ffffff'],
            'company_address'     => ['y' => 94,  'color' => '#dbeafe'],
            'company_postal_code' => ['y' => 122, 'color' => '#dbeafe'],
            'company_city'        => ['y' => 122, 'color' => '#dbeafe'],
            'company_email'       => ['y' => 150, 'color' => '#dbeafe'],
            'company_phone'       => ['y' => 178, 'color' => '#dbeafe'],

            // Documentgegevens ook in de band, rechts
            'static_text_lbl_invoice_number' => ['y' => 94,  'color' => '#bfdbfe'],
            'static_text_lbl_invoice_date'   => ['y' => 122, 'color' => '#bfdbfe'],
            'static_text_lbl_due_date'       => ['y' => 150, 'color' => '#bfdbfe'],
            'invoice_number'      => ['y' => 94,  'color' => '#ffffff'],
            'invoice_date'        => ['y' => 122, 'color' => '#ffffff'],
            'due_date'            => ['y' => 150, 'color' => '#ffffff'],

            // Klantblok onder de band
            'static_text_lbl_client' => ['y' => 262, 'color' => '#1e3a8a'],
            'client_name'         => ['y' => 290],
            'client_address'      => ['y' => 320],
            'client_postal_code'  => ['y' => 348],
            'client_city'         => ['y' => 348],
            'client_email'        => ['y' => 376],

            'items_table' => ['headerBg' => '#1e3a8a', 'headerColor' => '#ffffff', 'borderStyle' => 'horizontal', 'borderColor' => '#dbeafe', 'zebra' => true],

            'static_text_lbl_subtotal' => ['color' => '#1e3a8a'],
            'static_text_lbl_tax'      => ['color' => '#1e3a8a'],
            'static_text_lbl_total'    => ['color' => '#1e3a8a'],
            'total'                    => ['color' => '#1e3a8a'],
            'payment_terms'            => ['color' => '#6b7280'],
        ]);

        // Blauwe headerband (achter de tekst, op elke pagina)
        $positions = ['static_rect_header' => ['x' => 0, 'y' => 0, 'width' => 850, 'height' => 210, 'backgroundColor' => '#1e3a8a', 'staticText' => ' ', 'label' => 'Kleurvlak']] + $positions;

        return [
            'name'        => 'Modern Blauw',
            'description' => 'Donkerblauwe headerband met witte tekst en blauwe accenten.',
            'colors'      => ['#1e3a8a', '#bfdbfe', '#ffffff'],
            'positions'   => $positions,
        ];
    }

    private static function terracotta(): array
    {
        $positions = self::withOverrides([
            'company_name'        => ['color' => '#c2410c'],
            'static_text_lbl_invoice_number' => ['color' => '#9a3412'],
            'static_text_lbl_invoice_date'   => ['color' => '#9a3412'],
            'static_text_lbl_due_date'       => ['color' => '#9a3412'],
            'static_text_lbl_client'         => ['color' => '#9a3412'],
            'items_table' => ['headerBg' => '#ffedd5', 'headerColor' => '#7c2d12', 'borderStyle' => 'horizontal', 'borderColor' => '#fed7aa', 'zebra' => false],
            'static_text_lbl_subtotal' => ['color' => '#9a3412'],
            'static_text_lbl_tax'      => ['color' => '#9a3412'],
            'static_text_lbl_total'    => ['color' => '#c2410c'],
            'total'                    => ['color' => '#c2410c'],
            'payment_terms'            => ['color' => '#78716c'],
        ]);

        // Terracotta accentbalk over de volledige linkerrand
        $positions = ['static_rect_bar' => ['x' => 0, 'y' => 0, 'width' => 14, 'height' => 1200, 'backgroundColor' => '#c2410c', 'staticText' => ' ', 'label' => 'Kleurvlak']] + $positions;

        return [
            'name'        => 'Terracotta',
            'description' => 'Warme oranje accentbalk met zachte tinten. Vriendelijk en eigentijds.',
            'colors'      => ['#c2410c', '#ffedd5', '#7c2d12'],
            'positions'   => $positions,
        ];
    }

    private static function minimalist(): array
    {
        $positions = self::withOverrides([
            'company_name'        => ['fontSize' => 32, 'height' => 56],
            'static_text_lbl_invoice_number' => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'static_text_lbl_invoice_date'   => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'static_text_lbl_due_date'       => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'static_text_lbl_client'         => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'client_email'                   => ['color' => '#6b7280'],
            'items_table' => ['headerBg' => '#ffffff', 'headerColor' => '#111827', 'borderStyle' => 'minimal', 'borderColor' => '#111827', 'zebra' => false],
            'static_text_lbl_subtotal' => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'static_text_lbl_tax'      => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'payment_terms'            => ['color' => '#6b7280'],
        ]);

        // Dunne zwarte lijn boven de betalingsvoorwaarden
        $positions['static_rect_divider'] = ['x' => 50, 'y' => 878, 'width' => 750, 'height' => 3, 'backgroundColor' => '#111827', 'staticText' => ' ', 'label' => 'Lijn'];

        return [
            'name'        => 'Minimalistisch',
            'description' => 'Veel wit, grote typografie, subtiele lijnen. Rustig en chic.',
            'colors'      => ['#111827', '#6b7280', '#ffffff'],
            'positions'   => $positions,
        ];
    }

    private static function frisGroen(): array
    {
        $positions = self::withOverrides([
            'company_name'        => ['color' => '#065f46'],
            'static_text_lbl_invoice_number' => ['color' => '#047857'],
            'static_text_lbl_invoice_date'   => ['color' => '#047857'],
            'static_text_lbl_due_date'       => ['color' => '#047857'],
            'static_text_lbl_client'         => ['color' => '#047857'],
            'items_table' => ['headerBg' => '#d1fae5', 'headerColor' => '#065f46', 'borderStyle' => 'horizontal', 'borderColor' => '#a7f3d0', 'zebra' => true],
            'static_text_lbl_subtotal' => ['color' => '#047857'],
            'static_text_lbl_tax'      => ['color' => '#047857'],
            'static_text_lbl_total'    => ['color' => '#065f46'],
            'total'                    => ['color' => '#065f46'],
            'payment_terms'            => ['color' => '#6b7280'],
        ]);

        // Smalle groene band langs de bovenrand
        $positions = ['static_rect_top' => ['x' => 0, 'y' => 0, 'width' => 850, 'height' => 10, 'backgroundColor' => '#059669', 'staticText' => ' ', 'label' => 'Kleurvlak']] + $positions;

        return [
            'name'        => 'Fris Groen',
            'description' => 'Groene accenten met zachte tabelkop. Fris en betrouwbaar.',
            'colors'      => ['#059669', '#d1fae5', '#065f46'],
            'positions'   => $positions,
        ];
    }
}
