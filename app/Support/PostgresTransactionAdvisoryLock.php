<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use LogicException;

final class PostgresTransactionAdvisoryLock
{
    /**
     * @param  iterable<string>  $keys
     */
    public function acquire(iterable $keys, ?Connection $connection = null): void
    {
        $connection ??= DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        if ($connection->transactionLevel() < 1) {
            throw new LogicException('PostgreSQL transaction advisory locks require an active transaction.');
        }

        $normalizedKeys = [];

        foreach ($keys as $key) {
            $key = trim((string) $key);

            if ($key !== '') {
                $normalizedKeys[] = $key;
            }
        }

        $normalizedKeys = array_values(array_unique($normalizedKeys));
        sort($normalizedKeys, SORT_STRING);

        foreach ($normalizedKeys as $key) {
            $connection->select(
                'select pg_advisory_xact_lock(hashtextextended(?, 0))',
                [$key],
            );
        }
    }
}
