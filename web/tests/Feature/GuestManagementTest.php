<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\GuestInvitationLink;
use App\Models\Rsvp;
use App\Models\ServiceEntitlement;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Guests\GuestToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Owner-side guest management: tenant isolation, groups, personal links, quota
 * and CSV. Acceptance criteria from docs/ROADMAP.md, fase 4.
 */
final class GuestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_adds_a_guest_with_a_party_quota(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($owner)->post(route('weddings.guests.store', $wedding), [
            'display_name' => 'Bapak Ahmad dan keluarga',
            'allowed_party_size' => 4,
            'guest_group_id' => '',
            'contact_phone' => '0812-3456-7890',
        ]);

        $response->assertRedirect(route('weddings.guests.index', $wedding));

        $guest = Guest::query()->sole();

        $this->assertSame($wedding->getKey(), $guest->wedding_id);
        $this->assertSame('Bapak Ahmad dan keluarga', $guest->display_name);
        $this->assertSame(4, $guest->allowed_party_size);
        $this->assertNull($guest->contact_email);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'guest.created',
            'resource_id' => $guest->getKey(),
        ]);
    }

    public function test_another_account_cannot_read_or_change_the_guest_list(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();
        $guest = Guest::factory()->for($wedding)->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('weddings.guests.index', $wedding))->assertForbidden();
        $this->actingAs($intruder)->get(route('weddings.guests.export', $wedding))->assertForbidden();
        $this->actingAs($intruder)->get(route('weddings.guests.template', $wedding))->assertForbidden();
        $this->actingAs($intruder)
            ->post(route('weddings.guests.store', $wedding), [
                'display_name' => 'Tamu Palsu',
                'allowed_party_size' => 1,
            ])
            ->assertForbidden();
        $this->actingAs($intruder)
            ->patch(route('weddings.guests.update', [$wedding, $guest]), [
                'display_name' => 'Diubah Pihak Lain',
                'allowed_party_size' => 1,
            ])
            ->assertForbidden();
        $this->actingAs($intruder)
            ->delete(route('weddings.guests.destroy', [$wedding, $guest]))
            ->assertForbidden();
        $this->actingAs($intruder)
            ->post(route('weddings.guests.link.store', [$wedding, $guest]))
            ->assertForbidden();

        $this->assertDatabaseCount('guests', 1);
        $this->assertDatabaseCount('guest_invitation_links', 0);
    }

    public function test_a_guest_from_another_wedding_is_not_reachable_through_the_route(): void
    {
        $owner = User::factory()->create();
        $ownWedding = Wedding::factory()->for($owner, 'owner')->create();
        $foreignGuest = Guest::factory()->create();

        $this->actingAs($owner)
            ->get(route('weddings.guests.edit', [$ownWedding, $foreignGuest]))
            ->assertNotFound();
        $this->actingAs($owner)
            ->patch(route('weddings.guests.update', [$ownWedding, $foreignGuest]), [
                'display_name' => 'Pindah Kepemilikan',
                'allowed_party_size' => 1,
            ])
            ->assertNotFound();
    }

    public function test_groups_are_unique_per_wedding_and_guests_survive_group_deletion(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->post(route('weddings.guest-groups.store', $wedding), ['name' => 'Keluarga'])
            ->assertRedirect();

        $group = GuestGroup::query()->sole();

        $this->actingAs($owner)
            ->post(route('weddings.guest-groups.store', $wedding), ['name' => 'Keluarga'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('guest_groups', 1);

        $guest = Guest::factory()->for($wedding)->create(['guest_group_id' => $group->getKey()]);

        $this->actingAs($owner)
            ->delete(route('weddings.guest-groups.destroy', [$wedding, $group]))
            ->assertRedirect();

        $this->assertDatabaseCount('guest_groups', 0);
        $this->assertNull($guest->refresh()->guest_group_id);
    }

    public function test_issuing_a_personal_link_revokes_the_previous_one_and_hides_the_raw_token(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();
        $guest = Guest::factory()->for($wedding)->create();

        $first = $this->actingAs($owner)->post(route('weddings.guests.link.store', [$wedding, $guest]));

        $first->assertRedirect(route('weddings.guests.index', $wedding))->assertSessionHas('guest_link');

        $url = (string) $first->getSession()->get('guest_link');
        $token = Str::afterLast($url, '/');

        $this->assertStringContainsString('/g/', $url);
        $this->assertSame(GuestToken::LENGTH, strlen($token));

        $firstLink = GuestInvitationLink::query()->where('token_hash', GuestToken::hash($token))->sole();

        // Only the hash is stored; the bearer token never reaches the database.
        $this->assertDatabaseMissing('guest_invitation_links', ['token_hash' => $token]);
        $this->assertNull($firstLink->revoked_at);

        $second = $this->actingAs($owner)->post(route('weddings.guests.link.store', [$wedding, $guest]));
        $second->assertSessionHas('guest_link');

        $this->assertDatabaseCount('guest_invitation_links', 2);
        $this->assertNotNull($firstLink->refresh()->revoked_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'guest.link_issued']);
    }

    public function test_the_guest_list_still_shows_an_active_link_after_reissuing(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();
        $guest = Guest::factory()->for($wedding)->create();

        $this->actingAs($owner)->post(route('weddings.guests.link.store', [$wedding, $guest]));
        $this->actingAs($owner)->post(route('weddings.guests.link.store', [$wedding, $guest]));

        // Both rows can share a creation timestamp; the page must still treat
        // the single non-revoked link as active.
        $this->actingAs($owner)
            ->get(route('weddings.guests.index', $wedding))
            ->assertOk()
            ->assertSee('Aktif')
            ->assertSee('Cabut tautan')
            ->assertDontSee('Dicabut');
    }

    public function test_revoking_a_link_removes_it_from_public_resolution(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();
        $guest = Guest::factory()->for($wedding)->create();

        $token = GuestToken::generate();
        GuestInvitationLink::factory()->for($guest)->withToken($token)->create();

        $this->assertNotNull(GuestInvitationLink::resolve($token));

        $this->actingAs($owner)
            ->delete(route('weddings.guests.link.destroy', [$wedding, $guest]))
            ->assertRedirect();

        $this->assertNull(GuestInvitationLink::resolve($token));
        $this->assertDatabaseHas('audit_logs', ['action' => 'guest.link_revoked']);

        // Revoking twice is harmless and does not create a second audit entry.
        $this->actingAs($owner)
            ->delete(route('weddings.guests.link.destroy', [$wedding, $guest]))
            ->assertRedirect();

        $this->assertSame(1, AuditLog::query()->where('action', 'guest.link_revoked')->count());
    }

    public function test_a_guest_quota_from_the_active_package_is_enforced(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        ServiceEntitlement::factory()->for($wedding)->create([
            'limits_json' => [
                'months' => 12,
                'source' => 'order',
                'features' => ['guest_limit' => ['limit' => 2]],
            ],
        ]);

        foreach (['Tamu Satu', 'Tamu Dua'] as $name) {
            $this->actingAs($owner)
                ->post(route('weddings.guests.store', $wedding), [
                    'display_name' => $name,
                    'allowed_party_size' => 1,
                ])
                ->assertRedirect();
        }

        $this->actingAs($owner)
            ->post(route('weddings.guests.store', $wedding), [
                'display_name' => 'Tamu Tiga',
                'allowed_party_size' => 1,
            ])
            ->assertSessionHasErrors('guests');

        $this->assertDatabaseCount('guests', 2);
    }

    public function test_importing_a_valid_csv_creates_guests_and_groups(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        $csv = "name,group,phone,email,allowed_party_size\n"
            ."Bapak Ahmad,Keluarga,081200000001,,2\n"
            ."Ibu Siti,,081200000002,siti@example.test,1\n";

        $response = $this->actingAs($owner)->post(route('weddings.guests.import', $wedding), [
            'file' => UploadedFile::fake()->createWithContent('tamu.csv', $csv),
        ]);

        $response->assertRedirect(route('weddings.guests.index', $wedding));

        $this->assertDatabaseCount('guests', 2);

        $group = GuestGroup::query()->sole();
        $this->assertSame('Keluarga', $group->name);

        $ahmad = Guest::query()->where('display_name', 'Bapak Ahmad')->sole();
        $this->assertSame($group->getKey(), $ahmad->guest_group_id);
        $this->assertSame(2, $ahmad->allowed_party_size);
        $this->assertNull($ahmad->contact_email);

        $siti = Guest::query()->where('display_name', 'Ibu Siti')->sole();
        $this->assertNull($siti->guest_group_id);
        $this->assertSame('siti@example.test', $siti->contact_email);
        $this->assertDatabaseHas('audit_logs', ['action' => 'guest.imported', 'resource_id' => $wedding->getKey()]);
    }

    public function test_an_invalid_csv_imports_nothing_and_reports_row_numbers(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        $csv = "name,email\n"
            ."Budi,bukan-email\n"
            ."Budi,\n";

        $response = $this->actingAs($owner)->post(route('weddings.guests.import', $wedding), [
            'file' => UploadedFile::fake()->createWithContent('tamu.csv', $csv),
        ]);

        $response->assertSessionHasErrors('file');

        $messages = implode(' ', (array) $response->getSession()->get('errors')->get('file'));

        $this->assertStringContainsString('Baris 2', $messages);
        $this->assertStringContainsString('email', $messages);
        $this->assertStringContainsString('Baris 3', $messages);
        $this->assertStringContainsString('duplikat', $messages);

        // All-or-nothing: a file with errors writes no rows.
        $this->assertDatabaseCount('guests', 0);
    }

    public function test_a_semicolon_separated_csv_with_indonesian_headers_imports_normally(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        // Excel with Indonesian regional settings writes semicolons.
        $csv = "Nama Tamu;Grup;No. HP;Jumlah Tamu\n"
            ."Bapak Ahmad;Keluarga;081200000001;2\n";

        $response = $this->actingAs($owner)->post(route('weddings.guests.import', $wedding), [
            'file' => UploadedFile::fake()->createWithContent('tamu.csv', $csv),
        ]);

        $response->assertRedirect(route('weddings.guests.index', $wedding));
        $response->assertSessionHasNoErrors();

        $guest = Guest::query()->sole();

        $this->assertSame('Bapak Ahmad', $guest->display_name);
        $this->assertSame(2, $guest->allowed_party_size);
        $this->assertSame('Keluarga', $guest->group?->name);
    }

    public function test_a_csv_with_a_utf8_bom_and_windows_line_endings_imports_normally(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        // "CSV UTF-8" from Excel starts with a BOM and uses CRLF.
        $csv = "\xEF\xBB\xBFname,group,phone,allowed_party_size\r\n"
            ."Bapak Ahmad,,081200000001,2\r\n";

        $response = $this->actingAs($owner)->post(route('weddings.guests.import', $wedding), [
            'file' => UploadedFile::fake()->createWithContent('tamu.csv', $csv),
        ]);

        $response->assertRedirect(route('weddings.guests.index', $wedding));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseCount('guests', 1);
    }

    public function test_the_csv_template_download_is_importable_and_protected(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($owner)->get(route('weddings.guests.template', $wedding));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBFname,group,phone,email,allowed_party_size", $content);

        // Round trip: the file we hand out must import without edits.
        $import = $this->actingAs($owner)->post(route('weddings.guests.import', $wedding), [
            'file' => UploadedFile::fake()->createWithContent('template.csv', $content),
        ]);

        $import->assertRedirect(route('weddings.guests.index', $wedding));
        $import->assertSessionHasNoErrors();

        $this->assertDatabaseCount('guests', 2);
        $this->assertDatabaseHas('guest_groups', ['name' => 'Keluarga']);
    }

    public function test_an_unknown_header_is_reported_with_the_detected_columns(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        $csv = "tamu,undangan\nBapak Ahmad,081200000001\n";

        $response = $this->actingAs($owner)->post(route('weddings.guests.import', $wedding), [
            'file' => UploadedFile::fake()->createWithContent('tamu.csv', $csv),
        ]);

        $response->assertSessionHasErrors('file');

        $messages = implode(' ', (array) $response->getSession()->get('errors')->get('file'));

        $this->assertStringContainsString('Terbaca: "tamu, undangan"', $messages);
        $this->assertStringContainsString('titik koma', $messages);
        $this->assertDatabaseCount('guests', 0);
    }

    public function test_the_export_neutralises_spreadsheet_formulas(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        Guest::factory()->for($wedding)->create([
            'display_name' => '=HYPERLINK("http://example.test")',
            'contact_phone' => '+628123456789',
        ]);

        $response = $this->actingAs($owner)->get(route('weddings.guests.export', $wedding));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        $content = $response->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString("'+628123456789", $content);
        $this->assertStringNotContainsString("\n=HYPERLINK", $content);
    }

    public function test_deleting_a_guest_removes_its_rsvp_and_personal_link(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();
        $guest = Guest::factory()->for($wedding)->create();

        Rsvp::factory()->for($guest)->create();
        GuestInvitationLink::factory()->for($guest)->create();

        $this->actingAs($owner)
            ->delete(route('weddings.guests.destroy', [$wedding, $guest]))
            ->assertRedirect();

        $this->assertDatabaseMissing('guests', ['id' => $guest->getKey()]);
        $this->assertDatabaseCount('rsvps', 0);
        $this->assertDatabaseCount('guest_invitation_links', 0);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'guest.deleted',
            'resource_id' => $guest->getKey(),
        ]);
    }

    public function test_the_guest_screens_render_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();
        $guest = Guest::factory()->for($wedding)->create(['display_name' => 'Tamu Uji']);

        $this->actingAs($owner)
            ->get(route('weddings.guests.index', $wedding))
            ->assertOk()
            ->assertSee('Tamu Uji')
            ->assertSee('Total tamu')
            ->assertSee('Unduh template CSV')
            ->assertSee(route('weddings.guests.template', $wedding), false);

        $this->actingAs($owner)->get(route('weddings.guests.create', $wedding))->assertOk();
        $this->actingAs($owner)->get(route('weddings.guests.edit', [$wedding, $guest]))->assertOk();

        $this->actingAs($owner)
            ->get(route('weddings.show', $wedding))
            ->assertOk()
            ->assertSee('Kelola tamu');
    }
}
