<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Entitlements\GrantInvitationEntitlement;
use App\Actions\Invitations\PublishInvitation;
use App\Actions\Weddings\CreateWedding;
use App\Models\AuditLog;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingPartner;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.weddings.index'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
        $this->get(route('admin.audit-logs.index'))->assertRedirect(route('login'));
    }

    public function test_customers_cannot_reach_any_admin_route(): void
    {
        $customer = User::factory()->create();
        $wedding = $this->publishedWedding($customer);

        $this->actingAs($customer)->get(route('admin.weddings.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.weddings.show', $wedding))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($customer)
            ->post(route('admin.weddings.suspend', $wedding), ['reason' => 'Percobaan customer'])
            ->assertForbidden();

        $this->assertNull($wedding->refresh()->latestPublication?->unpublished_at);
    }

    public function test_an_admin_lists_and_inspects_other_peoples_weddings(): void
    {
        $admin = User::factory()->admin()->create();
        $first = $this->publishedWedding(User::factory()->create(['email' => 'satu@example.test']));
        $second = $this->publishedWedding(User::factory()->create(['email' => 'dua@example.test']));

        $this->actingAs($admin)
            ->get(route('admin.weddings.index'))
            ->assertOk()
            ->assertSee($first->internal_title)
            ->assertSee($second->internal_title)
            ->assertSee('satu@example.test');

        $this->actingAs($admin)
            ->get(route('admin.weddings.show', $first))
            ->assertOk()
            ->assertSee($first->slug)
            ->assertSee('Nonaktifkan publikasi');
    }

    public function test_the_admin_search_filters_by_owner_email(): void
    {
        $admin = User::factory()->admin()->create();
        $target = $this->publishedWedding(User::factory()->create(['email' => 'cari@example.test']));
        $other = $this->publishedWedding(User::factory()->create(['email' => 'lain@example.test']));

        $this->actingAs($admin)
            ->get(route('admin.weddings.index', ['q' => 'cari@example.test']))
            ->assertOk()
            ->assertSee($target->internal_title)
            ->assertDontSee($other->internal_title);
    }

    public function test_an_admin_can_suspend_a_publication_and_it_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $wedding = $this->publishedWedding(User::factory()->create());

        $this->actingAs($admin)
            ->post(route('admin.weddings.suspend', $wedding), ['reason' => 'Laporan penyalahgunaan'])
            ->assertRedirect();

        $wedding->refresh();

        $this->assertSame('unpublished', $wedding->lifecycle_status->value);
        $this->assertNotNull($wedding->latestPublication?->unpublished_at);
        $this->get(route('invitations.public', ['slug' => $wedding->slug]))->assertStatus(410);

        $log = AuditLog::query()->where('action', 'admin.invitation.suspended')->sole();
        $this->assertSame((string) $admin->getKey(), (string) $log->actor_user_id);
        $this->assertSame('Laporan penyalahgunaan', $log->metadata['reason']);
    }

    public function test_the_suspension_reason_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $wedding = $this->publishedWedding(User::factory()->create());

        $this->actingAs($admin)
            ->post(route('admin.weddings.suspend', $wedding), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame('published', $wedding->refresh()->lifecycle_status->value);
    }

    public function test_an_admin_still_cannot_edit_the_owners_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $wedding = $this->publishedWedding($owner);
        $version = $wedding->draft()->firstOrFail()->templateVersion;

        $this->actingAs($admin)
            ->put(route('weddings.draft.update', $wedding), [
                'template_version_id' => $version->getKey(),
                'content' => CreateWedding::defaultContent(),
            ])
            ->assertForbidden();
    }

    public function test_the_audit_log_page_can_be_filtered_by_action(): void
    {
        $admin = User::factory()->admin()->create();
        $wedding = $this->publishedWedding(User::factory()->create());

        $this->actingAs($admin)
            ->post(route('admin.weddings.suspend', $wedding), ['reason' => 'Tinjauan operator'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['action' => 'admin.invitation.suspended']))
            ->assertOk()
            ->assertSee('admin.invitation.suspended')
            ->assertSee('1 catatan tindakan sensitif.');

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('3 catatan tindakan sensitif.');
    }

    private function publishedWedding(User $owner): Wedding
    {
        $version = TemplateVersion::query()->where('renderer_key', 'classic-ivory.v1')->firstOrFail();
        $wedding = app(CreateWedding::class)->handle($owner, 'Pernikahan '.fake()->unique()->word(), $version);

        WeddingPartner::factory()->for($wedding)->create();
        WeddingEvent::factory()->for($wedding)->create();

        app(GrantInvitationEntitlement::class)->handle($wedding, $owner);
        app(PublishInvitation::class)->handle($wedding, $owner);

        return $wedding->refresh();
    }
}
