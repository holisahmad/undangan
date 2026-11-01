<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Weddings\CreateWedding;
use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Wedding;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
    }

    public function test_guests_and_customers_cannot_reach_the_user_and_order_console(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();

        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));

        $this->actingAs($customer)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.users.show', $other))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($customer)
            ->post(route('admin.users.suspend', $other), ['reason' => 'Percobaan customer'])
            ->assertForbidden();

        $this->assertFalse($other->refresh()->isSuspended());
    }

    public function test_the_user_directory_lists_accounts_and_applies_filters(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'operator@example.test']);
        $target = User::factory()->create(['email' => 'cari@example.test', 'name' => 'Cari Saya']);
        $other = User::factory()->create(['email' => 'lain@example.test']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('cari@example.test')
            ->assertSee('lain@example.test')
            ->assertSee('operator@example.test');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'cari@example.test']))
            ->assertOk()
            ->assertSee($target->email)
            ->assertDontSee($other->email);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'admin']))
            ->assertOk()
            ->assertSee('operator@example.test')
            ->assertDontSee($target->email);
    }

    public function test_the_user_detail_page_shows_related_weddings_orders_and_audit_trail(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create(['email' => 'pemilik@example.test']);
        $wedding = $this->weddingFor($owner, 'Undangan Pemilik');
        Order::factory()->paid()->create([
            'user_id' => $owner->getKey(),
            'wedding_id' => $wedding->getKey(),
            'public_order_number' => 'INV-DETAIL-1',
            'total_amount' => 199000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $owner))
            ->assertOk()
            ->assertSee('pemilik@example.test')
            ->assertSee('Undangan Pemilik')
            ->assertSee('INV-DETAIL-1')
            ->assertSee('Nonaktifkan akun');
    }

    public function test_suspending_an_account_blocks_sign_in_and_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['email' => 'diblokir@example.test']);

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $customer), ['reason' => 'Penyalahgunaan tautan'])
            ->assertRedirect();

        $customer->refresh();
        $this->assertTrue($customer->isSuspended());
        $this->assertSame('Penyalahgunaan tautan', $customer->suspension_reason);

        $log = AuditLog::query()->where('action', 'user.suspended')->sole();
        $this->assertSame((string) $admin->getKey(), (string) $log->actor_user_id);
        $this->assertSame((string) $customer->getKey(), (string) $log->resource_id);
        $this->assertSame('Penyalahgunaan tautan', $log->metadata['reason']);

        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => 'diblokir@example.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_suspension_reason_is_required_and_the_account_stays_active(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $customer), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($customer->refresh()->isSuspended());
        $this->assertSame(0, AuditLog::query()->where('action', 'user.suspended')->count());
    }

    public function test_an_admin_cannot_suspend_themselves_or_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $peer = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $admin), ['reason' => 'Kesalahan operator'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $peer), ['reason' => 'Kesalahan operator'])
            ->assertForbidden();

        $this->assertFalse($admin->refresh()->isSuspended());
        $this->assertFalse($peer->refresh()->isSuspended());
    }

    public function test_activating_an_account_restores_sign_in(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['email' => 'pulih@example.test']);
        $customer->forceFill(['suspended_at' => now()->subDay(), 'suspension_reason' => 'Sementara'])->save();

        $this->actingAs($admin)
            ->post(route('admin.users.activate', $customer))
            ->assertRedirect();

        $customer->refresh();
        $this->assertFalse($customer->isSuspended());
        $this->assertNull($customer->suspension_reason);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.activated',
            'resource_id' => (string) $customer->getKey(),
        ]);

        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => 'pulih@example.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_an_existing_session_ends_on_the_next_request_after_suspension(): void
    {
        $customer = User::factory()->create();
        $customer->forceFill(['suspended_at' => now(), 'suspension_reason' => 'Uji sesi'])->save();

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_the_order_ledger_shows_revenue_and_applies_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $paidOwner = User::factory()->create(['email' => 'lunas@example.test']);
        $pendingOwner = User::factory()->create(['email' => 'tertunda@example.test']);
        $paidWedding = $this->weddingFor($paidOwner, 'Undangan Lunas');
        $pendingWedding = $this->weddingFor($pendingOwner, 'Undangan Tertunda');

        Order::factory()->paid()->create([
            'user_id' => $paidOwner->getKey(),
            'wedding_id' => $paidWedding->getKey(),
            'public_order_number' => 'INV-LUNAS-1',
            'total_amount' => 150000,
        ]);

        Order::factory()->create([
            'user_id' => $pendingOwner->getKey(),
            'wedding_id' => $pendingWedding->getKey(),
            'public_order_number' => 'INV-TUNDA-1',
            'total_amount' => 99000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('INV-LUNAS-1')
            ->assertSee('INV-TUNDA-1')
            ->assertSee('Rp150.000')
            ->assertSee('1 pesanan lunas');

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['status' => OrderStatus::Paid->value]))
            ->assertOk()
            ->assertSee('INV-LUNAS-1')
            ->assertDontSee('INV-TUNDA-1');

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['q' => 'tertunda@example.test']))
            ->assertOk()
            ->assertSee('INV-TUNDA-1')
            ->assertDontSee('INV-LUNAS-1');
    }

    public function test_the_audit_log_can_be_filtered_by_resource_actor_and_date(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'operator@example.test']);
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $customer), ['reason' => 'Filter audit'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['resource_type' => 'user']))
            ->assertOk()
            ->assertSee('Filter audit')
            ->assertDontSee('Belum ada catatan.');

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['resource_type' => 'wedding']))
            ->assertOk()
            ->assertSee('Belum ada catatan.');

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['actor' => 'operator@example.test']))
            ->assertOk()
            ->assertSee('Filter audit');

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['actor' => 'tidak-ada@example.test']))
            ->assertOk()
            ->assertSee('Belum ada catatan.');

        $today = now()->toDateString();

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['from' => $today, 'to' => $today]))
            ->assertOk()
            ->assertSee('Filter audit');

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['from' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee('Belum ada catatan.');
    }

    private function weddingFor(User $owner, string $title): Wedding
    {
        $version = TemplateVersion::query()->where('renderer_key', 'classic-ivory.v1')->firstOrFail();

        return app(CreateWedding::class)->handle($owner, $title, $version);
    }
}
