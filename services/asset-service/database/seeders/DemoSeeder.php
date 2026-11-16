<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsTables;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration alignées sur mock-gateway.js (source de vérité
 * du frontend) : mêmes ids pour que les références croisées entre
 * services (asset_id, responsible_user_id…) restent valides.
 */
class DemoSeeder extends Seeder
{
    use SeedsTables;

    public function run(): void
    {
        $this->seedTable('categories', [
            ['id' => 1, 'name' => 'Informatique', 'code' => 'INFO', 'description' => 'Matériel informatique et bureautique'],
            ['id' => 2, 'name' => 'Mobilier', 'code' => 'MOB', 'description' => 'Mobilier de bureau'],
            ['id' => 3, 'name' => 'Véhicules', 'code' => 'VEH', 'description' => 'Véhicules de service'],
            ['id' => 4, 'name' => 'Équipements médicaux', 'code' => 'MED', 'description' => 'Matériel médical'],
        ]);

        $this->seedTable('departments', [
            ['id' => 1, 'name' => 'DSI', 'code' => 'DSI', 'description' => 'Direction des Systèmes d\'Information'],
            ['id' => 2, 'name' => 'Finances', 'code' => 'FIN', 'description' => 'Service financier'],
            ['id' => 3, 'name' => 'Ressources Humaines', 'code' => 'RH', 'description' => 'Gestion des ressources humaines'],
            ['id' => 4, 'name' => 'Service Santé', 'code' => 'SAN', 'description' => 'Service de santé'],
        ]);

        $this->seedTable('locations', [
            ['id' => 1, 'name' => 'Siège Dakar', 'code' => 'DKR', 'type' => 'site', 'city' => 'Dakar'],
            ['id' => 2, 'name' => 'Annexe Pikine', 'code' => 'PKN', 'type' => 'site', 'city' => 'Pikine'],
            ['id' => 3, 'name' => 'Direction régionale Thiès', 'code' => 'THS', 'type' => 'site', 'city' => 'Thiès'],
            ['id' => 4, 'name' => 'Entrepôt central', 'code' => 'ENT', 'type' => 'site', 'city' => 'Dakar'],
        ]);

        $asset = fn (int $id, string $code, string $designation, int $category, int $location, ?int $department, string $date, float $value, int $life, string $status, ?string $description, string $createdAt): array => [
            'id' => $id,
            'code' => $code,
            'designation' => $designation,
            'category_id' => $category,
            'location_id' => $location,
            'department_id' => $department,
            'acquisition_date' => $date,
            'acquisition_value' => $value,
            'useful_life_years' => $life,
            'status' => $status,
            'description' => $description,
            'created_at' => $createdAt,
        ];

        $this->seedTable('assets', [
            $asset(5, 'AST-2026-0005', 'Portable Dell Latitude 5540', 1, 1, 1, '2024-01-15', 850000, 3, 'assigned', 'Portable de travail pour les agents de la DSI.', '2024-01-16 09:00:00'),
            $asset(8, 'AST-2025-0012', 'Imprimante HP LaserJet Pro M404', 1, 2, 2, '2025-03-10', 320000, 5, 'acquired', 'Imprimante multifonction du service financier.', '2025-03-11 10:30:00'),
            $asset(11, 'AST-2024-0007', 'Fauteuil ergonomique Steelcase', 2, 1, 3, '2024-06-20', 180000, 8, 'assigned', null, '2024-06-21 08:15:00'),
            $asset(14, 'AST-2026-0002', 'Serveur Dell PowerEdge R750', 1, 4, 1, '2026-02-01', 4500000, 5, 'in_maintenance', 'Serveur de production des applications internes.', '2026-02-02 14:00:00'),
            $asset(19, 'AST-2023-0044', 'Véhicule Toyota Hilux Double Cabine', 3, 1, 3, '2023-05-15', 18500000, 7, 'assigned', 'Véhicule de service pour les missions extérieures.', '2023-05-16 11:00:00'),
            $asset(22, 'AST-2022-0018', 'Échographe Samsung Medison', 4, 3, 4, '2022-09-01', 7200000, 8, 'decommissioned', 'Matériel médical réformé, en attente de cession.', '2022-09-02 09:45:00'),
            $asset(25, 'AST-2026-0009', 'Vidéoprojecteur Epson EB-2250U', 1, 1, null, '2026-08-20', 540000, 4, 'acquired', null, '2026-08-21 16:20:00'),
            $asset(30, 'AST-2021-0033', 'Bureau direction en bois massif', 2, 1, 2, '2021-01-10', 250000, 10, 'disposed', 'Cédé lors de la réorganisation des bureaux.', '2021-01-11 10:00:00'),
        ]);

        // Pas de colonne is_active : une affectation est active tant que
        // end_date est NULL (cf. Assignment::isActive()).
        $assignment = fn (int $id, int $assetId, int $departmentId, ?int $userId, ?string $name, ?string $email, string $start, ?string $end, ?string $reason): array => [
            'id' => $id,
            'asset_id' => $assetId,
            'department_id' => $departmentId,
            'responsible_user_id' => $userId,
            'responsible_name' => $name,
            'responsible_email' => $email,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $reason,
            'created_by' => 1,
        ];

        $this->seedTable('assignments', [
            $assignment(9, 30, 2, 3, 'M. Fall', 'm.fall@patrimoine.local', '2021-02-01', '2024-12-31', 'Affectation initiale'),
            $assignment(10, 5, 1, 2, 'M. Ndiaye', 'n.diaye@patrimoine.local', '2024-02-01', null, 'Affectation initiale à la DSI'),
            $assignment(11, 11, 3, 4, 'Mme Ba', 'a.ba@patrimoine.local', '2024-07-01', null, 'Mobilier du service RH'),
            $assignment(12, 19, 3, 4, 'Mme Ba', 'a.ba@patrimoine.local', '2023-06-01', null, 'Véhicule de service'),
        ]);
    }
}
