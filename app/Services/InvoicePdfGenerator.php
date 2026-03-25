<?php

namespace App\Services;

use App\Models\InvoiceTemplate;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfGenerator
{
    /**
     * Generate PDF from template with data.
     */
    public function generateFromTemplate(InvoiceTemplate $template, array $data)
    {
        // Get field positions from template
        $positions = $template->field_positions ?? [];
        
        // A4 dimensions in pixels at 72 DPI (standard PDF resolution)
        // A4 = 210mm x 297mm = 595pt x 842pt
        $canvasWidth = 850;
        $canvasHeight = 1200;
        
        // Build HTML with absolute positioning
        $html = $this->buildHtml($positions, $data, $template);
        
        // Generate PDF
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf;
    }
    
    /**
     * Build HTML from field positions and data.
     */
    private function buildHtml(array $positions, array $data, InvoiceTemplate $template): string
    {
        // Canvas dimensions (from editor)
        $canvasWidth = 850;  // pixels
        $canvasHeight = 1200; // pixels
        
        // A4 dimensions in mm
        $a4Width = 210;  // mm
        $a4Height = 297; // mm
        
        // Calculate scale factor (canvas pixels to PDF mm)
        $scaleX = $a4Width / $canvasWidth;
        $scaleY = $a4Height / $canvasHeight;
        
        // Font scale: canvas px → PDF pt
        // Canvas 850px = 210mm = 595pt → 1px = 595/850 = 0.7pt
        // Dit geeft fonts die 1:1 overeenkomen met wat je in de editor ziet
        $fontScale = 595 / $canvasWidth; // pt per canvas pixel
        
        // Bepaal positie van de artikelentabel (als die er is)
        $tablePosition = $positions['items_table'] ?? null;
        $tableYmm      = $tablePosition ? ($tablePosition['y'] ?? 0) * $scaleY : null;
        $tableXmm      = $tablePosition ? ($tablePosition['x'] ?? 0) * $scaleX : null;
        $tableWidthmm  = $tablePosition ? ($tablePosition['width'] ?? 700) * $scaleX : null;

        // Velden ONDER de tabel: absolute positie omzetten naar relatief (y - tableY - tableHeight)
        // We splitsen: alles boven tabel = absoluut, tabel = flow, alles onder tabel = absoluut op volgende positie

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            width: 210mm;
        }
        .page-header {
            position: relative;
            width: 210mm;
            height: ' . round($tableYmm ?? 100) . 'mm;
        }
        .field {
            position: absolute;
            overflow: visible;
            word-wrap: break-word;
        }
        .table-section {
            width: 210mm;
            padding: 0 ' . round($tableXmm ?? 12) . 'mm;
            page-break-inside: auto;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }
        .items-table thead { display: table-header-group; }
        .items-table tbody tr { page-break-inside: avoid; page-break-after: auto; }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .page-footer {
            position: relative;
            width: 210mm;
        }
        .page-footer .field {
            position: absolute;
        }
    </style>
</head>
<body>';

        // Achtergrond
        if ($template->background_path) {
            $backgroundUrl = public_path('storage/' . $template->background_path);
            if (file_exists($backgroundUrl)) {
                $html .= '<img src="' . $backgroundUrl . '" style="position: fixed; top: 0; left: 0; width: 210mm; height: 297mm; z-index: -1;">';
            }
        }

        // ── SECTIE 1: Page header (alles boven de tabel, absoluut) ──
        $html .= '<div class="page-header">';

        // Logo
        if ($template->logo_path && isset($positions['logo'])) {
            $logoUrl = public_path('storage/' . $template->logo_path);
            if (file_exists($logoUrl)) {
                $logo = $positions['logo'];
                $html .= sprintf(
                    '<img src="%s" style="position: absolute; left: %smm; top: %smm; width: %smm; height: %smm;">',
                    $logoUrl,
                    ($logo['x'] ?? 0) * $scaleX,
                    ($logo['y'] ?? 0) * $scaleY,
                    ($logo['width'] ?? 150) * $scaleX,
                    ($logo['height'] ?? 80) * $scaleY
                );
            }
        }

        // Alle velden BOVEN de tabel (of zonder tabel: allemaal)
        foreach ($positions as $fieldId => $position) {
            if (in_array($fieldId, ['logo', 'background', 'items_table'])) continue;

            // Velden die ONDER de tabel staan bewaren we voor sectie 3
            if ($tableYmm !== null && (($position['y'] ?? 0) * $scaleY) >= $tableYmm) continue;

            if (str_starts_with($fieldId, 'static_text_')) {
                $value = $position['staticText'] ?? ($position['label'] ?? '');
            } else {
                $value = $this->getFieldValue($fieldId, $data);
                if ($value === null) continue;
            }

            $html .= $this->renderField($fieldId, $position, $value, $scaleX, $scaleY, $fontScale);
        }

        $html .= '</div>'; // einde page-header

        // ── SECTIE 2: Artikelentabel (normale flow, pagina-overloop) ──
        if ($tablePosition && isset($data['items_table']) && is_array($data['items_table'])) {
            $fontSize  = ($tablePosition['fontSize'] ?? 10) * $fontScale;
            $fontFamily = $tablePosition['fontFamily'] ?? 'Arial, sans-serif';

            $html .= sprintf('<div class="table-section" style="font-size: %spt; font-family: %s;">', $fontSize, $fontFamily);
            $html .= '<table class="items-table">
                <thead>
                    <tr>
                        <th>Omschrijving</th>
                        <th style="text-align:right;">Aantal</th>
                        <th style="text-align:right;">Prijs</th>
                        <th style="text-align:right;">Totaal</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($data['items_table'] as $item) {
                $total = ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
                $html .= sprintf(
                    '<tr>
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

        // ── SECTIE 3: Velden ONDER de tabel (relatief gepositioneerd) ──
        $belowFields = [];
        foreach ($positions as $fieldId => $position) {
            if (in_array($fieldId, ['logo', 'background', 'items_table'])) continue;
            if ($tableYmm === null) continue;
            if ((($position['y'] ?? 0) * $scaleY) < $tableYmm) continue;
            $belowFields[] = ['id' => $fieldId, 'pos' => $position];
        }

        if (!empty($belowFields)) {
            // Hoogste Y onder de tabel bepaalt de hoogte van de footer sectie
            $maxY = max(array_map(fn($f) => (($f['pos']['y'] ?? 0) + ($f['pos']['height'] ?? 30)) * $scaleY, $belowFields));
            $minY = min(array_map(fn($f) => ($f['pos']['y'] ?? 0) * $scaleY, $belowFields));
            $footerHeight = $maxY - $tableYmm + 10;

            $html .= '<div class="page-footer" style="height: ' . round($footerHeight) . 'mm; margin-top: 4mm;">';

            foreach ($belowFields as $item) {
                $fieldId  = $item['id'];
                $position = $item['pos'];

                // Pas Y aan: relatief tov tabel startpositie
                $adjustedPosition = $position;
                $adjustedPosition['y'] = (($position['y'] ?? 0) * $scaleY - $tableYmm) / $scaleY;

                if (str_starts_with($fieldId, 'static_text_')) {
                    $value = $position['staticText'] ?? ($position['label'] ?? '');
                } else {
                    $value = $this->getFieldValue($fieldId, $data);
                    if ($value === null) continue;
                }

                $html .= $this->renderField($fieldId, $adjustedPosition, $value, $scaleX, $scaleY, $fontScale);
            }

            $html .= '</div>';
        }

        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Get value for a field from data.
     */
    private function getFieldValue(string $fieldId, array $data)
    {
        // Direct mapping
        if (isset($data[$fieldId])) {
            return $data[$fieldId];
        }
        
        return null;
    }
    
    /**
     * Render a single field as HTML.
     */
    private function renderField(string $fieldId, array $position, $value, float $scaleX, float $scaleY, float $fontScale = 0.7): string
    {
        // Convert canvas pixels to PDF millimeters (positie + grootte)
        $x = ($position['x'] ?? 0) * $scaleX;
        $y = ($position['y'] ?? 0) * $scaleY;
        $width = ($position['width'] ?? 200) * $scaleX;
        $height = ($position['height'] ?? 30) * $scaleY;
        // Font: canvas px → pt (aparte schaal, niet mee met positie)
        $fontSize = ($position['fontSize'] ?? 12) * $fontScale;
        $fontFamily = $position['fontFamily'] ?? 'Arial, sans-serif';
        $align = $position['align'] ?? 'left';
        
        // Special handling for items table
        if ($fieldId === 'items_table' && isset($value) && is_array($value)) {
            return $this->renderItemsTable($position, $value, $scaleX, $scaleY, $fontScale);
        }
        
        // Format multi-line text
        $formattedValue = nl2br(htmlspecialchars($value));
        
        $style = sprintf(
            'position: absolute; left: %smm; top: %smm; width: %smm; height: %smm; font-size: %spt; font-family: %s; text-align: %s;',
            $x, $y, $width, $height, $fontSize, $fontFamily, $align
        );
        
        return sprintf('<div class="field" style="%s">%s</div>', $style, $formattedValue);
    }
    
    /**
     * Render items table.
     */
    private function renderItemsTable(array $position, array $items, float $scaleX, float $scaleY, float $fontScale = 0.7): string
    {
        // Convert canvas pixels to PDF millimeters
        $x = ($position['x'] ?? 0) * $scaleX;
        $y = ($position['y'] ?? 0) * $scaleY;
        $width = ($position['width'] ?? 700) * $scaleX;
        $fontSize = ($position['fontSize'] ?? 10) * $fontScale;
        $fontFamily = $position['fontFamily'] ?? 'Arial, sans-serif';

        $html = sprintf(
            '<div class="field" style="position: absolute; left: %smm; top: %smm; width: %smm; font-size: %spt; font-family: %s;">',
            $x, $y, $width, $fontSize, $fontFamily
        );
        
        $html .= '<table class="items-table">
            <thead>
                <tr>
                    <th>Omschrijving</th>
                    <th style="text-align: right;">Aantal</th>
                    <th style="text-align: right;">Prijs</th>
                    <th style="text-align: right;">Totaal</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($items as $item) {
            $total = ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
            $html .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td style="text-align: right;">%s</td>
                    <td style="text-align: right;">€ %s</td>
                    <td style="text-align: right;">€ %s</td>
                </tr>',
                htmlspecialchars($item['description'] ?? ''),
                number_format($item['quantity'] ?? 0, 0, ',', '.'),
                number_format($item['price'] ?? 0, 2, ',', '.'),
                number_format($total, 2, ',', '.')
            );
        }
        
        $html .= '</tbody></table></div>';
        
        return $html;
    }
}
