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
    private function scaleFont(float $px): float { return round($px * 595 / $this->canvasWidth, 3); } // canvas px → pt

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
        // Splits velden op in categorieën
        $tablePos    = $positions['items_table'] ?? null;
        $tableYmm    = $tablePos ? $this->scaleY($tablePos['y'] ?? 0) : null;

        // Velden gesorteerd op Y
        $allFields = [];
        foreach ($positions as $id => $pos) {
            if (in_array($id, ['logo', 'background', 'items_table'])) continue;
            $allFields[$id] = $pos;
        }

        // Categoriseer per pageVisibility
        $fixedFields = [];  // 'all' → position:fixed (elke pagina)
        $firstFields = [];  // 'first' of geen instelling
        $lastFields  = [];  // 'last' → na de tabel

        foreach ($allFields as $id => $pos) {
            $vis = $pos['pageVisibility'] ?? 'first';
            if ($vis === 'all') {
                $fixedFields[$id] = $pos;
            } elseif ($vis === 'last') {
                $lastFields[$id] = $pos;
            } else {
                // 'first': alles boven tabel = eerste pagina, alles onder = na tabel
                $firstFields[$id] = $pos;
            }
        }

        // Hoogte header (tot aan tabel, of volledige pagina als geen tabel)
        $headerHeight = $tableYmm ?? $this->a4Height;

        // Tabel afmetingen
        $tableXmm    = $tablePos ? $this->scaleX($tablePos['x']     ?? 0)   : 12;
        $tableWidthmm = $tablePos ? $this->scaleX($tablePos['width'] ?? 700) : 186;

        // Achtergrond URL
        $bgStyle = '';
        if ($template->background_path) {
            $bgPath = public_path('storage/' . $template->background_path);
            if (file_exists($bgPath)) {
                $bgStyle = "background-image: url('$bgPath'); background-size: cover; background-repeat: no-repeat;";
            }
        }

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: Arial, sans-serif;
    width: 210mm;
    ' . $bgStyle . '
}

/* Velden die op ELKE pagina herhalen (logo, bedrijfsnaam, etc.) */
.fixed-field {
    position: fixed;
    overflow: visible;
}

/* Eerste pagina header: absoluut in een container van vaste hoogte */
.page-header {
    position: relative;
    width: 210mm;
    height: ' . round($headerHeight, 2) . 'mm;
    page-break-inside: avoid;
}
.header-field {
    position: absolute;
    overflow: visible;
    word-wrap: break-word;
}

