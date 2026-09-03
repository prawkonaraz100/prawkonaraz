<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->plans() as $plan) {
            $values = [
                'name' => $plan['name'],
                'description' => $plan['description'],
                'price_gross_cents' => $plan['price_gross_cents'],
                'currency' => 'PLN',
                'access_days' => $plan['access_days'],
                'sort_order' => $plan['sort_order'],
                'is_active' => true,
                'updated_at' => $now,
            ];

            $exists = DB::table('product_plans')
                ->where('code', $plan['code'])
                ->exists();

            if ($exists) {
                DB::table('product_plans')
                    ->where('code', $plan['code'])
                    ->update($values);

                continue;
            }

            DB::table('product_plans')->insert([
                ...$values,
                'code' => $plan['code'],
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('product_plans')
            ->where('code', 'start-30')
            ->update([
                'name' => 'Plan Start',
                'description' => '30 dni dostępu do nauki, statystyk, powtórek i trybu rankingowego.',
                'price_gross_cents' => 4900,
                'access_days' => 30,
                'sort_order' => 10,
                'is_active' => true,
                'updated_at' => now(),
            ]);

        DB::table('product_plans')
            ->whereIn('code', ['start-90', 'start-365'])
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function plans(): array
    {
        return [
            [
                'code' => 'start-30',
                'name' => 'Dostęp na 1 miesiąc',
                'description' => '30 dni dostępu do nauki, statystyk, powtórek i trybu rankingowego.',
                'price_gross_cents' => 3900,
                'access_days' => 30,
                'sort_order' => 10,
            ],
            [
                'code' => 'start-90',
                'name' => 'Dostęp na 3 miesiące',
                'description' => '90 dni spokojnej nauki z powtórkami, analizą błędów i trybem rankingowym.',
                'price_gross_cents' => 6900,
                'access_days' => 90,
                'sort_order' => 20,
            ],
            [
                'code' => 'start-365',
                'name' => 'Dostęp na rok',
                'description' => '365 dni dostępu dla osób, które chcą uczyć się bez presji czasu.',
                'price_gross_cents' => 14900,
                'access_days' => 365,
                'sort_order' => 30,
            ],
        ];
    }
};
