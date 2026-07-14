<?php

namespace Tests\Feature;

use App\Enums\CaptureStatus;
use App\Models\Capture;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\AnalyzerRuleSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Análisis de archivos de solo log (show log) con el archivo real Log_SW_E7. */
class LogAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_file_is_analyzed_and_generates_findings(): void
    {
        Storage::fake('local');

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AnalyzerRuleSeeder::class);

        $engineer = User::factory()->create();
        $engineer->assignRole('engineer');
        $client = Client::factory()->create();

        $content = file_get_contents(base_path('tests/Fixtures/showlog_sw_e7.txt'));

        $this->actingAs($engineer)->post(route('admin.captures.store'), [
            'client_id' => $client->id,
            'analysis_type' => 'log',
            'files' => [UploadedFile::fake()->createWithContent('Log_SW_E7.txt', $content)],
        ])->assertSessionHas('success');

        $capture = Capture::first();
        $this->assertSame('log', $capture->analysis_type);
        $this->assertSame(CaptureStatus::Completed, $capture->status);
        // Sin identificación del equipo: no se asocia device automáticamente
        $this->assertNull($capture->device_id);

        // El archivo real registra reinicios inesperados (EPM.UnexpctRebootDtect)
        // en 1 fecha única => severidad Media según la regla SYS-REBOOT
        $reboot = $capture->findings()->where('rule_code', 'SYS-REBOOT')->first();
        $this->assertNotNull($reboot);
        $this->assertSame('medium', $reboot->level->value);
        $this->assertStringContainsString('03/23/2026', (string) $reboot->description);

        // Métricas de log registradas
        $this->assertGreaterThan(0, (float) $capture->metrics()
            ->where('category', 'logs')->where('metric', 'unexpected_reboots')->value('value'));

        // Advertencia explícita del alcance del análisis de solo log
        $this->assertStringContainsString(
            'solo log',
            implode(' ', $capture->parser_warnings ?? [])
        );
    }

    public function test_log_with_identity_headers_creates_device(): void
    {
        Storage::fake('local');

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AnalyzerRuleSeeder::class);

        $engineer = User::factory()->create();
        $engineer->assignRole('engineer');
        $client = Client::factory()->create();

        // Log con encabezados de identificación pegados al inicio
        $content = "->show switch\n".
            "SysName:          SW-E7\n".
            "System Type:      X620-16x\n".
            "System MAC:       00:04:96:DE:AD:07\n".
            "Current Time:     Mon Mar 23 18:45:00 2026\n".
            "->show version\n".
            "Switch      : 800600-00-01 2222X-11111 Rev 1 BootROM: 1.0.0.1    IMG: 31.7.2.28\n".
            "Image   : ExtremeXOS version 31.7.2.28 by release-manager\n".
            "          on Wed May 10 10:00:00 EDT 2023\n".
            "->show log\n".
            file_get_contents(base_path('tests/Fixtures/showlog_sw_e7.txt'));

        $this->actingAs($engineer)->post(route('admin.captures.store'), [
            'client_id' => $client->id,
            'analysis_type' => 'log',
            'files' => [UploadedFile::fake()->createWithContent('log_con_identidad.txt', $content)],
        ]);

        $capture = Capture::first();
        $this->assertSame(CaptureStatus::Completed, $capture->status);

        // Equipo creado con serie, modelo y versión desde los encabezados
        $this->assertNotNull($capture->device_id);
        $this->assertSame('SW-E7', $capture->device->sysname);
        $this->assertSame('2222X-11111', $capture->device->serial_number);
        $this->assertSame('31.7.2.28', $capture->exos_version);
        $this->assertSame('2026-03-23 18:45:00', $capture->captured_at->format('Y-m-d H:i:s'));

        // Y el log sigue analizándose (reinicios detectados)
        $this->assertNotNull($capture->findings()->where('rule_code', 'SYS-REBOOT')->first());
    }

    public function test_upload_requires_analysis_type(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $engineer = User::factory()->create();
        $engineer->assignRole('engineer');
        $client = Client::factory()->create();

        $this->actingAs($engineer)
            ->from(route('admin.captures.create'))
            ->post(route('admin.captures.store'), [
                'client_id' => $client->id,
                'files' => [UploadedFile::fake()->createWithContent('x.txt', 'contenido')],
            ])
            ->assertSessionHasErrors('analysis_type');
    }
}
