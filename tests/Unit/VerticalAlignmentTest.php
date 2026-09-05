<?php

namespace Tests\Unit;

use App\Models\InvoiceTemplate;
use App\Services\InvoicePdfGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Verticale uitlijning van tekstvelden op de PDF.
 *
 * dompdf negeert vertical-align op absoluut gepositioneerde blokken, ook via
 * display:table-cell. Het wordt daarom met padding-top gedaan, waarbij de
 * bloghoogte met dezelfde waarde omlaag gaat omdat dompdf padding bij de
 * hoogte optelt. Netto blijft het veld even hoog en schuift alleen de tekst.
 */
class VerticalAlignmentTest extends TestCase
{
    /** Canvas is 1200px hoog en staat voor 297mm. */
    private const HEIGHT_PX = 60;
    private const HEIGHT_MM = 60 * 297 / 1200; // 14.85mm

    private function htmlFor(?string $verticalAlign): string
    {
        $field = [
            'x' => 100, 'y' => 100, 'width' => 300, 'height' => self::HEIGHT_PX,
            'fontSize' => 12, 'align' => 'left',
        ];

        if ($verticalAlign !== null) {
            $field['verticalAlign'] = $verticalAlign;
        }

        $template = new InvoiceTemplate(['field_positions' => ['invoice_number' => $field]]);

        return (new InvoicePdfGenerator())
            ->generateFromTemplateToHtml($template, ['invoice_number' => 'F-2026-001']);
    }

    /** @return array{padding: float, height: float} */
    private function boxFor(?string $verticalAlign): array
    {
        $html = $this->htmlFor($verticalAlign);

        $this->assertMatchesRegularExpression('/F-2026-001/', $html, 'veld niet gerenderd');
        preg_match('/<div class="abs" style="([^"]*)">F-2026-001</', $html, $m);
        $this->assertNotEmpty($m, 'veldstijl niet gevonden');

        preg_match('/height:([\d.]+)mm/', $m[1], $h);
        preg_match('/padding-top:([\d.]+)mm/', $m[1], $p);

        return [
            'padding' => isset($p[1]) ? (float) $p[1] : 0.0,
            'height'  => (float) $h[1],
        ];
    }

    public function test_boven_is_de_standaard_en_voegt_geen_padding_toe(): void
    {
        foreach ([null, 'top'] as $value) {
            $box = $this->boxFor($value);

            $this->assertSame(0.0, $box['padding']);
            $this->assertEqualsWithDelta(self::HEIGHT_MM, $box['height'], 0.01);
        }
    }

    public function test_midden_zet_de_tekst_halverwege(): void
    {
        $box = $this->boxFor('middle');

        // Regelhoogte ≈ 8.4pt × 1.2 × 0.3528 = 3.556mm; padding = (14.85 − 3.556) / 2
        $this->assertEqualsWithDelta(5.647, $box['padding'], 0.02);
    }

    public function test_onder_duwt_de_tekst_naar_de_onderkant(): void
    {
        $box = $this->boxFor('bottom');

        $this->assertEqualsWithDelta(11.294, $box['padding'], 0.02);
    }

    /** De kern: het veld mag niet groeien door de padding. */
    public function test_het_veld_houdt_altijd_dezelfde_hoogte(): void
    {
        foreach (['top', 'middle', 'bottom'] as $verticalAlign) {
            $box = $this->boxFor($verticalAlign);

            $this->assertEqualsWithDelta(
                self::HEIGHT_MM,
                $box['padding'] + $box['height'],
                0.01,
                "Hoogte klopt niet bij '{$verticalAlign}'"
            );
        }
    }

    public function test_boven_midden_onder_lopen_oplopend_af(): void
    {
        $top    = $this->boxFor('top')['padding'];
        $middle = $this->boxFor('middle')['padding'];
        $bottom = $this->boxFor('bottom')['padding'];

        $this->assertLessThan($middle, $top);
        $this->assertLessThan($bottom, $middle);
    }

    public function test_onzinwaarde_valt_terug_op_boven(): void
    {
        $this->assertSame(0.0, $this->boxFor('onzin')['padding']);
    }

    /** Bij tekst die het veld vult blijft er niets over om mee te schuiven. */
    public function test_tekst_hoger_dan_het_veld_krijgt_geen_negatieve_padding(): void
    {
        $template = new InvoiceTemplate([
            'field_positions' => [
                'notes' => [
                    'x' => 100, 'y' => 100, 'width' => 300, 'height' => 20,
                    'fontSize' => 12, 'align' => 'left', 'verticalAlign' => 'bottom',
                ],
            ],
        ]);

        $html = (new InvoicePdfGenerator())
            ->generateFromTemplateToHtml($template, ['notes' => "regel1\nregel2\nregel3\nregel4"]);

        $this->assertStringNotContainsString('padding-top:-', $html);
    }
}
