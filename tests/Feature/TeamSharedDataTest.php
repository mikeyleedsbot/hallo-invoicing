<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\Team;
use App\Models\User;
use App\Models\VatRate;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bewijst het eigenlijke doel van de team-feature: twee verschillende
 * gebruikers (elk met eigen login + eigen MFA) in hetzelfde team delen
 * dezelfde klanten, facturen, instellingen en factuurnummering.
 */
class TeamSharedDataTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $member;
    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('eigenaar@example.test');
        $this->team  = $this->owner->currentTeam;

        $this->member = $this->makeUser('lid@example.test');
        // Voegt lid toe aan het team van de eigenaar i.p.v. zijn eigen team te gebruiken.
        $this->team->members()->attach($this->member->id, ['role' => Team::ROLE_MEMBER]);
        $this->member->current_team_id = $this->team->id;
        $this->member->save();
    }

    private function makeUser(string $email): User
    {
        $user = User::create([
            'name'              => 'User ' . $email,
            'email'             => $email,
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        (new TeamService())->createForOwner($user);

        return $user;
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    public function test_teamlid_ziet_klanten_van_de_eigenaar(): void
    {
        $this->actingAs($this->owner);
        $customer = Customer::create([
            'name'    => 'Gedeelde Klant',
            'country' => 'Nederland',
        ]);

        $this->as($this->member)->get('/customers')->assertOk()->assertSee($customer->name);
    }

    public function test_teamlid_kan_factuur_van_eigenaar_bewerken(): void
    {
        $this->actingAs($this->owner);
        $customer = Customer::create(['name' => 'Klant', 'country' => 'Nederland']);
        $template = InvoiceTemplate::first();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEAM-001',
            'customer_id'    => $customer->id,
            'template_id'    => $template->id,
            'invoice_date'   => now(),
            'due_date'       => now()->addDays(14),
            'payment_terms'  => 14,
            'subtotal'       => 100,
            'vat_amount'     => 21,
            'total'          => 121,
            'status'         => 'draft',
        ]);

        $this->as($this->member)
            ->get("/invoices/{$invoice->id}")
            ->assertOk();

        $this->as($this->member)
            ->post("/invoices/{$invoice->id}/mark-sent", ['sent_date' => now()->toDateString()])
            ->assertRedirect();

        $this->assertEquals('sent', $invoice->fresh()->status);
    }

    public function test_beide_teamleden_delen_dezelfde_btw_tarieven_en_geen_dubbele_seed(): void
    {
        $this->actingAs($this->owner);
        $ratesForOwner = VatRate::all();

        $this->actingAs($this->member);
        $ratesForMember = VatRate::all();

        $this->assertCount(3, $ratesForOwner);
        $this->assertEquals($ratesForOwner->pluck('id'), $ratesForMember->pluck('id'));
    }

    public function test_factuurnummering_is_gedeeld_over_het_team(): void
    {
        $this->actingAs($this->owner);
        $customer = Customer::create(['name' => 'Klant', 'country' => 'Nederland']);
        $template = InvoiceTemplate::first();

        $appSettings = \App\Models\AppSetting::get();
        $firstNumber = $appSettings->nextInvoiceNumber();

        Invoice::create([
            'invoice_number' => $firstNumber,
            'customer_id'    => $customer->id,
            'template_id'    => $template->id,
            'invoice_date'   => now(),
            'due_date'       => now()->addDays(14),
            'payment_terms'  => 14,
            'subtotal'       => 100,
            'vat_amount'     => 21,
            'total'          => 121,
            'status'         => 'draft',
        ]);

        $this->actingAs($this->member);
        $nextNumber = \App\Models\AppSetting::get()->nextInvoiceNumber();

        $this->assertNotEquals($firstNumber, $nextNumber);
    }
}
