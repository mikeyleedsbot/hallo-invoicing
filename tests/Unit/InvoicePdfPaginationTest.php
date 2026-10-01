<?php

namespace Tests\Unit;

use App\Models\InvoiceTemplate;
use App\Services\InvoicePdfGenerator;
use Dompdf\Dompdf;
use Dompdf\Frame;
use PHPUnit\Framework\TestCase;

/**
 * Paginering van de artikelentabel op de factuur-PDF.
 *
 * Het tabelblok heeft overflow:hidden. Als er te veel (of te hoge) rijen op
 * een pagina staan, valt de onderkant buiten het blok en verdwijnen rijen
 * onzichtbaar uit de PDF. De tests renderen de HTML met dompdf en meten de
 * echte hoogte van de tabel per pagina.
 */
class InvoicePdfPaginationTest extends TestCase
{
    /** Tabelblok van de standaardpositie: 300px van 1200px = 74,25mm. */
    private const BLOCK_HEIGHT_PT = 300 * 297 / 1200 / 0.3528;

    private const SHORT = 'Webdesign';
    private const TWO_LINES = 'Onderhoud en support van de website en webshop inclusief updates en back-ups';
    private const LONG = 'Uitgebreide maandelijkse dienstverlening bestaande uit onderhoud, support, monitoring, updates, back-ups, beveiligingscontroles en rapportage aan de opdrachtgever over de afgelopen periode';

    private function items(int $count, string $description): array
    {
        return array_map(fn (int $i) => [
            'description' => "Regel {$i} {$description}",
            'quantity'    => 1,
            'price'       => 10,
            'vat_rate'    => 21,
            'vat_total'   => 2.1,
        ], range(1, $count));
    }

    private function html(array $items): string
    {
        return (new InvoicePdfGenerator())
            ->generateFromTemplateToHtml(new InvoiceTemplate(), ['items_table' => $items]);
    }

    /**
     * Rendert de HTML en geeft per pagina [aantal rijen, hoogte tabel in pt].
     *
     * @return array<int, array{rows: int, height: float}>
     */
    private function measurePages(string $html): array
    {
        // De frame-tree wordt na render() opgeruimd; meten kan dus alleen
        // tijdens het renderen via de end_frame-callback.
        $pages = [];
        $dompdf = new Dompdf();
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->setCallbacks([[
            'event' => 'end_frame',
            'f'     => function (Frame $frame) use (&$pages) {
                $node = $frame->get_node();
                if ($node->nodeName === 'table' && str_contains((string) $node->getAttribute('class'), 'items-table')) {
                    $pages[] = [
                        'rows'   => $node->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr')->length,
                        'height' => $frame->get_border_box()['h'],
                    ];
                }
            },
        ]]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $pages;
    }

    private function assertEveryRowVisible(array $items): array
    {
        $html  = $this->html($items);
        $pages = $this->measurePages($html);

        $this->assertSame(count($items), array_sum(array_column($pages, 'rows')), 'rijen verloren bij paginering');

        foreach ($pages as $i => $page) {
            $this->assertLessThanOrEqual(
                self::BLOCK_HEIGHT_PT,
                $page['height'],
                sprintf('Tabel op pagina %d is %.1fpt hoog en past niet in het blok (%.1fpt): onderste rijen worden afgekapt.',
                    $i + 1, $page['height'], self::BLOCK_HEIGHT_PT)
            );
        }

        return $pages;
    }

    public function test_short_invoice_fits_on_one_page(): void
    {
        $pages = $this->assertEveryRowVisible($this->items(5, self::SHORT));

        $this->assertCount(1, $pages);
    }

    public function test_medium_invoice_with_two_line_descriptions_is_not_clipped(): void
    {
        // 12 regels van twee regels tekst: past niet op één pagina
        $pages = $this->assertEveryRowVisible($this->items(12, self::TWO_LINES));

        $this->assertGreaterThan(1, count($pages));
    }

    public function test_medium_invoice_with_mixed_descriptions_is_not_clipped(): void
    {
        $items = array_merge(
            $this->items(4, self::SHORT),
            $this->items(7, self::TWO_LINES),
            $this->items(3, self::SHORT)
        );

        $this->assertEveryRowVisible($items);
    }

    public function test_long_invoice_is_split_over_many_pages_without_clipping(): void
    {
        $pages = $this->assertEveryRowVisible($this->items(60, self::TWO_LINES));

        $this->assertGreaterThan(3, count($pages));
    }

    public function test_long_descriptions_are_not_clipped(): void
    {
        $this->assertEveryRowVisible($this->items(15, self::LONG));
    }

    public function test_every_page_repeats_the_table_header(): void
    {
        $html = $this->html($this->items(12, self::TWO_LINES));

        $this->assertSame(
            substr_count($html, "<div class=\"page\">"),
            substr_count($html, '<th style="text-align:left;">Omschrijving</th>')
        );
    }

    public function test_invoice_without_lines_renders_one_page(): void
    {
        $this->assertCount(1, $this->measurePages($this->html([])));
    }
}
