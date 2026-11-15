<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Assignment;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_mock_referentials_and_assets(): void
    {
        $this->seed();

        $this->assertSame(4, Category::count());
        $this->assertSame(4, Department::count());
        $this->assertSame(4, Location::count());
        $this->assertSame(8, Asset::count());
        $this->assertSame(4, Assignment::count());

        $this->assertSame('AST-2026-0002', Asset::find(14)?->code);
        $this->assertSame('in_maintenance', Asset::find(14)?->status?->value);
        $this->assertSame(3, Assignment::whereNull('end_date')->count());
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(8, Asset::count());
        $this->assertSame(4, Assignment::count());
    }
}
