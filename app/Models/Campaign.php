<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'user_id',
        'name',
        'subject',
        'body',
        'status',
        'scheduled_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaignSends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function subscribers()
    {
        return $this->belongsToMany(Subscriber::class, 'campaign_sends')
            ->withPivot(['status', 'sent_at'])
            ->withTimestamps();
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->total_recipients === 0) {
            return 0;
        }
    
        return (int) round(
            (($this->sent_count + $this->failed_count) / $this->total_recipients) * 100
        );
    }
}
