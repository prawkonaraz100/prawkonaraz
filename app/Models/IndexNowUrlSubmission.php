<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexNowUrlSubmission extends Model
{
    protected $table = 'indexnow_url_submissions';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_DELETED = 'deleted';

    protected $fillable = [
        'url',
        'url_hash',
        'status',
        'source',
        'event_type',
        'available_at',
        'last_enqueued_at',
        'last_submitted_at',
        'enqueued_count',
        'attempts',
        'last_http_status',
        'last_response_reason',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'available_at' => 'datetime',
            'last_enqueued_at' => 'datetime',
            'last_submitted_at' => 'datetime',
            'enqueued_count' => 'integer',
            'attempts' => 'integer',
            'last_http_status' => 'integer',
        ];
    }

    public static function hashUrl(string $url): string
    {
        return hash('sha256', $url);
    }
}
