<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Intervention;
use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_mock_maintenance_data(): void
    {
        $this->seed();

        $this->assertSame(4, MaintenanceRequest::count());
        $this->assertSame(2, Intervention::count());

        $request = MaintenanceRequest::find(3);
        $this->assertSame('MNT-2026-0003', $request?->reference);
        $this->assertSame('in_progress', $request?->status?->value);
        $this->assertSame('Tech X', $request?->assigned_to_name);
        $this->assertSame(1, $request?->interventions()->count());
    }

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(4, MaintenanceRequest::count());
        $this->assertSame(2, Intervention::count());
    }
}
