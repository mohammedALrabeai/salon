<?php

namespace Tests\Feature;

use App\Http\Middleware\ApiTokenAuth;
use App\Models\Branch;
use App\Models\DailyEntry;
use App\Models\User;
use App\Support\SimpleExcelExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_employee_roles_exclude_manager(): void
    {
        $this->assertSame(['barber', 'receptionist', 'other'], User::employeeRoles());
    }

    public function test_daily_entries_appear_in_overview_and_manager_reports(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
        ]);

        $branch = Branch::query()->create([
            'name' => 'Main Branch',
            'code' => 'BR-001',
            'status' => 'active',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $employee = User::factory()->create([
            'role' => 'barber',
            'status' => 'active',
            'branch_id' => $branch->id,
            'commission_rate' => 50,
        ]);

        DailyEntry::query()->create([
            'branch_id' => $branch->id,
            'user_id' => $employee->id,
            'date' => now()->toDateString(),
            'sales' => 320,
            'cash' => 40,
            'expense' => 20,
            'payment_type' => 'cash',
            'commission' => 160,
            'commission_rate' => 50,
            'bonus' => 15,
            'transactions_count' => 3,
            'source' => 'api',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $this->withoutMiddleware(ApiTokenAuth::class);
        $this->actingAs($manager);

        $overviewResponse = $this->getJson('/api/v1/reports/overview?period=today');

        $overviewResponse
            ->assertOk()
            ->assertJsonPath('data.overview.summary.total_sales', 320)
            ->assertJsonPath('data.overview.summary.entries_count', 1)
            ->assertJsonPath('data.snapshots.today.summary.total_sales', 320)
            ->assertJsonPath('data.snapshots.month.summary.total_sales', 320);

        $managerTodayResponse = $this->getJson('/api/v1/reports/manager?scope=overview&period=today');

        $managerTodayResponse
            ->assertOk()
            ->assertJsonPath('data.summary.total_sales', 320)
            ->assertJsonPath('data.summary.entries_count', 1)
            ->assertJsonPath('data.entries.0.user.id', $employee->id)
            ->assertJsonPath('data.entries.0.sales', 320);

        $managerMonthResponse = $this->getJson('/api/v1/reports/manager?scope=overview&period=month');

        $managerMonthResponse
            ->assertOk()
            ->assertJsonPath('data.summary.total_sales', 320)
            ->assertJsonPath('data.summary.entries_count', 1)
            ->assertJsonPath('data.employees_breakdown.0.user_id', $employee->id);
    }

    public function test_employee_report_can_be_exported_as_excel(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
        ]);

        $branch = Branch::query()->create([
            'name' => 'Main Branch',
            'code' => 'BR-001',
            'status' => 'active',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $employee = User::factory()->create([
            'role' => 'barber',
            'status' => 'active',
            'branch_id' => $branch->id,
            'commission_rate' => 45,
        ]);

        DailyEntry::query()->create([
            'branch_id' => $branch->id,
            'user_id' => $employee->id,
            'date' => now()->toDateString(),
            'sales' => 500,
            'cash' => 50,
            'expense' => 10,
            'payment_type' => 'network',
            'commission' => 225,
            'commission_rate' => 45,
            'bonus' => 20,
            'transactions_count' => 4,
            'source' => 'api',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $this->withoutMiddleware(ApiTokenAuth::class);
        $this->actingAs($manager);

        $response = $this->get('/api/v1/reports/employee-export?employee_id=' . $employee->id . '&period=today');

        $response->assertOk();
        $response->assertHeader('content-type', SimpleExcelExporter::CONTENT_TYPE);
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }
}
