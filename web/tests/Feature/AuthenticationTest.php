<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_an_account_and_signs_the_user_in(): void
    {
        Notification::fake();

        $response = $this->post(route('register'), [
            'name' => 'Ayu Lestari',
            'email' => 'ayu@example.test',
            'password' => 'rahasia-123',
            'password_confirmation' => 'rahasia-123',
        ]);

        // A new account must confirm its address before reaching the customer area.
        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ayu@example.test', 'role' => 'customer']);

        Notification::assertSentTo(
            User::query()->where('email', 'ayu@example.test')->sole(),
            VerifyEmailNotification::class,
        );
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ayu@example.test']);

        $this->post(route('register'), [
            'name' => 'Ayu Lestari',
            'email' => 'ayu@example.test',
            'password' => 'rahasia-123',
            'password_confirmation' => 'rahasia-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_user_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create(['email' => 'bima@example.test']);

        $this->post(route('login'), [
            'email' => 'bima@example.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'bima@example.test']);

        $this->post(route('login'), [
            'email' => 'bima@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guests_are_redirected_away_from_private_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('weddings.index'))->assertRedirect(route('login'));
    }
}
