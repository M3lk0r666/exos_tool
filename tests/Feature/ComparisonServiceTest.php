<?php

namespace Tests\Feature;

use App\Models\Capture;
use App\Models\Client;
use App\Models\Device;
use App\Models\Finding;
use App\Models\Metric;
use App\Services\History\ComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparisonServiceTest extends TestCase
{
    use RefreshDatabase;

    private Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $client = Client::factory()->create();
        $this->device = Device::factory()->create(['client_id' => $client->id]);
    }

    private function makeCapture(int $uptime, array $summary = [], string $capturedAt = 'now'): Capture
    {
        return Capture::factory()->create([
            'client_id' => $this->device->client_id,
            'device_id' => $this->device->id,
            'captured_at' => now()->parse($capturedAt),
            'uptime_seconds' => $uptime,
            'raw_summary' => $summary,
        ]);
    }

    private function addPortMetric(Capture $capture, string $port, string $metric, float $value): void
    {
        Metric::create([
            'capture_id' => $capture->id,
            'category' => 'ports',
            'entity' => $port,
            'metric' => $metric,
            'value' => $value,
        ]);
    }

    public function test_deltas_are_computed_without_reboot(): void
    {
        $old = $this->makeCapture(1_000_000, capturedAt: '-1 month');
        $new = $this->makeCapture(3_600_000, capturedAt: 'now');

        $this->addPortMetric($old, '32', 'crc_errors', 100);
        $this->addPortMetric($new, '32', 'crc_errors', 350);
        // Puerto solo presente en la nueva (con exclude, ausente = 0)
        $this->addPortMetric($new, '15', 'crc_errors', 40);

        $result = app(ComparisonService::class)->compare($old, $new);

        $this->assertFalse($result['reboot_detected']);

        $rows = collect($result['ports']['rows']);
        $crc32 = $rows->first(fn ($r) => $r['port'] === '32' && $r['metric'] === 'CRC');
        $this->assertSame(250.0, $crc32['delta']);

        $crc15 = $rows->first(fn ($r) => $r['port'] === '15');
        $this->assertSame(40.0, $crc15['delta']); // ausente antes = 0
    }

    public function test_reboot_detected_suspends_counter_deltas(): void
    {
        // Uptime menor en la captura nueva => reinicio (Anexo A / sección 5.8)
        $old = $this->makeCapture(1_000_000, capturedAt: '-1 month');
        $new = $this->makeCapture(50_000, capturedAt: 'now');

        $this->addPortMetric($old, '32', 'crc_errors', 39_000_000);
        $this->addPortMetric($new, '32', 'crc_errors', 1_200);

        $result = app(ComparisonService::class)->compare($old, $new);

        $this->assertTrue($result['reboot_detected']);
        $this->assertTrue($result['ports']['reset']);

        $crc32 = collect($result['ports']['rows'])->first(fn ($r) => $r['port'] === '32');
        $this->assertNull($crc32['delta']); // sin delta: contadores reiniciados
        $this->assertSame(1_200.0, $crc32['new']);
    }

    public function test_captures_are_ordered_chronologically_even_if_swapped(): void
    {
        $old = $this->makeCapture(1_000_000, capturedAt: '-1 month');
        $new = $this->makeCapture(2_000_000, capturedAt: 'now');

        // Pasadas en orden invertido
        $result = app(ComparisonService::class)->compare($new, $old);

        $this->assertSame($old->id, $result['old']->id);
        $this->assertSame($new->id, $result['new']->id);
    }

    public function test_findings_diff_new_resolved_persisting(): void
    {
        $old = $this->makeCapture(1_000_000, capturedAt: '-1 month');
        $new = $this->makeCapture(2_000_000, capturedAt: 'now');

        $base = [
            'device_id' => $this->device->id,
            'level' => 'high', 'area' => 'ports',
            'title' => 't', 'description' => 'd',
        ];

        // Persistente: mismo rule+entity en ambas
        Finding::create($base + ['capture_id' => $old->id, 'rule_code' => 'PORT-CRC', 'entity' => '32']);
        Finding::create($base + ['capture_id' => $new->id, 'rule_code' => 'PORT-CRC', 'entity' => '32']);
        // Resuelto: solo en la anterior
        Finding::create($base + ['capture_id' => $old->id, 'rule_code' => 'PORT-FLAP', 'entity' => '29']);
        // Nuevo: solo en la nueva
        Finding::create($base + ['capture_id' => $new->id, 'rule_code' => 'ENV-FAN', 'entity' => 'Fan-1']);

        $diff = app(ComparisonService::class)->compare($old, $new)['findings'];

        $this->assertSame(['ENV-FAN'], $diff['new']->pluck('rule_code')->all());
        $this->assertSame(['PORT-FLAP'], $diff['resolved']->pluck('rule_code')->all());
        $this->assertSame(['PORT-CRC'], $diff['persisting']->pluck('rule_code')->all());
    }

    public function test_environment_rows_flag_memory_degradation(): void
    {
        $old = $this->makeCapture(1_000_000, [
            'memory' => ['Slot-1' => ['total_kb' => 1000, 'free_kb' => 800]],
            'temperatures' => [['unit' => 'Slot-1', 'temp' => 25.0, 'status' => 'Normal', 'min' => 0, 'max' => 55]],
        ], '-1 month');

        $new = $this->makeCapture(2_000_000, [
            'memory' => ['Slot-1' => ['total_kb' => 1000, 'free_kb' => 400]],
            'temperatures' => [['unit' => 'Slot-1', 'temp' => 41.0, 'status' => 'Normal', 'min' => 0, 'max' => 55]],
        ], 'now');

        $env = app(ComparisonService::class)->compare($old, $new)['environment'];

        $this->assertSame('worse', $env['Memoria libre Slot-1 (%)']['change']); // 80% -> 40%
        $this->assertSame('worse', $env['Temperatura Slot-1 (°C)']['change']);  // 25 -> 41
    }
}
