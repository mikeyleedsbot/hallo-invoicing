<?php

namespace App\Services;

use App\Models\InvoiceTemplate;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfGenerator
{
    private float $canvasWidth  = 850;
    private float $canvasHeight = 1200;
    private float $a4Width      = 210; // mm
    private float $a4Height     = 297; // mm

    private function scaleX(float $px): float { return round($px * $this->a4Width  / $this->canvasWidth,  3); }
    private function scaleY(float $px): float { return round($px * $this->a4Height / $this->canvasHeight, 3); }
    private function scalePt(float $px): float { return round($px * 595 / $this->canvasWidth, 3); }

    public function generateFromTemplate(InvoiceTemplate $template, array $data)
    {
        $positions = $template->field_positions ?? [];
        $html      = $this->buildHtml($positions, $data, $template);
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        return $pdf;
    }

    private function buildHtml(array $positions, array $data, InvoiceTemplate $template): string
    {
        $tablePos = $positions['items_table'] ?? null;

        // Tabel bounding box in mm
        $tableX = $tablePos ? $this->scaleX($tablePos['x'] ?? 0)      : 12;
        $tableY = $tablePos ? $this->scaleY($tablePos['y'] ?? 0)       : 50;
        $tableW = $tablePos ? $this->scaleX($tablePos['width'] ?? 700) : 186;
        $tableH = $tablePos ? $this->scaleY($tablePos['height'] ?? 400): 180;

        // @page margins: content area = exact tabelblok
        // Alles buiten dit blok = fixed header/footer
        $marginTop    = round($tableY, 2);
        $marginLeft   = round($tableX, 2);
        $marginRight  = round($this->a4Width - $tableX - $tableW, 2);
        $marginBottom = round($this->a4Height - $tableY - $tableH, 2);

        // Achtergrond
        $bgCss = '';
        if ($template->background_path) {
            $bgPath = public_path('storage/' . $template->background_path);
            if (file_exists($bgPath)) {
                $bgCss = "background-image: url('$bgPath'); background-size: cover; background-repeat: no-repeat;";
            }
        }

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>

@page {
    margin-top:    ' . $marginTop    . 'mm;
    margin-left:   ' . $marginLeft   . 'mm;
    margin-right:  ' . $marginRight  . 'mm;
    margin-bottom: ' . $marginBottom . 'mm;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: Arial, sans-serif;
    ' . $bgCss . '
}

/* ── FIXED HEADER (boven tabel, herhaalt op elke pagina) ── */
.fixed-header {
    position: fixed;
    top:  -' . $marginTop   . 'mm;
    left: -' . $marginLeft  . 'mm;
    width:  ' . $this->a4Width . 'mm;
    height: ' . $marginTop . 'mm;
}
.fh-field {
    position: absolute;
    overflow: visible;
    word-wrap: break-word;
}

/* ── FIXED FOOTER (onder tabel, herhaalt op elke pagina) ── */
.fixed-footer {
    position: fixed;
    bottom: -' . $marginBottom . 'mm;
    left:   -' . $marginLeft   . 'mm;
    width:   ' . $this->a4Width . 'mm;
    height:  ' . $marginBottom . 'mm;
}
.ff-field {
    position: absolute;
    overflow: visible;
    word-wrap: break-word;
}

/* ── TABEL (flows in content area = tabelblok) ── */
.items-table {
    width: 100%;
    border-collapse: collapse;
    page-break-inside: auto;
}
.items-table thead { display: table-header-group; }
.items-table tr    { page-break-inside: avoid; page-break-after: auto; }
.items-table th, .items-table td {
    border: 1px solid #ccc;
    padding: 3px 5px;
}
.items-table th { background-color: #f0f0f0; font-weight: bold; }
.items-table tr:nth-child(even) td { background-color: #fafafa; }

</style>
</head>
<body>';

        // ── LOGO ──
        if ($template->logo_path && isset($positions['logo'])) {
            $logoPath = public_path('storage/' . $template->logo_path);
            if (file_exists($logoPath)) {
                $logo = $positions['logo'];
                // Logo altijd fixed (position: fixed = elke pagina)
                $html .= sprintf(
                    '<img src="%s" style="position:fixed; left:%smm; top:%smm; width:%smm; height:%smm;">',
                    $logoPath,
                    $this->scaleX($logo['x'] ?? 0) - $marginLeft,
                    $this->scaleY($logo['y'] ?? 0) - $marginTop,
                    $this->scaleX($logo['width']  ?? 150),
                    $this->scaleY($logo['height'] ?? 80)
                );
            }
        }

        // Splits alle velden op in header (boven tabel) en footer (onder/na tabel)
        $headerFields = [];
        $footerFields = [];

        foreach ($positions as $id => $pos) {
            if (in_array($id, ['logo', 'background', 'items_table'])) continue;
            $fieldYmm = $this->scaleY($pos['y'] ?? 0);
            $vis      = $pos['pageVisibility'] ?? 'first';

            if ($vis === 'last' || $fieldYmm >= ($tableY + $tableH * 0.5)) {
                $footerFields[$id] = $pos;
            } else {
                $headerFields[$id] = $pos;
            }
        }

        // ── FIXED HEADER (elke pagina) ──
        $html .= '<div class="fixed-header">';
        foreach ($headerFields as $id => $pos) {
            $value = $this->getValue($id, $pos, $data);
            if ($value === null) continue;
            // Positie relatief t.o.v. header div (offset door margin)
            $adjustedPos = $pos;
            $html .= $this->renderField($id, $adjustedPos, $value, 'fh-field', 0, 0);
        }
        $html .= '</div>';

        // ── FIXED FOOTER (elke pagina) ──
        if (!empty($footerFields)) {
            // Bereken min Y van footer velden als basis
            $footerMinY = min(array_map(fn($p) => $this->scaleY($p['y'] ?? 0), $footerFields));
            $html .= '<div class="fixed-footer">';
            foreach ($footerFields as $id => $pos) {
                $value = $this->getValue($id, $pos, $data);
                if ($value === null) continue;
                // Y relatief t.o.v. footer div
                $adjustedPos      = $pos;
                $adjustedPos['y'] = (($this->scaleY($pos['y'] ?? 0) - ($tableY + $tableH)) / $this->a4Height) * $this->canvasHeight;
                $html .= $this->renderField($id, $adjustedPos, $value, 'ff-field', 0, 0);
            }
            $html .= '</div>';
        }

        // ── ARTIKELENTABEL (normale flow = vult content area op elke pagina) ──
        $items = $data['items_table'] ?? [];
        if ($tablePos && is_array($items) && count($items) > 0) {
            $fontSize   = $this->scalePt($tablePos['fontSize'] ?? 10);
            $fontFamily = $tablePos['fontFamily'] ?? 'Arial, sans-serif';

            $html .= sprintf('<table class="items-table" style="font-size:%spt; font-family:%s;">
                <thead><tr>
                    <th style="text-align:left;">Omschrijving</th>
                    <th style="text-align:right;width:40px;">Aantal</th>
                    <th style="text-align:right;width:55px;">Prijs</th>
                    <th style="text-align:right;width:55px;">Totaal</th>
                </tr></thead>
                <tbody>', $fontSize, $fontFamily);

            foreach ($items as $item) {
                $total = ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
                $html .= sprintf('<tr>
                    <td>%s</td>
                    <td style="text-align:right;">%s</td>
                    <td style="text-align:right;">€ %s</td>
                    <td style="text-align:right;">€ %s</td>
                </tr>',
                    htmlspecialchars($item['description'] ?? ''),
                    number_format($item['quantity'] ?? 0, 0, ',', '.'),
                    number_format($item['price'] ?? 0, 2, ',', '.'),
                    number_format($total, 2, ',', '.')
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

    private function renderField(string $id, array $pos, mixed $value, string $class, float $offsetX = 0, float $offsetY = 0): string
    {
        $x     = $this->scaleX($pos['x'] ?? 0) - $offsetX;
        $y     = $this->scaleY($pos['y'] ?? 0) - $offsetY;
        $w     = $this->scaleX($pos['width']  ?? 200);
        $h     = $this->scaleY($pos['height'] ?? 30);
        $fs    = $this->scalePt($pos['fontSize'] ?? 12);
        $ff    = $pos['fontFamily'] ?? 'Arial, sans-serif';
        $align = $pos['align'] ?? 'left';

        return sprintf(
            '<div class="%s" style="left:%smm;top:%smm;width:%smm;height:%smm;font-size:%spt;font-family:%s;text-align:%s;">%s</div>',
            $class, $x, $y, $w, $h, $fs, $ff, $align,
            nl2br(htmlspecialchars((string)$value))
        );
    }
}
