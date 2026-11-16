<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsTables;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration alignées sur mock-gateway.js.
 * asset_code / asset_designation / asset_status sont des instantanés
 * du bien au moment de la demande (pas de FK vers asset-service).
 */
class DatabaseSeeder extends Seeder
{
    use SeedsTables;

    public function run(): void
    {
        $request = fn (int $id, string $reference, int $assetId, string $assetCode, string $assetDesignation, string $assetStatus, string $description, string $priority, string $status, ?int $requestedBy, ?string $requestedByName, ?string $requestedByEmail, ?string $scheduledDate, ?string $startedAt, ?string $completedAt, ?string $assignedToName, string $createdAt): array => [
            'id' => $id,
            'reference' => $reference,
            'asset_id' => $assetId,
            'asset_code' => $assetCode,
            'asset_designation' => $assetDesignation,
            'asset_status' => $assetStatus,
            'description' => $description,
            'priority' => $priority,
            'status' => $status,
            'scheduled_date' => $scheduledDate,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'requested_by' => $requestedBy,
            'requested_by_name' => $requestedByName,
            'requested_by_email' => $requestedByEmail,
            'assigned_to_name' => $assignedToName,
            'created_at' => $createdAt,
        ];

        $this->seedTable('maintenance_requests', [
            $request(1, 'MNT-2026-0001', 25, 'AST-2026-0009', 'Vidéoprojecteur Epson EB-2250U', 'acquired', 'Lampe de projection grillée.', 'normal', 'completed', 1, 'Admin Demo', 'admin@patrimoine.local', '2026-09-15', '2026-09-15 09:00:00', '2026-09-16 17:00:00', 'Tech Y', '2026-09-10 08:00:00'),
            $request(2, 'MNT-2026-0002', 19, 'AST-2023-0044', 'Véhicule Toyota Hilux Double Cabine', 'assigned', 'Climatisation du véhicule à réviser.', 'low', 'scheduled', 4, 'Mme Ba', 'a.ba@patrimoine.local', '2026-11-20', null, null, 'Garage Central', '2026-10-28 14:00:00'),
            $request(3, 'MNT-2026-0003', 14, 'AST-2026-0002', 'Serveur Dell PowerEdge R750', 'in_maintenance', 'Ventilateurs bruyants et température anormalement élevée.', 'high', 'in_progress', 2, 'M. Ndiaye', 'n.diaye@patrimoine.local', '2026-11-10', '2026-11-05 08:30:00', null, 'Tech X', '2026-11-01 10:00:00'),
            $request(4, 'MNT-2026-0004', 5, 'AST-2026-0005', 'Portable Dell Latitude 5540', 'assigned', 'Batterie qui se décharge anormalement vite.', 'normal', 'requested', 2, 'M. Ndiaye', 'n.diaye@patrimoine.local', null, null, null, null, '2026-11-08 09:30:00'),
        ]);

        $this->seedTable('interventions', [
            [
                'id' => 1,
                'maintenance_request_id' => 3,
                'technician_name' => 'Tech X',
                'description' => 'Nettoyage complet et remplacement des ventilateurs',
                'cost' => 150000,
                'performed_at' => '2026-11-05',
                'created_by' => 2,
            ],
            [
                'id' => 2,
                'maintenance_request_id' => 1,
                'technician_name' => 'Tech Y',
                'description' => "Remplacement de la lampe d'origine",
                'cost' => 95000,
                'performed_at' => '2026-09-16',
                'created_by' => 1,
            ],
        ]);
    }
}
