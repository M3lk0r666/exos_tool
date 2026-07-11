<?php

namespace Tests\Feature;

use App\Enums\FindingStatus;
use App\Models\Capture;
use App\Models\Client;
use App\Models\Device;
use App\Models\Finding;
use App\Services\Analysis\AnalysisEngine;
use App\Services\Parser\ExosParser;
use Database\Seeders\AnalyzerRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Device $device;

    private Capture $first;

    private Capture $second;

    private string $raw;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AnalyzerRuleSeeder::class);

        $client = Client::factory()->create();
        $this->device = Device::factory()->create(['client_id' => $client->id]);

        $this->first = Capture::factory()->create([
            'client_id' => $client->id,
            'device_id' => $this->device->id,
            'captured_at' => now()->subMonth(),
        ]);

        $this->second = Capture::factory()->create([
            'client_id' => $client->id,
            'device_id' => $this->device->id,
            'captured_at' => now(),
        ]);

        $this->raw = file_get_contents(base_path('tests/Fixtures/techsupport_standard_x440g2_exos22.txt'));

        // Primera captura analizada
        app(AnalysisEngine::class)->analyze($this->first, (new ExosParser)->parse($this->raw), $this->raw);
    }

    private function analyzeSecond(): void
    {
        app(AnalysisEngine::class)->analyze($this->second, (new ExosParser)->parse($this->raw), $this->raw);
    }

    public function test_recurring_finding_links_to_first_seen_capture(): void
    {
        $this->analyzeSecond();

        $original = Finding::where('capture_id', $this->first->id)
            ->where('rule_code', 'PORT-CRC')->where('entity', '32')->first();

        $recurring = Finding::where('capture_id', $this->second->id)
            ->where('rule_code', 'PORT-CRC')->where('entity', '32')->first();

        $this->assertNotNull($recurring);
        // Vinculado a la primera aparición, no duplicado como "nuevo"
        $this->assertSame($this->first->id, $recurring->first_seen_capture_id);
        $this->assertSame($this->first->id, $original->first_seen_capture_id);

        // Misma cantidad de hallazgos automáticos en ambas capturas (mismo archivo)
        $this->assertSame(
            Finding::where('capture_id', $this->first->id)->count(),
            Finding::where('capture_id', $this->second->id)->count()
        );
    }

    public function test_acknowledged_status_is_inherited(): void
    {
        Finding::where('capture_id', $this->first->id)
            ->where('rule_code', 'PORT-CRC')->where('entity', '32')
            ->update(['status' => 'acknowledged', 'status_notes' => 'Cable en proceso de reemplazo']);

        $this->analyzeSecond();

        $recurring = Finding::where('capture_id', $this->second->id)
            ->where('rule_code', 'PORT-CRC')->where('entity', '32')->first();

        $this->assertSame(FindingStatus::Acknowledged, $recurring->status);
        $this->assertSame('Cable en proceso de reemplazo', $recurring->status_notes);
    }

    public function test_resolved_finding_that_reappears_is_reopened(): void
    {
        Finding::where('capture_id', $this->first->id)
            ->where('rule_code', 'PORT-CRC')->where('entity', '32')
            ->update(['status' => 'resolved']);

        $this->analyzeSecond();

        $recurring = Finding::where('capture_id', $this->second->id)
            ->where('rule_code', 'PORT-CRC')->where('entity', '32')->first();

        $this->assertSame(FindingStatus::Open, $recurring->status);
        $this->assertStringContainsString('Reabierto automáticamente', (string) $recurring->status_notes);
    }

    public function test_false_positive_is_inherited(): void
    {
        Finding::where('capture_id', $this->first->id)
            ->where('rule_code', 'MGMT-SEC')
            ->update(['status' => 'false_positive', 'status_notes' => 'SNMP deshabilitado por política del cliente']);

        $this->analyzeSecond();

        $recurring = Finding::where('capture_id', $this->second->id)
            ->where('rule_code', 'MGMT-SEC')->first();

        $this->assertSame(FindingStatus::FalsePositive, $recurring->status);
    }
}
