<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Insère des lignes avec des id explicites (cohérents avec mock-gateway.js
 * et les autres services), de façon idempotente (upsert) et recale la
 * séquence PostgreSQL après insertion.
 */
trait SeedsTables
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function seedTable(string $table, array $rows): void
    {
        if ($rows === [] || ! Schema::hasTable($table)) {
            return;
        }

        $now = now();
        $hasCreatedAt = Schema::hasColumn($table, 'created_at');
        $hasUpdatedAt = Schema::hasColumn($table, 'updated_at');

        $rows = array_map(
            function (array $row) use ($now, $hasCreatedAt, $hasUpdatedAt): array {
                if ($hasCreatedAt) {
                    $row += ['created_at' => $now];
                }
                if ($hasUpdatedAt) {
                    $row += ['updated_at' => $now];
                }

                return $row;
            },
            $rows,
        );

        // Sur conflit (id existant), on met à jour tout sauf l'id et created_at.
        $update = array_values(array_diff(array_keys($rows[0]), ['id', 'created_at']));

        DB::table($table)->upsert($rows, ['id'], $update);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1))"
            );
        }

        $this->command?->info(sprintf('%d ligne(s) → %s', count($rows), $table));
    }
}
