<?php

namespace Tests\Feature;

use App\Models\Capture;
use App\Models\Client;
use App\Models\Device;
use App\Models\Finding;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $engineer;

    private Capture $capture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->engineer = User::factory()->create();
        $this->engineer->assignRole('engineer');

        $client = Client::factory()->create(['name' => 'Cliente Uno']);
        $device = Device::factory()->create(['client_id' => $client->id]);

        $this->capture = Capture::factory()->create([
            'client_id' => $client->id,
            'device_id' => $device->id,
            'raw_summary' => ['sysname' => 'SW-1'],
        ]);

        Finding::factory()->create([
            'capture_id' => $this->capture->id,
            'device_id' => $device->id,
            'rule_code' => 'PORT-CRC',
            'level' => 'critical',
            'area' => 'ports',
            'entity' => '32',
            'status' => 'open',
        ]);

        $this->capture->metrics()->create([
            'category' => 'ports', 'entity' => '32', 'metric' => 'crc_errors', 'value' => 1000,
        ]);
    }

    public function test_dashboard_shows_stats_and_worst_devices(): void
    {
        $this->actingAs($this->engineer)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Análisis realizados')
            ->assertSee('Cliente Uno')                 // cliente con hallazgos abiertos
            ->assertSee('Equipos que requieren atención')
            ->assertSee('Crítico');
    }

    public function test_client_page_shows_device_semaphore(): void
    {
        $this->actingAs($this->engineer)
            ->get(route('admin.clients.show', $this->capture->client_id))
            ->assertOk()
            ->assertSee('Estado')
            ->assertSee('Crítico');
    }

    public function test_json_export_contains_findings_and_metrics(): void
    {
        $response = $this->actingAs($this->engineer)
            ->get(route('admin.captures.export.json', $this->capture))
            ->assertOk()
            ->assertHeader('Content-Disposition');

        $data = $response->json();

        $this->assertSame('Cliente Uno', $data['capture']['client']);
        $this->assertSame('PORT-CRC', $data['findings'][0]['rule_code']);
        $this->assertSame('crc_errors', $data['metrics']['ports'][0]['metric']);
        $this->assertSame(1000.0, $data['metrics']['ports'][0]['value']);
    }

    public function test_excel_export_degrades_gracefully_without_package(): void
    {
        $response = $this->actingAs($this->engineer)
            ->from(route('admin.captures.show', $this->capture))
            ->get(route('admin.captures.export.excel', $this->capture));

        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            $response->assertOk();
        } else {
            $response->assertRedirect(route('admin.captures.show', $this->capture));
        }
    }
}
