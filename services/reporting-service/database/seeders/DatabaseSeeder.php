<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsTables;
use Illuminate\Database\Seeder;

/**
 * Journaux d'audit de démonstration alignés sur mock-gateway.js.
 * report_type correspond aux types d'export du mock (assets_csv…).
 */
class DatabaseSeeder extends Seeder
{
    use SeedsTables;

    public function run(): void
    {
        $log = fn (int $id, string $reportType, int $rowsCount, ?int $userId, ?string $name, ?string $email, ?string $ip, string $createdAt): array => [
            'id' => $id,
            'report_type' => $reportType,
            'format' => 'csv',
            'filters' => null,
            'rows_count' => $rowsCount,
            'generated_by' => $userId,
            'generated_by_name' => $name,
            'generated_by_email' => $email,
            'ip_address' => $ip,
            'created_at' => $createdAt,
        ];

        $this->seedTable('report_audit_logs', [
            $log(1, 'assets_csv', 8, 1, 'Admin Demo', 'admin@patrimoine.local', '172.18.0.1', '2026-11-12 09:00:00'),
            $log(2, 'maintenance_csv', 4, 1, 'Admin Demo', 'admin@patrimoine.local', '172.18.0.1', '2026-11-11 16:30:00'),
            $log(3, 'inventory_csv', 8, 5, 'Auditeur', 'auditeur@patrimoine.local', '172.18.0.5', '2026-11-10 11:15:00'),
        ]);
    }
}
