<?php

namespace App\Services;

use App\Models\InvoiceTemplate;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF Generator met fixed tabelblok over meerdere pagina's.
 *
 * Strategie:
 *   @page margins worden ingesteld zodat de content area EXACT het tabelblok is.
 *   De tabel vloeit als normale content door die ruimte heen over meerdere pagina's.
 *   Alle andere velden worden via position:fixed op de VOLLEDIGE pagina geplaatst
 *   (met negatieve offset t.o.v. de content area) zodat ze op elke pagina herhalen.
 */
class InvoicePdfGenerator
{
    // Canvas = 850×1200px, A4 = 210×297mm = 595×842pt
    private float $cW = 850;
    private float $cH = 1200;
    private float $pW = 210;  // mm
    private float $pH = 297;  // mm

    private function mmX(float $px): float { return round($px * $this->pW / $this->cW, 3); }
    private function mmY(float $px): float { return round($px * $this->pH / $this->cH, 3); }
    private function pt(float $px): float  { return round($px * 595 / $this->cW, 3); }

    public function generateFromTemplate(InvoiceTemplate $template, array $data)
    {
        $html = $this->build($template->field_positions ?? [], $data, $template);
        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    private function build(array $pos, array $data, InvoiceTemplate $template): string
    {
        $tp = $pos['items_table'] ?? null; // tabel positie config

        // Tabelblok in mm (= content area van elke pagina)
        $tX = $tp ? $this->mmX($tp['x']      ?? 0)   : 12;
        $tY = $tp ? $this->mmY($tp['y']      ?? 0)   : 60;
        $tW = $tp ? $this->mmX($tp['width']  ?? 700) : 186;
        $tH = $tp ? $this->mmY($tp['height'] ?? 400) : 180;

        // Marges: content area = tabelblok
        $mTop    = $tY;
        $mLeft   = $tX;
        $mRight  = max(0, round($this->pW - $tX - $tW, 3));
        $mBottom = max(0, round($this->pH - $tY - $tH, 3));

        // Tabel font
        $tFontSize = $tp ? $this->pt($tp['fontSize'] ?? 10) : 7;
        $tFontFam  = $tp ? ($tp['fontFamily'] ?? 'Arial, sans-serif') : 'Arial, sans-serif';

        // Achtergrond
        $bgCss = '';
        if ($template->background_path) {
            $p = public_path('storage/' . $template->background_path);
            if (file_exists($p)) $bgCss = "background-image:url('$p');background-size:cover;";
        }

        // HTML opbouwen
        $html = <<<HTML
<!DOCTYPE html><html><head><meta charset="utf-8">
<style>
@page {
    margin-top:    {$mTop}mm;
    margin-left:   {$mLeft}mm;
    margin-right:  {$mRight}mm;
    margin-bottom: {$mBottom}mm;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; {$bgCss} }

/*
 * Elk fixed element wordt gepositioneerd t.o.v. de PAGINA (0,0 = linksboven pagina).
 * Omdat de content area begint op (mLeft, mTop), moeten we compenseren:
 *   left in content = pageLeft - mLeft
 *   top  in content = pageTop  - mTop
 */
.pf { position:fixed; overflow:visible; word-wrap:break-word; }

/* Artikelentabel */
.items-table { width:100%; border-collapse:collapse; page-break-inside:auto; }
.items-table thead { display:table-header-group; }
.items-table tr { page-break-inside:avoid; page-break-after:auto; }
.items-table th, .items-table td { border:1px solid #ccc; padding:3px 5px; }
.items-table th { background:#f0f0f0; font-weight:bold; }
.items-table tr:nth-child(even) td { background:#fafafa; }
</style>
</head><body>
HTML;

        // ── LOGO ──
        if ($template->logo_path && isset($pos['logo'])) {
            $lp = public_path('storage/' . $template->logo_path);
            if (file_exists($lp)) {
                $l = $pos['logo'];
                // Pagina-coördinaten → content-coördinaten: aftrekken van margin
                $lLeft = round($this->mmX($l['x'] ?? 0) - $mLeft, 3);
                $lTop  = round($this->mmY($l['y'] ?? 0) - $mTop,  3);
                $lW    = $this->mmX($l['width']  ?? 150);
                $lH    = $this->mmY($l['height'] ?? 80);
                $html .= "<img src=\"$lp\" style=\"position:fixed;left:{$lLeft}mm;top:{$lTop}mm;width:{$lW}mm;height:{$lH}mm;\">";
            }
        }

        // ── ALLE ANDERE VELDEN (fixed, herhalen op elke pagina) ──
        foreach ($pos as $id => $p) {
            if (in_array($id, ['logo', 'background', 'items_table'])) continue;

            $value = $this->getValue($id, $p, $data);
            if ($value === null || $value === '') continue;

            // Pagina-coördinaten → content-coördinaten
            $left = round($this->mmX($p['x'] ?? 0) - $mLeft, 3);
            $top  = round($this->mmY($p['y'] ?? 0) - $mTop,  3);
            $w    = $this->mmX($p['width']  ?? 200);
            $h    = $this->mmY($p['height'] ?? 30);
            $fs   = $this->pt($p['fontSize'] ?? 12);
            $ff   = $p['fontFamily'] ?? 'Arial, sans-serif';
            $al   = $p['align']      ?? 'left';

            $html .= sprintf(
                '<div class="pf" style="left:%smm;top:%smm;width:%smm;height:%smm;font-size:%spt;font-family:%s;text-align:%s;">%s</div>',
                $left, $top, $w, $h, $fs, $ff, $al,
                nl2br(htmlspecialchars((string)$value))
            );
        }

        // ── ARTIKELENTABEL (normale flow = vult content area = tabelblok) ──
        $items = $data['items_table'] ?? [];
        if ($tp && is_array($items) && count($items) > 0) {
            $html .= "<table class=\"items-table\" style=\"font-size:{$tFontSize}pt;font-family:{$tFontFam};\">";
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
