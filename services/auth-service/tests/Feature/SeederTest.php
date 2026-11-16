<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_the_five_demo_accounts(): void
    {
        $this->seed();

        $this->assertSame(5, Role::count());
        $this->assertSame(5, User::count());

        $expected = [
            'admin@patrimoine.local' => 'admin',
            'n.diaye@patrimoine.local' => 'asset_manager',
            'm.fall@patrimoine.local' => 'accountant',
            'a.ba@patrimoine.local' => 'service_head',
            'auditeur@patrimoine.local' => 'auditor',
        ];

        foreach ($expected as $email => $roleSlug) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertSame($roleSlug, $user->role?->slug);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(5, User::count());
        $this->assertSame(5, Role::count());
    }
}
