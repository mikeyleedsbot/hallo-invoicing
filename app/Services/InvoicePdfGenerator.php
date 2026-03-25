<?php

namespace App\Services;

use App\Models\InvoiceTemplate;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF Generator - drie secties aanpak:
 *
 * 1. HEADER  : position:relative container, hoogte = table Y
 *              Alle velden boven de tabel staan hier absoluut gepositioneerd
 *
 * 2. TABEL   : normale document flow in een wrapper met exact de breedte
 *              en linkermarge van het tabelblok. DomPDF laat dit automatisch
 *              doorlopen over pagina's binnen die breedte.
 *
 * 3. FOOTER  : position:relative container na de tabel
 *              Alle velden onder de tabel staan hier absoluut gepositioneerd,
 *              Y-offset is relatief aan het einde van de tabel.
 */
class InvoicePdfGenerator
{
    private float $cW = 850;
    private float $cH = 1200;
    private float $pW = 210; // mm A4
    private float $pH = 297; // mm A4

    private function x(float $px): float { return round($px * $this->pW / $this->cW, 3); }
    private function y(float $px): float { return round($px * $this->pH / $this->cH, 3); }
    private function pt(float $px): float { return round($px * 595 / $this->cW, 3); }

    public function generateFromTemplate(InvoiceTemplate $template, array $data)
    {
        $html = $this->build($template->field_positions ?? [], $data, $template);
        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    private function build(array $pos, array $data, InvoiceTemplate $template): string
    {
        $tp = $pos['items_table'] ?? null;

        // Tabelblok in mm (paginacoördinaten)
        $tX = $tp ? $this->x($tp['x']      ?? 0)   : 12;
        $tY = $tp ? $this->y($tp['y']      ?? 0)   : 60;
        $tW = $tp ? $this->x($tp['width']  ?? 700) : 186;
        $tH = $tp ? $this->y($tp['height'] ?? 400) : 180;

        $tFontSize = $tp ? $this->pt($tp['fontSize'] ?? 10) : 7;
        $tFontFam  = $tp ? ($tp['fontFamily'] ?? 'Arial') : 'Arial';

        // Hoogte footer sectie (onderkant tabel → onderkant pagina)
        $footerH = $this->pH - $tY - $tH;

        $bgCss = '';
        if ($template->background_path) {
            $p = public_path('storage/' . $template->background_path);
            if (file_exists($p)) $bgCss = "background-image:url('$p');background-size:cover;background-repeat:no-repeat;";
        }

        $html = "<!DOCTYPE html><html><head><meta charset='utf-8'>
<style>
@page { margin: 0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; width:{$this->pW}mm; $bgCss }

/* ── Secties ── */
.sec-header {
    position: relative;
    width: {$this->pW}mm;
    height: {$tY}mm;
    overflow: visible;
}
.sec-footer {
    position: relative;
    width: {$this->pW}mm;
    height: {$footerH}mm;
    overflow: visible;
}
.abs { position: absolute; overflow: visible; word-wrap: break-word; }

/* ── Tabel ── */
.tabel-wrap {
    margin-left: {$tX}mm;
    width: {$tW}mm;
}
.items-table { width:100%; border-collapse:collapse; page-break-inside:auto; }
.items-table thead { display:table-header-group; }
.items-table tr { page-break-inside:avoid; page-break-after:auto; }
.items-table th, .items-table td { border:1px solid #ccc; padding:3px 5px; }
.items-table th { background:#f0f0f0; font-weight:bold; }
.items-table tr:nth-child(even) td { background:#fafafa; }
</style></head><body>";

        // ── SECTIE 1: HEADER (velden boven de tabel) ──
        $html .= '<div class="sec-header">';

        // Logo
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

        foreach ($pos as $id => $p) {
            if (in_array($id, ['logo', 'background', 'items_table'])) continue;
            // Alleen velden die boven de tabel staan (Y < tabelY)
            if ($this->y($p['y'] ?? 0) >= $tY) continue;
            $value = $this->getValue($id, $p, $data);
            if ($value === null && !str_starts_with($id, 'static_text_')) continue;
            $html .= $this->renderAbs($p, $value ?? '', 0, 0);
        }

        $html .= '</div>'; // einde sec-header

        // ── SECTIE 2: TABEL ──
        $items = $data['items_table'] ?? [];
        if ($tp && is_array($items) && count($items) > 0) {
            $html .= "<div class='tabel-wrap'>";
            $html .= "<table class='items-table' style='font-size:{$tFontSize}pt;font-family:{$tFontFam};'>";
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
            $html .= '</tbody></table></div>';
        }

        // ── SECTIE 3: FOOTER (velden onder de tabel) ──
        $footerFields = [];
        foreach ($pos as $id => $p) {
            if (in_array($id, ['logo', 'background', 'items_table'])) continue;
            if ($this->y($p['y'] ?? 0) < $tY) continue; // al in header
            $footerFields[$id] = $p;
        }

        if (!empty($footerFields)) {
            $html .= '<div class="sec-footer">';
            foreach ($footerFields as $id => $p) {
                $value = $this->getValue($id, $p, $data);
                if ($value === null && !str_starts_with($id, 'static_text_')) continue;
                // Y relatief aan onderkant tabel
                $html .= $this->renderAbs($p, $value ?? '', 0, $tY + $tH);
            }
            $html .= '</div>';
        }

        $html .= '</body></html>';
        return $html;
    }

    private function renderAbs(array $p, mixed $value, float $offsetX, float $offsetY): string
    {
        $left  = round($this->x($p['x']      ?? 0) - $offsetX, 3);
        $top   = round($this->y($p['y']      ?? 0) - $offsetY, 3);
        $w     = $this->x($p['width']  ?? 200);
        $h     = $this->y($p['height'] ?? 30);
        $fs    = $this->pt($p['fontSize'] ?? 12);
        $ff    = $p['fontFamily'] ?? 'Arial';
        $align = $p['align']      ?? 'left';

        return sprintf(
            '<div class="abs" style="left:%smm;top:%smm;width:%smm;height:%smm;font-size:%spt;font-family:%s;text-align:%s;">%s</div>',
            $left, $top, $w, $h, $fs, $ff, $align,
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
