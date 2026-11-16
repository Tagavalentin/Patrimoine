<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsTables;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration alignées sur mock-gateway.js.
 * Les lignes ne concernent que la campagne 7 (en cours) comme dans le mock.
 */
class DatabaseSeeder extends Seeder
{
    use SeedsTables;

    public function run(): void
    {
        $campaign = fn (int $id, string $reference, string $name, ?int $departmentId, ?string $departmentName, ?int $locationId, ?string $locationName, string $status, ?string $startedAt, ?string $completedAt): array => [
            'id' => $id,
            'reference' => $reference,
            'name' => $name,
            'department_id' => $departmentId,
            'department_name' => $departmentName,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'status' => $status,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'created_by' => 1,
            'created_by_name' => 'Admin Demo',
            'created_by_email' => 'admin@patrimoine.local',
        ];

        $this->seedTable('inventory_campaigns', [
            $campaign(6, 'INV-2026-0006', 'Inventaire du siège Dakar', null, null, 1, 'Siège Dakar', 'completed', '2026-06-01 09:00:00', '2026-06-30 17:00:00'),
            $campaign(7, 'INV-2026-0007', 'Inventaire annuel 2026', null, null, null, null, 'in_progress', '2026-11-01 10:00:00', null),
        ]);

        // Instantanés des 8 biens du mock : [asset_id, code, désignation, statut,
        // service, lieu, constat, notes, contrôleur]
        $lines = [
            [5, 'AST-2026-0005', 'Portable Dell Latitude 5540', 'assigned', 'DSI', 'Siège Dakar', 'found', null, 'Mme Ba'],
            [8, 'AST-2025-0012', 'Imprimante HP LaserJet Pro M404', 'acquired', 'Finances', 'Annexe Pikine', 'pending', null, 'Mme Ba'],
            [11, 'AST-2024-0007', 'Fauteuil ergonomique Steelcase', 'assigned', 'Ressources Humaines', 'Siège Dakar', 'missing', 'Bien introuvable au service', 'Mme Ba'],
            [14, 'AST-2026-0002', 'Serveur Dell PowerEdge R750', 'in_maintenance', 'DSI', 'Entrepôt central', 'damaged', null, 'Mme Ba'],
            [19, 'AST-2023-0044', 'Véhicule Toyota Hilux Double Cabine', 'assigned', 'Ressources Humaines', 'Siège Dakar', 'found', null, 'Mme Ba'],
            [22, 'AST-2022-0018', 'Échographe Samsung Medison', 'decommissioned', 'Service Santé', 'Direction régionale Thiès', 'pending', null, null],
            [25, 'AST-2026-0009', 'Vidéoprojecteur Epson EB-2250U', 'acquired', null, 'Siège Dakar', 'found', null, null],
            [30, 'AST-2021-0033', 'Bureau direction en bois massif', 'disposed', 'Finances', 'Siège Dakar', 'missing', null, null],
        ];

        $rows = [];
        foreach ($lines as $i => [$assetId, $code, $designation, $status, $department, $location, $check, $notes, $checkedByName]) {
            $checked = $checkedByName !== null;
            $rows[] = [
                'id' => 100 + $i,
                'inventory_campaign_id' => 7,
                'asset_id' => $assetId,
                'asset_code' => $code,
                'asset_designation' => $designation,
                'asset_status' => $status,
                'department_name' => $department,
                'location_name' => $location,
                'check_status' => $check,
                'notes' => $notes,
                'checked_by' => $checked ? 4 : null,
                'checked_by_name' => $checkedByName,
                'checked_at' => $checked ? '2026-11-05 10:00:00' : null,
            ];
        }

        $this->seedTable('inventory_lines', $rows);
    }
}
