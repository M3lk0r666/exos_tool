<?php

namespace Tests\Feature;

use App\Models\AnalyzerRule;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AnalyzerRuleSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $engineer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AnalyzerRuleSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->engineer = User::factory()->create();
        $this->engineer->assignRole('engineer');
    }

    public function test_admin_can_edit_rule_thresholds(): void
    {
        $rule = AnalyzerRule::where('code', 'PORT-CRC')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.rules.update', $rule), [
                'threshold_warning' => 500,
                'threshold_critical' => 50000,
                'level_warning' => 'low',
                'level_critical' => 'critical',
                'enabled' => '1',
            ])
            ->assertSessionHas('success');

        $rule->refresh();
        $this->assertSame(500.0, (float) $rule->threshold_warning);
        $this->assertSame('critical', $rule->level_critical);
        $this->assertTrue(AuditLog::where('action', 'updated')->exists());
    }

    public function test_engineer_cannot_manage_rules_or_settings(): void
    {
        $rule = AnalyzerRule::first();

        $this->actingAs($this->engineer)
            ->get(route('admin.rules.index'))->assertForbidden();

        $this->actingAs($this->engineer)
            ->put(route('admin.settings.update'), ['settings' => []])->assertForbidden();
    }

    public function test_admin_can_update_settings_with_type_validation(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'upload.max_size_mb' => '100',
                    'branding.company_name' => 'Mi Empresa SA',
                    'upload.allowed_extensions' => 'no-es-json', // debe ignorarse
                ],
            ])
            ->assertSessionHas('success');

        $this->assertSame(100, Setting::get('upload.max_size_mb'));
        $this->assertSame('Mi Empresa SA', Setting::get('branding.company_name'));
        $this->assertSame(['txt', 'log'], Setting::get('upload.allowed_extensions')); // sin cambio
    }

    public function test_admin_can_view_audit_log(): void
    {
        AuditLog::create(['user_id' => $this->admin->id, 'action' => 'created']);

        $this->actingAs($this->admin)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('created');

        $this->actingAs($this->engineer)
            ->get(route('admin.audit.index'))
            ->assertForbidden();
    }
}
