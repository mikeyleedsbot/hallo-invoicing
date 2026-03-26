<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $prevMonth = now()->subMonth();

        // Openstaand (verzonden + verlopen)
        $openstaandBedrag  = Invoice::whereIn('status', ['sent', 'overdue'])->sum('total');
        $openstaandAantal  = Invoice::whereIn('status', ['sent', 'overdue'])->count();

        // Omzet deze maand (betaald)
        $dezeMaandBedrag = Invoice::whereMonth('invoice_date', $now->month)
            ->whereYear('invoice_date', $now->year)
            ->whereIn('status', ['paid'])
            ->sum('total');
        $dezeMaandAantal = Invoice::whereMonth('invoice_date', $now->month)
            ->whereYear('invoice_date', $now->year)
            ->count();

        // Omzet vorige maand (voor % vergelijking)
        $vorigeMaandBedrag = Invoice::whereMonth('invoice_date', $prevMonth->month)
            ->whereYear('invoice_date', $prevMonth->year)
            ->whereIn('status', ['paid'])
            ->sum('total');

        $groeiPercentage = $vorigeMaandBedrag > 0
            ? round((($dezeMaandBedrag - $vorigeMaandBedrag) / $vorigeMaandBedrag) * 100)
            : ($dezeMaandBedrag > 0 ? 100 : 0);

        // Totale omzet (alle betaalde facturen)
        $totaleOmzet = Invoice::where('status', 'paid')->sum('total');

        // Klanten
        $klantenAantal      = Customer::count();
        $klantenDezeMaand   = Customer::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        // Offertes
        $offertesAantal   = Quote::count();
        $offertesDraft    = Quote::where('status', 'draft')->count();
        $offertesSent     = Quote::where('status', 'sent')->count();

        // Recente facturen (laatste 5)
        $recenteFacturen = Invoice::with('customer')
            ->latest('invoice_date')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'openstaandBedrag',
            'openstaandAantal',
            'dezeMaandBedrag',
            'dezeMaandAantal',
            'vorigeMaandBedrag',
            'groeiPercentage',
            'totaleOmzet',
            'klantenAantal',
            'klantenDezeMaand',
            'offertesAantal',
            'offertesDraft',
            'offertesSent',
            'recenteFacturen'
        ));
    }
}
