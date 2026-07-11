<?php

namespace Tests\Feature;

use App\Enums\CaptureStatus;
use App\Jobs\ProcessCaptureJob;
use App\Models\Capture;
use App\Models\Client;
use App\Models\User;
use App\Notifications\CriticalFindingsDetected;
use Database\Seeders\AnalyzerRuleSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CriticalNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function runJobWithContent(string $content): Capture
    {
        Storage::fake('local');

        $client = Client::factory()->create();
        $path = "captures/{$client->id}/test.txt";
        Storage::disk('local')->put($path, $content);

        $capture = Capture::create([
            'client_id' => $client->id,
            'uploaded_at' => now(),
            'original_filename' => 'test.txt',
            'file_path' => $path,
            'file_hash' => hash('sha256', $content.uniqid()),
            'file_size' => strlen($content),
            'status' => CaptureStatus::Pending,
        ]);

        (new ProcessCaptureJob($capture))->handle(
            app(\App\Services\Parser\ExosParser::class),
            app(\App\Services\DeviceResolver::class),
            app(\App\Services\CaptureMetricsRecorder::class),
            app(\App\Services\Analysis\AnalysisEngine::class),
        );

        return $capture->fresh();
    }

    public function test_notifies_admins_and_engineers_when_high_findings_detected(): void
    {
        Notification::fake();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AnalyzerRuleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $engineer = User::factory()->create();
        $engineer->assignRole('engineer');
        $reader = User::factory()->create();
        $reader->assignRole('reader');

        // El archivo real standard genera hallazgos High (PORT-CRC puerto 32, FW-AGE...)
        $this->runJobWithContent(
            file_get_contents(base_path('tests/Fixtures/techsupport_standard_x440g2_exos22.txt'))
        );

        Notification::assertSentTo([$admin, $engineer], CriticalFindingsDetected::class);
        Notification::assertNotSentTo($reader, CriticalFindingsDetected::class);
    }

    public function test_no_notification_when_no_critical_or_high_findings(): void
    {
        Notification::fake();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AnalyzerRuleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Archivo mínimo sano: sin hallazgos Critical/High
        $this->runJobWithContent(
            "->show switch\nSysName: SW-OK\nSystem Type: X440G2-24t\n".
            "System MAC: 00:04:96:11:22:33\nCurrent Time: Mon Jan  5 10:00:00 2026\n"
        );

        Notification::assertNothingSent();
    }
}
