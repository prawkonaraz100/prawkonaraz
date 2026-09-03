<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIpHistory extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'source',
        'ip_address',
        'country_code',
        'country_name',
        'city_name',
        'user_agent',
        'path',
        'session_id',
        'request_id',
        'hit_count',
        'first_seen_at',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hit_count' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
