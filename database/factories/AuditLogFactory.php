<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'action' => 'admin.question.updated',
            'entity_type' => 'question',
            'entity_id' => (string) fake()->numberBetween(1, 9999),
            'request_id' => (string) fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => [
                'source' => 'admin_api',
            ],
            'created_at' => now(),
        ];
    }
}
