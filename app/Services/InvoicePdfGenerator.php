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
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            position: relative;
            width: 210mm;
            height: 297mm;
        }
        .field {
            position: absolute;
            overflow: hidden;
            word-wrap: break-word;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
</head>
<body>';

        // Add background image if exists
        if ($template->background_path) {
            $backgroundUrl = public_path('storage/' . $template->background_path);
            if (file_exists($backgroundUrl)) {
                $html .= '<img src="' . $backgroundUrl . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;">';
            }
        }

        // Add logo if exists and positioned
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

        // Render each field
        foreach ($positions as $fieldId => $position) {
            if ($fieldId === 'logo' || $fieldId === 'background') {
                continue;
            }

            // Vrije tekstvelden: gebruik staticText direct als waarde
            if (str_starts_with($fieldId, 'static_text_')) {
                $value = $position['staticText'] ?? ($position['label'] ?? '');
                $html .= $this->renderField($fieldId, $position, $value, $scaleX, $scaleY, $fontScale);
                continue;
            }

            $value = $this->getFieldValue($fieldId, $data);
            if ($value === null) {
                continue;
            }

            $html .= $this->renderField($fieldId, $position, $value, $scaleX, $scaleY, $fontScale);
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
