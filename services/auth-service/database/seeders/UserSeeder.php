<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Database\Seeders\Concerns\SeedsTables;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use SeedsTables;

    /**
     * Comptes de démonstration alignés sur mock-gateway.js.
     * Mot de passe : SEED_USER_PASSWORD, sinon « password » (local
     * uniquement, refusé en production). Les ids sont explicites pour
     * rester cohérents avec les responsible_user_id / requested_by
     * des autres services.
     *
     * @var array<int, array{id: int, name: string, email: string, role: string}>
     */
    private const USERS = [
        ['id' => 1, 'name' => 'Admin Demo', 'email' => 'admin@patrimoine.local', 'role' => 'admin'],
        ['id' => 2, 'name' => 'M. Ndiaye', 'email' => 'n.diaye@patrimoine.local', 'role' => 'asset_manager'],
        ['id' => 3, 'name' => 'M. Fall', 'email' => 'm.fall@patrimoine.local', 'role' => 'accountant'],
        ['id' => 4, 'name' => 'Mme Ba', 'email' => 'a.ba@patrimoine.local', 'role' => 'service_head'],
        ['id' => 5, 'name' => 'Auditeur', 'email' => 'auditeur@patrimoine.local', 'role' => 'auditor'],
    ];

    public function run(): void
    {
        $plainPassword = env('SEED_USER_PASSWORD');

        if ($plainPassword === null) {
            if (app()->environment('production')) {
                throw new \RuntimeException('SEED_USER_PASSWORD est obligatoire en production.');
            }

            $plainPassword = 'password'; // démo locale uniquement
        }

        $password = Hash::make($plainPassword);
        $verifiedAt = now();

        $rows = array_map(
            fn (array $user): array => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => $verifiedAt,
                'password' => $password,
                'role_id' => Role::where('slug', $user['role'])->value('id'),
            ],
            self::USERS,
        );

        $this->seedTable('users', $rows);
    }
}
