<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * Account state is deliberately absent: role changes, suspension and the
     * two-factor enrolment timestamp are written with forceFill() by the
     * controllers that own them, so no request payload can ever name them
     * (docs/SECURITY.md, section 3).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'crm_note',
        'crm_tags',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'suspended_at' => 'datetime',
            'crm_tags' => 'array',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function weddings(): HasMany
    {
        return $this->hasMany(Wedding::class, 'owner_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function testimonial(): HasOne
    {
        return $this->hasOne(Testimonial::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Swaps the framework's plain verification mail for the branded template;
     * the signed URL still comes from the parent notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        try {
            $this->notify(new VerifyEmailNotification);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * A secret only counts once the owner has proven one code from it, so a
     * half-finished setup can never lock the account out.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null
            && is_string($this->two_factor_secret)
            && $this->two_factor_secret !== '';
    }

    /**
     * @return list<string>
     */
    public function twoFactorRecoveryHashes(): array
    {
        $codes = $this->two_factor_recovery_codes;

        return is_array($codes) ? array_values(array_filter($codes, 'is_string')) : [];
    }
}
