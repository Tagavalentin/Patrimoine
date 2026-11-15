<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ReportAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_mock_audit_logs(): void
    {
        $this->seed();

        $this->assertSame(3, ReportAuditLog::count());
        $this->assertTrue(
            ReportAuditLog::where('report_type', 'assets_csv')->where('rows_count', 8)->exists()
        );
        $this->assertTrue(
            ReportAuditLog::where('generated_by_email', 'auditeur@patrimoine.local')->exists()
        );
    }

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(3, ReportAuditLog::count());
    }
}
