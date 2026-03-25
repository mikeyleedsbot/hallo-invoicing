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
        $html = $this->build($template->field_positions ?? [], $data, $template);
        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
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

        // Velden splitsen: boven tabel = op elke pagina, onder tabel = alleen laatste pagina
        $aboveFields = [];
        $belowFields = [];
        foreach ($pos as $id => $p) {
            if (in_array($id, ['logo', 'background', 'items_table'])) continue;
            if ($this->y($p['y'] ?? 0) < $tY) {
                $aboveFields[$id] = $p;
            } else {
                $belowFields[$id] = $p;
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

            // Velden boven tabel (op elke pagina)
            foreach ($aboveFields as $id => $p) {
                $value = $this->getValue($id, $p, $data);
                if ($value === null) continue;
                $html .= $this->renderAbs($p, $value);
            }

            // Velden onder tabel (alleen op laatste pagina)
            if ($isLast) {
                foreach ($belowFields as $id => $p) {
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
