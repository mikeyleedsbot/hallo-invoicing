<?php

namespace App\Services;

use App\Models\InvoiceTemplate;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF strategie:
 *
 * - @page margins = tabelblok grenzen → content area = exact het tabelblok
 * - Tabel = normale HTML flow binnen die content area, loopt door over pagina's
 * - Alle andere velden = position:fixed met DIRECTE paginacoördinaten (geen offset!)
 *   In DomPDF is position:fixed altijd t.o.v. de pagina zelf (0,0 = linksboven pagina)
 */
class InvoicePdfGenerator
{
    private float $cW = 850;   // canvas breedte px
    private float $cH = 1200;  // canvas hoogte px
    private float $pW = 210;   // A4 breedte mm
    private float $pH = 297;   // A4 hoogte mm

    private function mmX(float $px): string { return round($px * $this->pW / $this->cW, 2) . 'mm'; }
    private function mmY(float $px): string { return round($px * $this->pH / $this->cH, 2) . 'mm'; }
    private function fmmX(float $px): float { return round($px * $this->pW / $this->cW, 2); }
    private function fmmY(float $px): float { return round($px * $this->pH / $this->cH, 2); }
    private function pt(float $px): string  { return round($px * 595 / $this->cW, 2)  . 'pt'; }

    public function generateFromTemplate(InvoiceTemplate $template, array $data)
    {
        $html = $this->build($template->field_positions ?? [], $data, $template);
        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    private function build(array $pos, array $data, InvoiceTemplate $template): string
    {
        $tp = $pos['items_table'] ?? null;

        // Tabelblok in mm (paginacoördinaten)
        $tX = $tp ? $this->fmmX($tp['x']      ?? 0)   : 12;
        $tY = $tp ? $this->fmmY($tp['y']      ?? 0)   : 60;
        $tW = $tp ? $this->fmmX($tp['width']  ?? 700) : 186;
        $tH = $tp ? $this->fmmY($tp['height'] ?? 400) : 180;

        // @page margins zodat content area = tabelblok
        $mTop    = $tY;
        $mLeft   = $tX;
        $mRight  = max(0, $this->pW - $tX - $tW);
        $mBottom = max(0, $this->pH - $tY - $tH);

        $tFontSize = $tp ? $this->pt($tp['fontSize'] ?? 10) : '7pt';
        $tFontFam  = $tp ? ($tp['fontFamily'] ?? 'Arial') : 'Arial';

        $bgCss = '';
        if ($template->background_path) {
            $p = public_path('storage/' . $template->background_path);
            if (file_exists($p)) $bgCss = "background-image:url('$p');background-size:cover;";
        }

        $html = "<!DOCTYPE html><html><head><meta charset='utf-8'>
<style>
@page {
    margin-top:    {$mTop}mm;
    margin-left:   {$mLeft}mm;
    margin-right:  {$mRight}mm;
    margin-bottom: {$mBottom}mm;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; $bgCss }

/* Fixed = t.o.v. PAGINA (0,0 = linksboven pagina) — herhaalt op elke pagina */
.pf {
    position: fixed;
    overflow: visible;
    word-wrap: break-word;
}

/* Tabel vloeit door in de content area (= tabelblok) */
.items-table { width:100%; border-collapse:collapse; page-break-inside:auto; }
.items-table thead { display:table-header-group; }
.items-table tr { page-break-inside:avoid; }
.items-table th, .items-table td { border:1px solid #ccc; padding:3px 5px; }
.items-table th { background:#f0f0f0; font-weight:bold; }
.items-table tr:nth-child(even) td { background:#fafafa; }
</style>
</head><body>";

        // ── LOGO (fixed, directe paginacoördinaten) ──
        if ($template->logo_path && isset($pos['logo'])) {
            $lp = public_path('storage/' . $template->logo_path);
            if (file_exists($lp)) {
                $l = $pos['logo'];
                $html .= sprintf(
                    '<img src="%s" class="pf" style="left:%s;top:%s;width:%s;height:%s;">',
                    $lp,
                    $this->mmX($l['x']      ?? 0),
                    $this->mmY($l['y']      ?? 0),
                    $this->mmX($l['width']  ?? 150),
                    $this->mmY($l['height'] ?? 80)
                );
            }
        }

        // ── ALLE ANDERE VELDEN (fixed, directe paginacoördinaten) ──
        foreach ($pos as $id => $p) {
            if (in_array($id, ['logo', 'background', 'items_table'])) continue;

            $value = $this->getValue($id, $p, $data);
            if ($value === null || $value === '') continue;

            $html .= sprintf(
                '<div class="pf" style="left:%s;top:%s;width:%s;height:%s;font-size:%s;font-family:%s;text-align:%s;">%s</div>',
                $this->mmX($p['x']      ?? 0),
                $this->mmY($p['y']      ?? 0),
                $this->mmX($p['width']  ?? 200),
                $this->mmY($p['height'] ?? 30),
                $this->pt($p['fontSize'] ?? 12),
                $p['fontFamily'] ?? 'Arial',
                $p['align']      ?? 'left',
                nl2br(htmlspecialchars((string)$value))
            );
        }

        // ── ARTIKELENTABEL (normale flow = vult content area op elke pagina) ──
        $items = $data['items_table'] ?? [];
        if ($tp && is_array($items) && count($items) > 0) {
            $html .= "<table class='items-table' style='font-size:{$tFontSize};font-family:{$tFontFam};'>";
            $html .= '<thead><tr>
                <th style="text-align:left;">Omschrijving</th>
                <th style="text-align:right;width:36px;">Aantal</th>
                <th style="text-align:right;width:52px;">Prijs</th>
                <th style="text-align:right;width:52px;">Totaal</th>
            </tr></thead><tbody>';

            foreach ($items as $item) {
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
            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';
        return $html;
    }

    private function getValue(string $id, array $pos, array $data): mixed
    {
        if (str_starts_with($id, 'static_text_')) {
            return $pos['staticText'] ?? ($pos['label'] ?? '');
        }
        return $data[$id] ?? null;
    }
}
