<?php

namespace App\Services;

use App\Models\InvoiceTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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
    public bool $withBackground = true;

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

    public function generateFromTemplateToHtml(InvoiceTemplate $template, array $data): string
    {
        $positions = $template->field_positions ?? [];
        if (empty($positions)) {
            $positions = self::getDefaultPositions();
        }

        return $this->build($positions, $data, $template);
    }

    /**
     * Standaard veldposities (preset 'klassiek' uit TemplatePresets).
     * Wordt gebruikt als field_positions null/leeg is zodat de PDF ook zonder
     * handmatig opslaan een nette lay-out heeft.
     */
    public static function getDefaultPositions(): array
    {
        return TemplatePresets::positions('klassiek');
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
        $tFontFam = $tp ? $this->safeFontFamily($tp['fontFamily'] ?? 'Arial') : 'Arial';

        // Schatting: rijen per pagina op basis van font + padding
        // Rij hoogte ≈ fontPt * 1.4 (line-height) + 6pt padding = in mm: pt * 0.353mm/pt
        $rowHeightMm = ($tFontPt * 1.4 + 6) * 0.353;
        $headerRowMm = ($tFontPt * 1.4 + 6) * 0.353 * 1.2; // header iets groter
        $availableMm = $tH - $headerRowMm;
        $rowsPerPage = max(1, (int) floor($availableMm / $rowHeightMm));

        // Velden splitsen op basis van pageVisibility (editor-instelling heeft prioriteit).
        // Fallback: boven tabel Y = alle pagina's, onder tabel Y = alleen laatste pagina.
        // Decoratieve vlakken (static_rect_*) worden eerst gesorteerd zodat ze
        // áchter de tekstvelden renderen.
        $pos = $this->rectsFirst($pos);

        // BTW verlegd: geen BTW-bedrag/-label tonen en "Totaal incl. BTW" wordt
        // gewoon "Totaal" (alle bedragen zijn immers excl. BTW).
        $reverseCharged = (bool) ($data['vat_reverse_charged'] ?? false);
        if ($reverseCharged) {
            unset($pos['static_text_lbl_tax'], $pos['tax'], $pos['vat_amount']);
            foreach (['static_text_lbl_total' => 'Totaal:'] as $id => $txt) {
                if (isset($pos[$id])) {
                    $pos[$id]['staticText'] = $txt;
                    $pos[$id]['label']      = $txt;
                }
            }
        }

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

        // Achtergrond als full-page <img> met base64 data-URI.
        // Bewust NIET via CSS: dompdf's CSS-parser splitst op puntkomma's
        // en struikelt over de ';' in 'data:image/png;base64,...' waardoor
        // de hele .page-regel corrupt raakt. In een HTML-attribuut is de
        // data-URI wel veilig, en dompdf hoeft zo ook geen bestand te
        // openen (chroot/symlink-proof, o.a. voor Forge).
        $bgImg = '';
        if ($template->background_path && $this->withBackground) {
            $bgUri = $this->dataUri(Storage::path($template->background_path));
            if ($bgUri) {
                $bgImg = sprintf(
                    '<img src="%s" style="position:absolute;left:0;top:0;width:%smm;height:%smm;">',
                    $bgUri, $this->pW, $this->pH
                );
            }
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
{$this->tableCss($tp)}
</style></head><body>";

        // Elke pagina opbouwen
        for ($page = 0; $page < $totalPages; $page++) {
            $isLast = ($page === $totalPages - 1);
            $html  .= '<div class="page">';

            // Achtergrond eerst, zodat alle velden er bovenop komen
            $html .= $bgImg;

            // Logo (op elke pagina) — ook als data-URI, zie opmerking bij $bgImg
            if ($template->logo_path && isset($pos['logo'])) {
                $logoUri = $this->dataUri(Storage::path($template->logo_path));
                if ($logoUri) {
                    $l = $pos['logo'];
                    $html .= sprintf(
                        '<img src="%s" class="abs" style="left:%smm;top:%smm;width:%smm;height:%smm;">',
                        $logoUri,
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

            // Verplichte vermelding bij verlegde BTW — altijd op de laatste pagina,
            // los van het notities-veld (dat niet elke template rendert).
            if ($isLast && $reverseCharged && !empty($data['reverse_charge_note'])) {
                $html .= sprintf(
                    '<div class="abs" style="left:%smm;top:%smm;width:%smm;font-size:%spt;font-family:Arial;font-weight:bold;color:#78350f;border:1px solid #f59e0b;background-color:#fffbeb;padding:4px 8px;">%s</div>',
                    $this->x(50), $this->y(862), $this->x(750), $this->pt(13),
                    htmlspecialchars($data['reverse_charge_note'])
                );
            }

            // Tabelblok met rijen van deze pagina
            $html .= "<div class='tabel-blok'>";
            $html .= "<table class='items-table' style='font-size:{$tFontPt}pt;font-family:{$tFontFam};'>";

            // Koptekst op elke pagina (bij verlegd zonder BTW-kolommen)
            $vatHeaders = $reverseCharged ? '' :
                '<th style="text-align:right;width:52px;">BTW%</th>
                <th style="text-align:right;width:52px;">BTW</th>';
            $html .= '<thead><tr>
                <th style="text-align:left;">Omschrijving</th>
                <th style="text-align:right;width:36px;">Aantal</th>
                <th style="text-align:right;width:52px;">Prijs</th>
                ' . $vatHeaders . '
                <th style="text-align:right;width:52px;">Totaal</th>
            </tr></thead><tbody>';

            foreach ($chunks[$page] as $item) {
                $total = ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
                $vatCells = $reverseCharged ? '' : sprintf(
                    '<td style="text-align:right;">%s</td>
                    <td style="text-align:right;">%s</td>',
                    number_format($item['vat_rate'] ?? 0, 0, ',', '.') . "%",
                    number_format($item['vat_total'] ?? 0, 2, ',', '.')
                );
                $html .= sprintf('<tr>
                    <td>%s</td>
                    <td style="text-align:right;">%s</td>
                    <td style="text-align:right;">€&nbsp;%s</td>
                    %s
                    <td style="text-align:right;">€&nbsp;%s</td>
                </tr>',
                    htmlspecialchars($item['description'] ?? ''),
                    number_format($item['quantity'] ?? 0, 0, ',', '.'),
                    number_format($item['price']    ?? 0, 2, ',', '.'),
                    $vatCells,
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
        $extra = ($p['fontWeight'] ?? 'normal') === 'bold' ? 'font-weight:bold;' : '';

        if (!empty($p['color']) && ($c = $this->safeColor($p['color']))) {
            $extra .= "color:{$c};";
        }
        if (!empty($p['backgroundColor']) && ($c = $this->safeColor($p['backgroundColor']))) {
            $extra .= "background-color:{$c};";
        }

        return sprintf(
            '<div class="abs" style="left:%smm;top:%smm;width:%smm;height:%smm;font-size:%spt;font-family:%s;text-align:%s;%s">%s</div>',
            $this->x($p['x']      ?? 0),
            $this->y($p['y']      ?? 0),
            $this->x($p['width']  ?? 200),
            $this->y($p['height'] ?? 30),
            $this->pt($p['fontSize'] ?? 12),
            $this->safeFontFamily($p['fontFamily'] ?? 'Arial'),
            $this->safeAlign($p['align'] ?? 'left'),
            $extra,
            nl2br(htmlspecialchars(trim((string)$value)))
        );
    }

    /**
     * Sorteer decoratieve vlakken (static_rect_*) vóór de overige velden,
     * zodat tekst er bovenop komt te liggen.
     */
    private function rectsFirst(array $pos): array
    {
        $rects = array_filter($pos, fn ($id) => str_starts_with((string)$id, 'static_rect_'), ARRAY_FILTER_USE_KEY);
        $rest  = array_filter($pos, fn ($id) => !str_starts_with((string)$id, 'static_rect_'), ARRAY_FILTER_USE_KEY);

        return $rects + $rest;
    }

    /**
     * CSS voor de artikelen-tabel, stuurbaar via extra keys op het
     * items_table-veld: headerBg, headerColor, borderColor,
     * borderStyle (full|horizontal|minimal) en zebra (bool).
     */
    private function tableCss(?array $tp): string
    {
        $headerBg    = $this->safeColor($tp['headerBg'] ?? '')    ?: '#f0f0f0';
        $headerColor = $this->safeColor($tp['headerColor'] ?? '') ?: '#000000';
        $borderColor = $this->safeColor($tp['borderColor'] ?? '') ?: '#cccccc';
        $borderStyle = in_array($tp['borderStyle'] ?? '', ['full', 'horizontal', 'minimal'], true)
            ? $tp['borderStyle'] : 'full';
        $zebra = array_key_exists('zebra', $tp ?? []) ? (bool)$tp['zebra'] : true;

        $css = ".items-table { width:100%; border-collapse:collapse; }\n";

        switch ($borderStyle) {
            case 'horizontal':
                $css .= ".items-table th, .items-table td { border:0; border-bottom:1px solid {$borderColor}; padding:4px 6px; }\n";
                break;
            case 'minimal':
                $css .= ".items-table td { border:0; border-bottom:1px solid #e5e7eb; padding:4px 6px; }\n";
                $css .= ".items-table th { border:0; border-bottom:2px solid {$borderColor}; padding:4px 6px; }\n";
                break;
            default: // full
                $css .= ".items-table th, .items-table td { border:1px solid {$borderColor}; padding:3px 5px; }\n";
        }

        $css .= ".items-table th { background:{$headerBg}; color:{$headerColor}; font-weight:bold; }\n";

        if ($zebra) {
            $css .= ".items-table tr:nth-child(even) td { background:#fafafa; }\n";
        }

        return $css;
    }

    /**
     * Security: field_positions komt (via de editor) uit user-input en wordt
     * in inline-CSS geplaatst. Whitelisten voorkomt HTML/attribuut-injectie
     * in de dompdf-render (en daarmee o.a. local file inclusion via <img>).
     */
    private function safeFontFamily(mixed $font): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9 ,\-]/', '', (string) $font);

        return $clean !== '' ? $clean : 'Arial';
    }

    private function safeAlign(mixed $align): string
    {
        return in_array($align, ['left', 'right', 'center', 'justify'], true)
            ? $align
            : 'left';
    }

    /**
     * Lees een afbeelding in als base64 data-URI. Retourneert null als het
     * bestand niet bestaat of niet leesbaar is.
     */
    private function dataUri(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $data = file_get_contents($path);

        return $data === false ? null : 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * Whitelist voor kleuren uit user-input (hex only).
     */
    private function safeColor(mixed $color): string
    {
        $color = (string)$color;

        return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color) ? strtolower($color) : '';
    }

    private function getValue(string $id, array $pos, array $data): mixed
    {
        if (str_starts_with($id, 'static_rect_')) {
            return ''; // decoratief vlak: geen tekst, alleen backgroundColor
        }
        if (str_starts_with($id, 'static_text_')) {
            return $pos['staticText'] ?? ($pos['label'] ?? '');
        }
        return $data[$id] ?? null;
    }
}
