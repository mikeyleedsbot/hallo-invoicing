<?php

namespace App\Services;

use App\Models\InvoiceTemplate;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF Generator — handmatige paginering.
 *
 * Elke pagina is een position:relative blok van 210×297mm.
 * Alle velden (logo, tekst) zijn absoluut gepositioneerd op paginacoördinaten.
 * De tabel-rijen worden verdeeld over pagina's op basis van de tabelblok-hoogte.
 * Zo blijft de tabel exact op de gedefinieerde positie/breedte/hoogte op elke pagina.
 */
class InvoicePdfGenerator
{
    private float $cW = 850;
    private float $cH = 1200;
    private float $pW = 210;  // mm
    private float $pH = 297;  // mm

    private function x(float $px): float  { return round($px * $this->pW / $this->cW, 3); }
    private function y(float $px): float  { return round($px * $this->pH / $this->cH, 3); }
    private function pt(float $px): float { return round($px * 595 / $this->cW, 3); }

    public function generateFromTemplate(InvoiceTemplate $template, array $data)
    {
        $positions = $template->field_positions ?? [];
        if (empty($positions)) {
            $positions = self::getDefaultPositions();
        }
        $html = $this->build($positions, $data, $template);
        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    /**
     * Standaard veldposities — identiek aan loadDefaultLayout() in de editor JS.
     * Wordt gebruikt als field_positions null/leeg is zodat de PDF ook zonder
     * handmatig opslaan een nette lay-out heeft.
     */
    public static function getDefaultPositions(): array
    {
        return [
            'company_name'        => ['x' => 50,  'y' => 50,  'width' => 300, 'height' => 40,  'fontSize' => 18, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_address'     => ['x' => 50,  'y' => 100, 'width' => 300, 'height' => 25,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_postal_code' => ['x' => 50,  'y' => 128, 'width' => 80,  'height' => 25,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_city'        => ['x' => 135, 'y' => 128, 'width' => 215, 'height' => 25,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_email'       => ['x' => 50,  'y' => 158, 'width' => 300, 'height' => 20,  'fontSize' => 10, 'fontFamily' => 'inherit', 'align' => 'left'],
            'company_phone'       => ['x' => 50,  'y' => 183, 'width' => 300, 'height' => 20,  'fontSize' => 10, 'fontFamily' => 'inherit', 'align' => 'left'],
            'invoice_number'      => ['x' => 550, 'y' => 150, 'width' => 200, 'height' => 25,  'fontSize' => 12, 'fontFamily' => 'inherit', 'align' => 'left'],
            'invoice_date'        => ['x' => 550, 'y' => 180, 'width' => 200, 'height' => 25,  'fontSize' => 12, 'fontFamily' => 'inherit', 'align' => 'left'],
            'due_date'            => ['x' => 550, 'y' => 210, 'width' => 200, 'height' => 25,  'fontSize' => 12, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_name'         => ['x' => 50,  'y' => 250, 'width' => 300, 'height' => 30,  'fontSize' => 14, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_address'      => ['x' => 50,  'y' => 290, 'width' => 300, 'height' => 25,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_postal_code'  => ['x' => 50,  'y' => 318, 'width' => 80,  'height' => 25,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_city'         => ['x' => 135, 'y' => 318, 'width' => 215, 'height' => 25,  'fontSize' => 11, 'fontFamily' => 'inherit', 'align' => 'left'],
            'client_email'        => ['x' => 50,  'y' => 348, 'width' => 300, 'height' => 20,  'fontSize' => 10, 'fontFamily' => 'inherit', 'align' => 'left'],
            'items_table'         => ['x' => 50,  'y' => 420, 'width' => 700, 'height' => 300, 'fontSize' => 10, 'fontFamily' => 'inherit', 'align' => 'left'],
            'subtotal'            => ['x' => 550, 'y' => 750, 'width' => 200, 'height' => 25,  'fontSize' => 12, 'fontFamily' => 'inherit', 'align' => 'left'],
            'tax'                 => ['x' => 550, 'y' => 780, 'width' => 200, 'height' => 25,  'fontSize' => 12, 'fontFamily' => 'inherit', 'align' => 'left'],
            'total'               => ['x' => 550, 'y' => 810, 'width' => 200, 'height' => 30,  'fontSize' => 16, 'fontFamily' => 'inherit', 'align' => 'left'],
            'payment_terms'       => ['x' => 50,  'y' => 900, 'width' => 700, 'height' => 80,  'fontSize' => 10, 'fontFamily' => 'inherit', 'align' => 'left'],
        ];
    }

    private function build(array $pos, array $data, InvoiceTemplate $template): string
    {
        $tp = $pos['items_table'] ?? null;

        // Tabelblok (paginacoördinaten in mm)
        $tX = $tp ? $this->x($tp['x']      ?? 0)   : 12;
        $tY = $tp ? $this->y($tp['y']      ?? 0)   : 60;
        $tW = $tp ? $this->x($tp['width']  ?? 700) : 186;
        $tH = $tp ? $this->y($tp['height'] ?? 400) : 180;  // max hoogte tabel per pagina

        $tFontPt  = $tp ? $this->pt($tp['fontSize'] ?? 10) : 7;
        $tFontFam = $tp ? ($tp['fontFamily'] ?? 'Arial') : 'Arial';

        // Schatting: rijen per pagina op basis van font + padding
        // Rij hoogte ≈ fontPt * 1.4 (line-height) + 6pt padding = in mm: pt * 0.353mm/pt
        $rowHeightMm = ($tFontPt * 1.4 + 6) * 0.353;
        $headerRowMm = ($tFontPt * 1.4 + 6) * 0.353 * 1.2; // header iets groter
        $availableMm = $tH - $headerRowMm;
        $rowsPerPage = max(1, (int) floor($availableMm / $rowHeightMm));

        // Velden splitsen op basis van pageVisibility (editor-instelling heeft prioriteit).
        // Fallback: boven tabel Y = alle pagina's, onder tabel Y = alleen laatste pagina.
        $firstOnlyFields = [];  // alleen pagina 1
        $allPageFields   = [];  // elke pagina
        $lastOnlyFields  = [];  // alleen laatste pagina
        foreach ($pos as $id => $p) {
            if (in_array($id, ['logo', 'background', 'items_table'])) continue;
            $visibility = $p['pageVisibility'] ?? null;
            if ($visibility === 'first') {
                $firstOnlyFields[$id] = $p;
            } elseif ($visibility === 'last') {
                $lastOnlyFields[$id] = $p;
            } elseif ($visibility === 'all') {
                $allPageFields[$id] = $p;
            } else {
                // Geen instelling: gebruik positie-gebaseerde fallback
                if ($this->y($p['y'] ?? 0) < $tY) {
                    $allPageFields[$id] = $p;
                } else {
                    $lastOnlyFields[$id] = $p;
                }
            }
        }

        // Rijen opdelen in pagina-chunks
        $items  = $data['items_table'] ?? [];
        $chunks = is_array($items) && count($items) > 0
            ? array_chunk($items, $rowsPerPage)
            : [[]];

        $totalPages = max(1, count($chunks));

        // CSS
        $bgCss = '';
        if ($template->background_path) {
            $p = public_path('storage/' . $template->background_path);
            if (file_exists($p)) $bgCss = "background-image:url('$p');background-size:cover;background-repeat:no-repeat;";
        }

        $html = "<!DOCTYPE html><html><head><meta charset='utf-8'>
<style>
@page { margin:0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; }
.page {
    position: relative;
    width: {$this->pW}mm;
    height: {$this->pH}mm;
    overflow: hidden;
    page-break-after: always;
    $bgCss
}
.page:last-child { page-break-after: auto; }
.abs { position:absolute; overflow:hidden; word-wrap:break-word; }
.tabel-blok {
    position: absolute;
    left: {$tX}mm;
    top: {$tY}mm;
    width: {$tW}mm;
    height: {$tH}mm;
    overflow: hidden;
}
.items-table { width:100%; border-collapse:collapse; }
.items-table th, .items-table td { border:1px solid #ccc; padding:3px 5px; }
.items-table th { background:#f0f0f0; font-weight:bold; }
.items-table tr:nth-child(even) td { background:#fafafa; }
</style></head><body>";

        // Elke pagina opbouwen
        for ($page = 0; $page < $totalPages; $page++) {
            $isLast = ($page === $totalPages - 1);
            $html  .= '<div class="page">';

            // Logo (op elke pagina)
            if ($template->logo_path && isset($pos['logo'])) {
                $lp = public_path('storage/' . $template->logo_path);
                if (file_exists($lp)) {
                    $l = $pos['logo'];
                    $html .= sprintf(
                        '<img src="%s" class="abs" style="left:%smm;top:%smm;width:%smm;height:%smm;">',
                        $lp,
                        $this->x($l['x'] ?? 0), $this->y($l['y'] ?? 0),
                        $this->x($l['width'] ?? 150), $this->y($l['height'] ?? 80)
                    );
                }
            }

            // Velden die op alle pagina's verschijnen
            foreach ($allPageFields as $id => $p) {
                $value = $this->getValue($id, $p, $data);
                if ($value === null) continue;
                $html .= $this->renderAbs($p, $value);
            }

            // Velden die alleen op de eerste pagina verschijnen
            if ($page === 0) {
                foreach ($firstOnlyFields as $id => $p) {
                    $value = $this->getValue($id, $p, $data);
                    if ($value === null) continue;
                    $html .= $this->renderAbs($p, $value);
                }
            }

            // Velden die alleen op de laatste pagina verschijnen
            if ($isLast) {
                foreach ($lastOnlyFields as $id => $p) {
                    $value = $this->getValue($id, $p, $data);
                    if ($value === null) continue;
                    $html .= $this->renderAbs($p, $value);
                }
            }

            // Tabelblok met rijen van deze pagina
            $html .= "<div class='tabel-blok'>";
            $html .= "<table class='items-table' style='font-size:{$tFontPt}pt;font-family:{$tFontFam};'>";

            // Koptekst op elke pagina
            $html .= '<thead><tr>
                <th style="text-align:left;">Omschrijving</th>
                <th style="text-align:right;width:36px;">Aantal</th>
                <th style="text-align:right;width:52px;">Prijs</th>
                <th style="text-align:right;width:52px;">Totaal</th>
            </tr></thead><tbody>';

            foreach ($chunks[$page] as $item) {
                $total = ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
                $html .= sprintf('<tr>
                    <td>%s</td>
                    <td style="text-align:right;">%s</td>
                    <td style="text-align:right;">€&nbsp;%s</td>
                    <td style="text-align:right;">€&nbsp;%s</td>
                </tr>',
                    htmlspecialchars($item['description'] ?? ''),
                    number_format($item['quantity'] ?? 0, 0, ',', '.'),
                    number_format($item['price']    ?? 0, 2, ',', '.'),
                    number_format($total,               2, ',', '.')
                );
            }

            $html .= '</tbody></table></div>'; // einde tabel-blok
            $html .= '</div>'; // einde page
        }

        $html .= '</body></html>';
        return $html;
    }

    private function renderAbs(array $p, mixed $value): string
    {
        return sprintf(
            '<div class="abs" style="left:%smm;top:%smm;width:%smm;height:%smm;font-size:%spt;font-family:%s;text-align:%s;">%s</div>',
            $this->x($p['x']      ?? 0),
            $this->y($p['y']      ?? 0),
            $this->x($p['width']  ?? 200),
            $this->y($p['height'] ?? 30),
            $this->pt($p['fontSize'] ?? 12),
            $p['fontFamily'] ?? 'Arial',
            $p['align']      ?? 'left',
            nl2br(htmlspecialchars((string)$value))
        );
    }

    private function getValue(string $id, array $pos, array $data): mixed
    {
        if (str_starts_with($id, 'static_text_')) {
            return $pos['staticText'] ?? ($pos['label'] ?? '');
        }
        return $data[$id] ?? null;
    }
}
