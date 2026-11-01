<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Marketing\WeddingServices;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Marketing enquiry from the public site. Attribution is first-touch only and
 * deliberately stores no IP, user agent, or browsing history.
 */
class Lead extends Model
{
    use HasUuids;

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'message',
        'source',
        'service_interest',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'status',
    ];

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    /**
     * What the visitor asked about, in the wording the catalogue uses. A slug
     * that no longer names a service is reported as such instead of being
     * dropped: the enquiry is still real and the reader has to see why it came.
     */
    public function serviceInterestLabel(): ?string
    {
        $slug = $this->service_interest;

        if (! is_string($slug) || trim($slug) === '') {
            return null;
        }

        return WeddingServices::label($slug) ?? $slug;
    }
}
