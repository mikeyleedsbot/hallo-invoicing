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
                    '<img src="%s" style="position: absolute; left: %spx; top: %spx; width: %spx; height: %spx;">',
                    $logoUrl,
                    $logo['x'] ?? 0,
                    $logo['y'] ?? 0,
                    $logo['width'] ?? 150,
                    $logo['height'] ?? 80
                );
            }
        }

        // Render each field
        foreach ($positions as $fieldId => $position) {
            if ($fieldId === 'logo' || $fieldId === 'background') {
                continue; // Skip special fields
            }
            
            $value = $this->getFieldValue($fieldId, $data);
            if ($value === null) {
                continue;
            }
            
            $html .= $this->renderField($fieldId, $position, $value);
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
    private function renderField(string $fieldId, array $position, $value): string
    {
        $x = $position['x'] ?? 0;
        $y = $position['y'] ?? 0;
        $width = $position['width'] ?? 200;
        $height = $position['height'] ?? 30;
        $fontSize = $position['fontSize'] ?? 12;
        $fontFamily = $position['fontFamily'] ?? 'Arial, sans-serif';
        $align = $position['align'] ?? 'left';
        
        // Special handling for items table
        if ($fieldId === 'items_table' && isset($value) && is_array($value)) {
            return $this->renderItemsTable($position, $value);
        }
        
        // Format multi-line text
        $formattedValue = nl2br(htmlspecialchars($value));
        
        $style = sprintf(
            'position: absolute; left: %spx; top: %spx; width: %spx; height: %spx; font-size: %spx; font-family: %s; text-align: %s;',
            $x, $y, $width, $height, $fontSize, $fontFamily, $align
        );
        
        return sprintf('<div class="field" style="%s">%s</div>', $style, $formattedValue);
    }
    
    /**
     * Render items table.
     */
    private function renderItemsTable(array $position, array $items): string
    {
        $x = $position['x'] ?? 0;
        $y = $position['y'] ?? 0;
        $width = $position['width'] ?? 700;
        $fontSize = $position['fontSize'] ?? 10;
        
        $html = sprintf(
            '<div class="field" style="position: absolute; left: %spx; top: %spx; width: %spx; font-size: %spx;">',
            $x, $y, $width, $fontSize
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
