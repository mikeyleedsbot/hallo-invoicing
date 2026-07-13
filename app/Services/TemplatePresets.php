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
        return [
            'company_name'        => ['x' => 50,  'y' => 50,  'width' => 400, 'height' => 36,  'fontSize' => 20, 'fontFamily' => 'inherit', 'align' => 'left', 'fontWeight' => 'bold'],
            'company_address'     => ['x' => 50,  'y' => 96,  'width' => 300, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_postal_code' => ['x' => 50,  'y' => 122, 'width' => 80,  'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_city'        => ['x' => 135, 'y' => 122, 'width' => 215, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_email'       => ['x' => 50,  'y' => 148, 'width' => 300, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_phone'       => ['x' => 50,  'y' => 174, 'width' => 300, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],

            'static_text_lbl_invoice_number' => ['x' => 400, 'y' => 150, 'width' => 145, 'height' => 22, 'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Factuurnummer:', 'label' => 'Factuurnummer:'],
            'static_text_lbl_invoice_date'   => ['x' => 400, 'y' => 176, 'width' => 145, 'height' => 22, 'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Factuurdatum:', 'label' => 'Factuurdatum:'],
            'static_text_lbl_due_date'       => ['x' => 400, 'y' => 202, 'width' => 145, 'height' => 22, 'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Vervaldatum:', 'label' => 'Vervaldatum:'],
            'invoice_number'      => ['x' => 555, 'y' => 150, 'width' => 195, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'invoice_date'        => ['x' => 555, 'y' => 176, 'width' => 195, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'due_date'            => ['x' => 555, 'y' => 202, 'width' => 195, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],

            'static_text_lbl_client' => ['x' => 50, 'y' => 260, 'width' => 300, 'height' => 22, 'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left', 'fontWeight' => 'bold', 'staticText' => 'Aan:', 'label' => 'Aan:'],
            'client_name'         => ['x' => 50,  'y' => 286, 'width' => 300, 'height' => 24,  'fontSize' => 12, 'fontFamily' => 'inherit', 'align' => 'left', 'fontWeight' => 'bold'],
            'client_address'      => ['x' => 50,  'y' => 314, 'width' => 300, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_postal_code'  => ['x' => 50,  'y' => 340, 'width' => 80,  'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_city'         => ['x' => 135, 'y' => 340, 'width' => 215, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_email'        => ['x' => 50,  'y' => 366, 'width' => 300, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],

            'items_table'         => ['x' => 50,  'y' => 430, 'width' => 700, 'height' => 300, 'fontSize' => 10, 'fontFamily' => 'inherit', 'align' => 'left'],

            'static_text_lbl_subtotal' => ['x' => 380, 'y' => 755, 'width' => 165, 'height' => 22, 'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Totaal excl. BTW:', 'label' => 'Totaal excl. BTW:'],
            'static_text_lbl_tax'      => ['x' => 380, 'y' => 781, 'width' => 165, 'height' => 22, 'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'BTW:', 'label' => 'BTW:'],
            'static_text_lbl_total'    => ['x' => 380, 'y' => 812, 'width' => 165, 'height' => 26, 'fontSize' => 13, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold', 'staticText' => 'Totaal incl. BTW:', 'label' => 'Totaal incl. BTW:'],
            'subtotal'            => ['x' => 555, 'y' => 755, 'width' => 195, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'right'],
            'tax'                 => ['x' => 555, 'y' => 781, 'width' => 195, 'height' => 22,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'right'],
            'total'               => ['x' => 555, 'y' => 812, 'width' => 195, 'height' => 26,  'fontSize' => 13, 'fontFamily' => 'inherit', 'align' => 'right', 'fontWeight' => 'bold'],

            'payment_terms'       => ['x' => 50,  'y' => 890, 'width' => 700, 'height' => 80,  'fontSize' => 10, 'fontFamily' => 'inherit', 'align' => 'left'],
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
            'company_name'        => ['y' => 40,  'fontSize' => 22, 'height' => 40, 'color' => '#ffffff'],
            'company_address'     => ['y' => 92,  'color' => '#dbeafe'],
            'company_postal_code' => ['y' => 118, 'color' => '#dbeafe'],
            'company_city'        => ['y' => 118, 'color' => '#dbeafe'],
            'company_email'       => ['y' => 144, 'color' => '#dbeafe'],
            'company_phone'       => ['y' => 170, 'color' => '#dbeafe'],

            // Documentgegevens ook in de band, rechts
            'static_text_lbl_invoice_number' => ['y' => 92,  'color' => '#bfdbfe'],
            'static_text_lbl_invoice_date'   => ['y' => 118, 'color' => '#bfdbfe'],
            'static_text_lbl_due_date'       => ['y' => 144, 'color' => '#bfdbfe'],
            'invoice_number'      => ['y' => 92,  'color' => '#ffffff'],
            'invoice_date'        => ['y' => 118, 'color' => '#ffffff'],
            'due_date'            => ['y' => 144, 'color' => '#ffffff'],

            // Klantblok onder de band
            'static_text_lbl_client' => ['y' => 250, 'color' => '#1e3a8a'],
            'client_name'         => ['y' => 276],
            'client_address'      => ['y' => 304],
            'client_postal_code'  => ['y' => 330],
            'client_city'         => ['y' => 330],
            'client_email'        => ['y' => 356],

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
            'company_name'        => ['fontSize' => 22, 'height' => 40, 'color' => '#c2410c'],
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
            'company_name'        => ['fontSize' => 26, 'height' => 46],
            'static_text_lbl_invoice_number' => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'static_text_lbl_invoice_date'   => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'static_text_lbl_due_date'       => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'static_text_lbl_client'         => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'client_email'                   => ['color' => '#6b7280'],
            'items_table' => ['headerBg' => '#ffffff', 'headerColor' => '#111827', 'borderStyle' => 'minimal', 'borderColor' => '#111827', 'zebra' => false],
            'static_text_lbl_subtotal' => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'static_text_lbl_tax'      => ['fontWeight' => 'normal', 'color' => '#6b7280'],
            'static_text_lbl_total'    => ['fontSize' => 14],
            'total'                    => ['fontSize' => 14],
            'payment_terms'            => ['color' => '#6b7280'],
        ]);

        // Dunne zwarte lijn boven de betalingsvoorwaarden
        $positions['static_rect_divider'] = ['x' => 50, 'y' => 868, 'width' => 700, 'height' => 3, 'backgroundColor' => '#111827', 'staticText' => ' ', 'label' => 'Lijn'];

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
            'company_name'        => ['fontSize' => 22, 'height' => 40, 'color' => '#065f46'],
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
