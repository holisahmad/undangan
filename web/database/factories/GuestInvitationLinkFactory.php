<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guest;
use App\Models\GuestInvitationLink;
use App\Support\Guests\GuestToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GuestInvitationLink>
 */
class GuestInvitationLinkFactory extends Factory
{
    protected $model = GuestInvitationLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'token_hash' => GuestToken::hash(Str::random(GuestToken::LENGTH)),
            'expires_at' => null,
            'revoked_at' => null,
        ];
    }

    /**
     * Creates a link whose raw token is known to the caller, which is how a
     * guest would receive it.
     */
    public function withToken(string $rawToken): static
    {
        return $this->state(fn (): array => ['token_hash' => GuestToken::hash($rawToken)]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }
}