/* Tabel sectie: normale flow zodat hij door paginas loopt */
.table-section {
    margin-left: ' . round($tableXmm, 2) . 'mm;
    width: ' . round($tableWidthmm, 2) . 'mm;
    page-break-before: avoid;
}
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
    text-align: left;
}
.items-table th { background-color: #f0f0f0; font-weight: bold; }
.items-table tr:nth-child(even) td { background-color: #fafafa; }

/* Footer: velden na de tabel */
.page-footer {
    position: relative;
    width: 210mm;
    margin-top: 4mm;
}
.footer-field {
    position: absolute;
    overflow: visible;
    word-wrap: break-word;
}
</style>
</head>
<body>';

        // ── FIXED FIELDS (herhalen op elke pagina) ──
        foreach ($fixedFields as $id => $pos) {
            $value = $this->getValue($id, $pos, $data);
            if ($value === null) continue;
            $html .= $this->renderFixed($id, $pos, $value);
        }

        // ── LOGO ──
        if ($template->logo_path && isset($positions['logo'])) {
            $logoPath = public_path('storage/' . $template->logo_path);
            if (file_exists($logoPath)) {
                $logo = $positions['logo'];
                $vis  = $logo['pageVisibility'] ?? 'first';
                $posStyle = $vis === 'all' ? 'position:fixed;' : 'position:absolute;';
                $html .= sprintf(
                    '<img src="%s" style="%s left:%smm; top:%smm; width:%smm; height:%smm;">',
                    $logoPath, $posStyle,
                    $this->scaleX($logo['x'] ?? 0),
                    $this->scaleY($logo['y'] ?? 0),
                    $this->scaleX($logo['width'] ?? 150),
                    $this->scaleY($logo['height'] ?? 80)
                );
            }
        }

        // ── PAGE HEADER (eerste pagina velden boven tabel) ──
        $html .= '<div class="page-header">';
        foreach ($firstFields as $id => $pos) {
            // Sla velden onder de tabel over — die komen in de footer
            if ($tableYmm !== null && $this->scaleY($pos['y'] ?? 0) >= $tableYmm) continue;
            $value = $this->getValue($id, $pos, $data);
            if ($value === null) continue;
            $html .= $this->renderAbsolute($id, $pos, $value, 'header-field');
        }
        $html .= '</div>';

        // ── ARTIKELENTABEL ──
        $items = $data['items_table'] ?? [];
        if ($tablePos && is_array($items) && count($items) > 0) {
            $fontSize   = $this->scaleFont($tablePos['fontSize'] ?? 10);
            $fontFamily = $tablePos['fontFamily'] ?? 'Arial, sans-serif';
            $html .= sprintf('<div class="table-section" style="font-size:%spt; font-family:%s;">', $fontSize, $fontFamily);
            $html .= '<table class="items-table">
                <thead><tr>
                    <th>Omschrijving</th>
                    <th style="text-align:right;width:40px;">Aantal</th>
                    <th style="text-align:right;width:55px;">Prijs</th>
                    <th style="text-align:right;width:55px;">Totaal</th>
                </tr></thead><tbody>';
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
            $html .= '</tbody></table></div>';
        }

        // ── PAGE FOOTER (velden na tabel + 'last' velden) ──
        // Collect: firstFields onder tabel + lastFields
        $footerFields = $lastFields;
        foreach ($firstFields as $id => $pos) {
            if ($tableYmm !== null && $this->scaleY($pos['y'] ?? 0) >= $tableYmm) {
                $footerFields[$id] = $pos;
            }
        }

        if (!empty($footerFields)) {
            // Bereken hoogte footer sectie
            $minY = min(array_map(fn($p) => $this->scaleY($p['y'] ?? 0), $footerFields));
            $maxY = max(array_map(fn($p) => $this->scaleY(($p['y'] ?? 0) + ($p['height'] ?? 30)), $footerFields));
            $footerHeight = $maxY - ($tableYmm ?? $minY) + 10;

            $html .= '<div class="page-footer" style="height:' . round($footerHeight) . 'mm;">';
            foreach ($footerFields as $id => $pos) {
                $value = $this->getValue($id, $pos, $data);
                if ($value === null) continue;
                // Y relatief ten opzichte van tabel start
                $adjustedPos = $pos;
                $adjustedPos['y'] = (($this->scaleY($pos['y'] ?? 0) - ($tableYmm ?? 0)) / $this->a4Height) * $this->canvasHeight;
                $html .= $this->renderAbsolute($id, $adjustedPos, $value, 'footer-field');
            }
            $html .= '</div>';
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

    private function renderFixed(string $id, array $pos, mixed $value): string
    {
        $x       = $this->scaleX($pos['x'] ?? 0);
        $y       = $this->scaleY($pos['y'] ?? 0);
        $w       = $this->scaleX($pos['width'] ?? 200);
        $h       = $this->scaleY($pos['height'] ?? 30);
        $fs      = $this->scaleFont($pos['fontSize'] ?? 12);
        $ff      = $pos['fontFamily'] ?? 'Arial, sans-serif';
        $align   = $pos['align'] ?? 'left';

        return sprintf(
            '<div class="fixed-field" style="left:%smm;top:%smm;width:%smm;height:%smm;font-size:%spt;font-family:%s;text-align:%s;">%s</div>',
            $x, $y, $w, $h, $fs, $ff, $align, nl2br(htmlspecialchars((string)$value))
        );
    }

    private function renderAbsolute(string $id, array $pos, mixed $value, string $class = 'header-field'): string
    {
        $x     = $this->scaleX($pos['x'] ?? 0);
        $y     = $this->scaleY($pos['y'] ?? 0);
        $w     = $this->scaleX($pos['width'] ?? 200);
        $h     = $this->scaleY($pos['height'] ?? 30);
        $fs    = $this->scaleFont($pos['fontSize'] ?? 12);
        $ff    = $pos['fontFamily'] ?? 'Arial, sans-serif';
        $align = $pos['align'] ?? 'left';

        return sprintf(
            '<div class="%s" style="left:%smm;top:%smm;width:%smm;height:%smm;font-size:%spt;font-family:%s;text-align:%s;">%s</div>',
            $class, $x, $y, $w, $h, $fs, $ff, $align, nl2br(htmlspecialchars((string)$value))
        );
    }
}
