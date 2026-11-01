<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 4 operator tools: CRM tags/notes, segments, campaign export and the
 * lead queue. The directory stays read-only for everyone except an operator.
 */
final class AdminCrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_operator_can_store_normalised_tags_and_a_note(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.crm.update', $customer), [
                'crm_tags' => '  VIP , wedding-fair, vip  ',
                'crm_note' => 'Menghubungi lewat WhatsApp, minta contoh paket Premium.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame(['vip', 'wedding-fair'], $customer->crm_tags);
        $this->assertSame('Menghubungi lewat WhatsApp, minta contoh paket Premium.', $customer->crm_note);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.crm_updated',
            'resource_id' => (string) $customer->getKey(),
        ]);
    }

    public function test_a_customer_cannot_write_crm_fields(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($customer)
            ->post(route('admin.users.crm.update', $other), ['crm_tags' => 'vip'])
            ->assertForbidden();

        $this->assertNull($other->fresh()->crm_tags);
    }

    public function test_the_directory_filters_by_tag_and_segment(): void
    {
        $admin = User::factory()->admin()->create();
        $vip = User::factory()->create(['crm_tags' => ['vip']]);
        $regular = User::factory()->create(['crm_tags' => ['biasa']]);
        $withWedding = User::factory()->create();
        Wedding::factory()->for($withWedding, 'owner')->create();
        $withoutWedding = User::factory()->create();

        $tagged = $this->actingAs($admin)
            ->get(route('admin.users.index', ['tag' => 'vip']))
            ->assertOk();

        $tagged->assertSee($vip->email);
        $tagged->assertDontSee($regular->email);
        $tagged->assertDontSee($withWedding->email);

        $idle = $this->actingAs($admin)
            ->get(route('admin.users.index', ['segment' => 'no_weddings']))
            ->assertOk();

        $idle->assertSee($withoutWedding->email);
        $idle->assertDontSee($withWedding->email);
    }

    public function test_the_campaign_export_is_formula_safe_and_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create([
            'name' => '=HYPERLINK("http://example.test")',
            'crm_tags' => ['vip'],
        ]);
        $other = User::factory()->create(['crm_tags' => ['biasa']]);

        $response = $this->actingAs($admin)->get(route('admin.users.export', ['tag' => 'vip']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        $content = $response->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringNotContainsString("\n=HYPERLINK", $content);
        $this->assertStringContainsString($customer->email, $content);
        $this->assertStringNotContainsString($other->email, $content);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.exported']);
    }

    public function test_the_lead_queue_marks_an_enquiry_as_contacted(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = Lead::create([
            'name' => 'Rina',
            'email' => 'rina@example.test',
            'source' => 'landing',
            'status' => Lead::STATUS_NEW,
        ]);

        $this->actingAs($admin)->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee('Rina')
            ->assertSee('Belum dihubungi');

        $this->actingAs($admin)
            ->post(route('admin.leads.contacted', $lead))
            ->assertRedirect();

        $this->assertSame(Lead::STATUS_CONTACTED, $lead->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'lead.contacted',
            'resource_id' => (string) $lead->getKey(),
        ]);

        $this->actingAs($admin)->get(route('admin.leads.index', ['status' => 'new']))
            ->assertOk()
            ->assertDontSee('rina@example.test');
    }

    public function test_a_customer_cannot_read_leads_or_export_the_directory(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.leads.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.users.export'))->assertForbidden();
    }
}
