<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InventoryCampaign;
use App\Models\InventoryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_mock_inventory_data(): void
    {
        $this->seed();

        $this->assertSame(2, InventoryCampaign::count());
        $this->assertSame(8, InventoryLine::count());

        $campaign = InventoryCampaign::find(7);
        $this->assertSame('INV-2026-0007', $campaign?->reference);
        $this->assertSame('in_progress', $campaign?->status?->value);
        $this->assertSame(8, $campaign?->lines()->count());
        $this->assertSame(3, InventoryLine::whereIn('check_status', ['missing', 'damaged'])->count());
    }

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(2, InventoryCampaign::count());
        $this->assertSame(8, InventoryLine::count());
    }
}
