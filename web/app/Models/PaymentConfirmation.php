<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentConfirmationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Customer-submitted transfer proof. The stored path is private: it is only
 * readable through the admin proof download route.
 */
class PaymentConfirmation extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id',
        'payment_id',
        'submitted_by',
        'sender_name',
        'sender_bank',
        'transferred_at',
        'amount',
        'note',
        'proof_path',
        'proof_original_name',
        'proof_mime',
        'proof_size',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentConfirmationStatus::class,
            'transferred_at' => 'date',
            'amount' => 'integer',
            'proof_size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function amountLabel(): string
    {
        return 'Rp'.number_format($this->amount, 0, ',', '.');
    }
}
