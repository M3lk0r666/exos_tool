<?php

namespace Tests\Feature;

use App\Enums\FindingSeverity;
use App\Enums\ReportStatus;
use App\Models\Capture;
use App\Models\Client;
use App\Models\Device;
use App\Models\Finding;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $engineer;

    private User $reader;

    private Capture $capture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->engineer = User::factory()->create();
        $this->engineer->assignRole('engineer');

        $this->reader = User::factory()->create();
        $this->reader->assignRole('reader');

        $client = Client::factory()->create();
        $device = Device::factory()->create(['client_id' => $client->id]);
        $this->capture = Capture::factory()->create([
            'client_id' => $client->id,
            'device_id' => $device->id,
        ]);

        Finding::factory()->create([
            'capture_id' => $this->capture->id,
            'device_id' => $device->id,
            'rule_code' => 'PORT-CRC',
            'level' => 'high',
            'area' => 'ports',
            'entity' => '32',
        ]);
    }

    public function test_first_visit_creates_draft_v1(): void
    {
        $response = $this->actingAs($this->engineer)
            ->get(route('admin.reports.for-capture', $this->capture));

        $report = Report::where('capture_id', $this->capture->id)->first();
        $this->assertNotNull($report);
        $this->assertSame(1, $report->version);
        $this->assertSame(ReportStatus::Draft, $report->status);

        $response->assertRedirect(route('admin.reports.show', $report));

        $this->actingAs($this->engineer)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('Estado por área')
            ->assertSee('Puertos');
    }

    public function test_engineer_can_update_report_sections(): void
    {
        $report = app(\App\Services\Reporting\ReportService::class)->draftFor($this->capture);

        $this->actingAs($this->engineer)
            ->put(route('admin.reports.update', $report), [
                'executive_summary' => '<p>Resumen de prueba</p>',
                'conclusions' => '<p>Conclusión</p>',
                'recommendations' => '<p>Recomendación</p>',
            ])
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('<p>Resumen de prueba</p>', $report->executive_summary);
    }

    public function test_engineer_can_edit_finding_severity_and_add_manual(): void
    {
        $finding = Finding::first();

        $this->actingAs($this->engineer)
            ->put(route('admin.findings.update', $finding), [
                'level' => 'critical',
                'status' => 'acknowledged',
                'title' => $finding->title,
                'description' => $finding->description,
            ])
            ->assertSessionHas('success');

        $finding->refresh();
        $this->assertSame(FindingSeverity::Critical, $finding->level);
        $this->assertSame($this->engineer->id, $finding->edited_by);

        // Hallazgo manual
        $this->actingAs($this->engineer)
            ->post(route('admin.findings.store', $this->capture), [
                'level' => 'low',
                'area' => 'management',
                'title' => 'Observación de sitio',
                'description' => 'Gabinete sin organización de cables.',
            ])
            ->assertSessionHas('success');

        $manual = Finding::where('is_manual', true)->first();
        $this->assertNotNull($manual);
        $this->assertSame('MANUAL', $manual->rule_code);
    }

    public function test_issue_freezes_report_and_new_version_copies_content(): void
    {
        $service = app(\App\Services\Reporting\ReportService::class);
        $report = $service->draftFor($this->capture);
        $report->update(['executive_summary' => '<p>Contenido v1</p>']);

        $this->actingAs($this->engineer)
            ->post(route('admin.reports.issue', $report))
            ->assertRedirect(route('admin.reports.show', $report));

        $report->refresh();
        $this->assertSame(ReportStatus::Issued, $report->status);
        $this->assertSame($this->engineer->id, $report->issued_by);
        $this->assertNotNull($report->issued_at);

        // Emitido ya no es editable
        $this->actingAs($this->engineer)
            ->put(route('admin.reports.update', $report), ['executive_summary' => '<p>Cambio</p>'])
            ->assertForbidden();

        // Nueva versión copia el contenido
        $this->actingAs($this->engineer)
            ->post(route('admin.reports.new-version', $report));

        $draft = Report::where('capture_id', $this->capture->id)->orderByDesc('version')->first();
        $this->assertSame(2, $draft->version);
        $this->assertSame(ReportStatus::Draft, $draft->status);
        $this->assertSame('<p>Contenido v1</p>', $draft->executive_summary);
    }

    public function test_reader_can_view_but_not_edit(): void
    {
        $report = app(\App\Services\Reporting\ReportService::class)->draftFor($this->capture);

        $this->actingAs($this->reader)
            ->get(route('admin.reports.show', $report))
            ->assertOk();

        $this->actingAs($this->reader)
            ->put(route('admin.reports.update', $report), ['executive_summary' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->reader)
            ->post(route('admin.reports.issue', $report))
            ->assertForbidden();

        $finding = Finding::first();
        $this->actingAs($this->reader)
            ->delete(route('admin.findings.destroy', $finding))
            ->assertForbidden();
    }
}
