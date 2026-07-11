<?php

namespace Tests\Feature;

use App\Enums\CaptureStatus;
use App\Jobs\ProcessCaptureJob;
use App\Models\Capture;
use App\Models\Client;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessCaptureJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_parses_real_file_creates_device_and_metrics(): void
    {
        Storage::fake('local');

        $client = Client::factory()->create();

        $content = file_get_contents(base_path('tests/Fixtures/techsupport_standard_x440g2_exos22.txt'));
        $path = "captures/{$client->id}/test.txt";
        Storage::disk('local')->put($path, $content);

        $capture = Capture::create([
            'client_id' => $client->id,
            'uploaded_at' => now(),
            'original_filename' => 'techsupport.txt',
            'file_path' => $path,
            'file_hash' => hash('sha256', $content),
            'file_size' => strlen($content),
            'status' => CaptureStatus::Pending,
        ]);

        (new ProcessCaptureJob($capture))->handle(
            app(\App\Services\Parser\ExosParser::class),
            app(\App\Services\DeviceResolver::class),
            app(\App\Services\CaptureMetricsRecorder::class),
            app(\App\Services\Analysis\AnalysisEngine::class),
        );

        $capture->refresh();

        // Estado y campos normalizados
        $this->assertSame(CaptureStatus::Completed, $capture->status);
        $this->assertSame('22.7.1.2', $capture->exos_version);
        $this->assertSame(71, $capture->boot_count);
        // captured_at proviene del Current Time del archivo
        $this->assertSame('2026-07-02 19:39:18', $capture->captured_at->format('Y-m-d H:i:s'));

        // Equipo auto-creado por System MAC + SysName
        $device = Device::where('system_mac', '00:04:96:B4:44:65')->first();
        $this->assertNotNull($device);
        $this->assertSame('SWITCH-PB-Z4-Auditorio', $device->sysname);
        $this->assertSame('1910N-44546', $device->serial_number);
        $this->assertSame($client->id, $device->client_id);
        $this->assertSame($device->id, $capture->device_id);

        // Métricas normalizadas guardadas
        $this->assertGreaterThan(50, $capture->metrics()->count());
        $crc = $capture->metrics()
            ->where('category', 'ports')->where('entity', '32')->where('metric', 'crc_errors')
            ->value('value');
        $this->assertSame(39415678.0, (float) $crc);
    }

    public function test_job_marks_error_when_file_missing(): void
    {
        Storage::fake('local');

        $client = Client::factory()->create();

        $capture = Capture::create([
            'client_id' => $client->id,
            'uploaded_at' => now(),
            'original_filename' => 'missing.txt',
            'file_path' => 'captures/nope.txt',
            'file_hash' => str_repeat('a', 64),
            'file_size' => 10,
            'status' => CaptureStatus::Pending,
        ]);

        (new ProcessCaptureJob($capture))->handle(
            app(\App\Services\Parser\ExosParser::class),
            app(\App\Services\DeviceResolver::class),
            app(\App\Services\CaptureMetricsRecorder::class),
            app(\App\Services\Analysis\AnalysisEngine::class),
        );

        $capture->refresh();
        $this->assertSame(CaptureStatus::Error, $capture->status);
        $this->assertNotNull($capture->error_message);
    }
}
