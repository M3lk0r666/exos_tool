<?php

namespace Tests\Feature;

use App\Enums\CaptureStatus;
use App\Enums\ReportStatus;
use App\Jobs\ProcessCaptureJob;
use App\Models\Capture;
use App\Models\Client;
use App\Models\User;
use App\Services\Parser\ExosParser;
use App\Services\Reporting\ReportService;
use Database\Seeders\AnalyzerRuleSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 8: endurecimiento — flujo E2E completo y rendimiento con archivo grande.
 */
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_upload_analyze_report_issue(): void
    {
        Storage::fake('local');

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AnalyzerRuleSeeder::class);

        $engineer = User::factory()->create();
        $engineer->assignRole('engineer');

        $client = Client::factory()->create();

        // 1. Subida vía web (queue síncrona en tests: el job corre al despachar)
        $content = file_get_contents(base_path('tests/Fixtures/techsupport_standard_x440g2_exos22.txt'));

        $this->actingAs($engineer)->post(route('admin.captures.store'), [
            'client_id' => $client->id,
            'analysis_type' => 'tech_support',
            'files' => [UploadedFile::fake()->createWithContent('ts.txt', $content)],
        ])->assertSessionHas('success');

        // 2. Procesada y analizada
        $capture = Capture::first();
        $this->assertSame(CaptureStatus::Completed, $capture->status);
        $this->assertNotNull($capture->device_id);
        $this->assertGreaterThan(5, $capture->findings()->count());
        $this->assertGreaterThan(50, $capture->metrics()->count());

        // 3. Reporte: borrador → edición → emisión
        $this->actingAs($engineer)->get(route('admin.reports.for-capture', $capture));
        $report = $capture->reports()->first();

        $this->actingAs($engineer)->put(route('admin.reports.update', $report), [
            'executive_summary' => '<p>Resumen E2E</p>',
        ]);

        $this->actingAs($engineer)->post(route('admin.reports.issue', $report));

        $report->refresh();
        $this->assertSame(ReportStatus::Issued, $report->status);
        $this->assertNotNull($report->issued_at);
    }

    public function test_parser_handles_large_stack_file(): void
    {
        // Tech-support sintético grande: stack de 8 nodos con secciones repetidas
        // y ~40,000 líneas de log (≈ 6 MB), muy por encima de los archivos reales.
        $raw = file_get_contents(base_path('tests/Fixtures/techsupport_stack_x460_exos12.txt'));

        $logLines = '';
        for ($i = 0; $i < 50000; $i++) {
            $day = str_pad((string) (($i % 27) + 1), 2, '0', STR_PAD_LEFT);
            $logLines .= "06/{$day}/2026 10:00:00.10 <Info:vlan.msgs.portLinkStateUp> Slot-1: Port 1:".($i % 48 + 1)." link UP at speed 1 Gbps and full-duplex\n";
        }

        $rxRows = '';
        foreach (range(1, 8) as $slot) {
            foreach (range(1, 48) as $port) {
                $rxRows .= "{$slot}:{$port}       A        ".($port * 137)."       0        0        ".($port * 11)."        0          0          0\n";
            }
        }

        $big = $raw."\n-> show log\n".$logLines."\n-> show port rxerror no-refresh\nPort      Link\n".$rxRows;

        $this->assertGreaterThan(5_000_000, strlen($big), 'El archivo sintético debe superar 5 MB');

        $start = microtime(true);
        $parsed = (new ExosParser)->parse($big);
        $elapsed = microtime(true) - $start;

        // Correctitud: el parser procesó las secciones grandes completas
        $this->assertGreaterThanOrEqual(50000, $parsed->logTotal);
        $this->assertGreaterThanOrEqual(384, count($parsed->rxErrors)); // 8 slots x 48 puertos
        $this->assertSame('Stack', $parsed->sysName);

        // Guardia de rendimiento holgada (evita regresiones catastróficas O(n²))
        $this->assertLessThan(30, $elapsed, "Parseo de archivo de 6 MB tomó {$elapsed}s");
    }

    public function test_upload_rejects_files_over_configured_limit(): void
    {
        Storage::fake('local');

        $this->seed(RolePermissionSeeder::class);
        \App\Models\Setting::create(['key' => 'upload.max_size_mb', 'value' => '1', 'type' => 'int']);

        $engineer = User::factory()->create();
        $engineer->assignRole('engineer');
        $client = Client::factory()->create();

        $response = $this->actingAs($engineer)->post(route('admin.captures.store'), [
            'client_id' => $client->id,
            'analysis_type' => 'tech_support',
            'files' => [UploadedFile::fake()->create('grande.txt', 2048)], // 2 MB > 1 MB
        ]);

        $response->assertSessionHasErrors('files.0');
        $this->assertSame(0, Capture::count());
    }
}
