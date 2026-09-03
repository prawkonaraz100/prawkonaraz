<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocialAccount extends Model
{
    public const PROVIDER_GOOGLE = 'google';

    public const PROVIDER_FACEBOOK = 'facebook';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'email',
        'display_name',
        'avatar_url',
        'linked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
