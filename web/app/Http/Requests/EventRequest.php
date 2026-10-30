<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Wedding;
use App\Models\WeddingEvent;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->wedding()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['required', Rule::in(['akad', 'resepsi', 'other'])],
            'title' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'venue_name' => ['required', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:500'],
            'map_url' => ['nullable', 'url', 'max:2048'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    /**
     * Times arrive from a datetime-local input that follows the event's own
     * timezone. They are stored in UTC so comparisons and expiry checks stay
     * unambiguous; the event timezone is kept for display.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validated();
        $timezone = $data['timezone'] ?? 'Asia/Jakarta';

        $data['timezone'] = $timezone;
        $data['display_order'] ??= 0;
        $data['starts_at'] = CarbonImmutable::parse($data['starts_at'], $timezone)->utc();
        $data['ends_at'] = isset($data['ends_at'])
            ? CarbonImmutable::parse($data['ends_at'], $timezone)->utc()
            : null;

        return $data;
    }

    public function wedding(): Wedding
    {
        return $this->route('wedding');
    }

    public function event(): ?WeddingEvent
    {
        return $this->route('event');
    }
}
