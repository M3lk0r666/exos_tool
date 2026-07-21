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

        // Eventos por día para el gráfico de incidentes (orden cronológico m/d/Y)
        $perDay = $capture->raw_summary['logs']['per_day'] ?? [];
        $this->assertNotEmpty($perDay);
        $this->assertGreaterThan(0, array_sum($perDay));
        $this->assertLessThanOrEqual($capture->raw_summary['logs']['total'], array_sum($perDay));
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

    public function test_epm_cpu_and_watched_warnings_generate_findings(): void
    {
        Storage::fake('local');

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AnalyzerRuleSeeder::class);

        $engineer = User::factory()->create();
        $engineer->assignRole('engineer');
        $client = Client::factory()->create();

        // Eventos reales reportados por GTAC en el caso del cliente
        $content = "* SW.1 # show log\n".
            "07/20/2026 18:39:17.01 <Warn:EPM.cpu> CPU utilization monitor: process hal consumes 95 % CPU\n".
            "07/20/2026 18:38:57.02 <Warn:EPM.cpu> CPU utilization monitor: process hal consumes 92 % CPU\n".
            "07/20/2026 18:38:42.05 <Warn:EPM.cpu> CPU utilization monitor: process hal consumes 92 % CPU\n".
            "06/18/2026 20:21:08.75 <Warn:HAL.Card.Warning> Slot-6 is not present to do card exec cmd POWER_OFF\n".
            "06/18/2026 20:21:05.89 <Warn:HAL.Card.Warning> Slot-6 is not present to do card exec cmd REBOOT\n".
            "07/20/2026 10:00:00.00 <Info:AAA.authPass> Login passed for user admin\n";

        // Tormenta de caídas de link en el puerto 1:5 (incidente pasado; estado actual OK)
        for ($i = 0; $i < 60; $i++) {
            $content .= "07/20/2026 12:".str_pad((string) ($i % 60), 2, '0', STR_PAD_LEFT).":00.00 <Info:vlan.msgs.portLinkStateDown> Port 1:5 link down\n";
        }
        // Puerto con pocas caídas: no debe alertar
        $content .= "07/20/2026 12:00:01.00 <Info:vlan.msgs.portLinkStateDown> Port 1:9 link down\n";

        $this->actingAs($engineer)->post(route('admin.captures.store'), [
            'client_id' => $client->id,
            'analysis_type' => 'log',
            'files' => [UploadedFile::fake()->createWithContent('gtac_case.txt', $content)],
        ]);

        $capture = Capture::first();

        // LOG-CPU: proceso hal al 95 % => High (umbral crítico 70)
        $cpu = $capture->findings()->where('rule_code', 'LOG-CPU')->first();
        $this->assertNotNull($cpu);
        $this->assertSame('hal', $cpu->entity);
        $this->assertSame('high', $cpu->level->value);
        $this->assertStringContainsString('95 %', $cpu->title);
        $this->assertStringContainsString('3 alerta(s)', $cpu->description);

        // LOG-WARN: componente vigilado HAL.Card.Warning
        $warn = $capture->findings()->where('rule_code', 'LOG-WARN')->first();
        $this->assertNotNull($warn);
        $this->assertSame('HAL.Card.Warning', $warn->entity);
        $this->assertStringContainsString('Slot-6 is not present', $warn->description);

        // Los Info no generan hallazgos de warning
        $this->assertSame(0, $capture->findings()->where('entity', 'AAA.authPass')->count());

        // LOG-FLAP: 60 caídas del puerto 1:5 registradas en el log => Medium
        $flap = $capture->findings()->where('rule_code', 'LOG-FLAP')->first();
        $this->assertNotNull($flap);
        $this->assertSame('1:5', $flap->entity);
        $this->assertSame('medium', $flap->level->value);
        // El puerto con 1 caída no alerta
        $this->assertSame(0, $capture->findings()->where('rule_code', 'LOG-FLAP')->where('entity', '1:9')->count());
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
