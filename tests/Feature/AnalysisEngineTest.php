<?php

namespace Tests\Feature;

use App\Enums\FindingSeverity;
use App\Models\Capture;
use App\Models\Client;
use App\Models\Device;
use App\Models\Finding;
use App\Services\Analysis\AnalysisEngine;
use App\Services\Parser\ExosParser;
use Database\Seeders\AnalyzerRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests golden-file del motor de análisis sobre los tech-support reales.
 * Los hallazgos esperados corresponden a problemas verificados manualmente
 * en los archivos (Anexo B + correlaciones).
 */
class AnalysisEngineTest extends TestCase
{
    use RefreshDatabase;

    private function analyzeFixture(string $fixture): Capture
    {
        $this->seed(AnalyzerRuleSeeder::class);

        $client = Client::factory()->create();
        $device = Device::factory()->create(['client_id' => $client->id]);

        $capture = Capture::factory()->create([
            'client_id' => $client->id,
            'device_id' => $device->id,
        ]);

        $raw = file_get_contents(base_path("tests/Fixtures/{$fixture}"));
        $parsed = (new ExosParser)->parse($raw);

        app(AnalysisEngine::class)->analyze($capture, $parsed, $raw);

        return $capture;
    }

    public function test_standard_file_produces_expected_findings(): void
    {
        $capture = $this->analyzeFixture('techsupport_standard_x440g2_exos22.txt');
        $findings = Finding::where('capture_id', $capture->id)->get();

        // PORT-CRC High en el puerto 32 (39.4M CRC ≥ 10,000)
        $crc = $findings->firstWhere(fn ($f) => $f->rule_code === 'PORT-CRC' && $f->entity === '32');
        $this->assertNotNull($crc);
        $this->assertSame(FindingSeverity::High, $crc->level);
        $this->assertNotNull($crc->evidence);
        $this->assertStringContainsString('línea', (string) $crc->file_location);

        // PORT-FRAG High en el puerto 32 (59.2M fragmentos)
        $this->assertNotNull($findings->firstWhere(
            fn ($f) => $f->rule_code === 'PORT-FRAG' && $f->entity === '32' && $f->level === FindingSeverity::High
        ));

        // PORT-FLAP Medium en el puerto 29 (1,955 transiciones)
        $flap = $findings->firstWhere(fn ($f) => $f->rule_code === 'PORT-FLAP' && $f->entity === '29');
        $this->assertNotNull($flap);
        $this->assertSame(FindingSeverity::Medium, $flap->level);

        // PORT-10M en 5 puertos (6, 15, 17, 29, 33)
        $tenM = $findings->where('rule_code', 'PORT-10M');
        $this->assertCount(5, $tenM);
        $this->assertContains('29', $tenM->pluck('entity')->all());

        // FW-AGE High: imagen compilada en 2019, captura de 2026 (~7 años ≥ 5)
        $fw = $findings->firstWhere('rule_code', 'FW-AGE');
        $this->assertNotNull($fw);
        $this->assertSame(FindingSeverity::High, $fw->level);

        // LOG-ERR High: 200 eventos Erro (SNMP deshabilitado)
        $logErr = $findings->firstWhere('rule_code', 'LOG-ERR');
        $this->assertNotNull($logErr);
        $this->assertSame(FindingSeverity::High, $logErr->level);

        // MGMT-SEC: SSH deshabilitado + sin NTP
        $mgmt = $findings->firstWhere('rule_code', 'MGMT-SEC');
        $this->assertNotNull($mgmt);
        $this->assertStringContainsString('SSH', $mgmt->description);
        $this->assertStringContainsString('NTP', $mgmt->description);

        // Correlación (b): CRC + fragmentos en el puerto 32 ⇒ capa física
        $phy = $findings->firstWhere(fn ($f) => $f->rule_code === 'CORR-PHY' && $f->entity === '32');
        $this->assertNotNull($phy);
        $this->assertSame(FindingSeverity::High, $phy->level);

        // Correlación (c): flapping + 10 Mbps en el puerto 29 ⇒ cableado
        $cable = $findings->firstWhere(fn ($f) => $f->rule_code === 'CORR-CABLE' && $f->entity === '29');
        $this->assertNotNull($cable);

        // NO debe haber falsos positivos en reglas sanas:
        $this->assertNull($findings->firstWhere('rule_code', 'ENV-TEMP'));   // 61.5°C, margen 48.5°C
        $this->assertNull($findings->firstWhere('rule_code', 'CPU-1H'));     // 1.0 %
        $this->assertNull($findings->firstWhere('rule_code', 'MEM-FREE'));   // 68 % libre
        $this->assertNull($findings->firstWhere('rule_code', 'ENV-FAN'));    // 4 fans OK
        $this->assertNull($findings->firstWhere('rule_code', 'PWR-PSU'));    // PSU OK (Empty no es falla)
        $this->assertNull($findings->firstWhere('rule_code', 'SYS-REBOOT')); // sin reinicios
        $this->assertNull($findings->firstWhere('rule_code', 'HW-AGE'));     // 5.3 años < 7
    }

    public function test_stack_file_produces_expected_findings(): void
    {
        $capture = $this->analyzeFixture('techsupport_stack_x460_exos12.txt');
        $findings = Finding::where('capture_id', $capture->id)->get();

        // FW-AGE High: imagen de 2011, captura de 2026 (~15 años)
        $fw = $findings->firstWhere('rule_code', 'FW-AGE');
        $this->assertNotNull($fw);
        $this->assertSame(FindingSeverity::High, $fw->level);

        // PORT-10M en 14 puertos del stack
        $this->assertCount(14, $findings->where('rule_code', 'PORT-10M'));

        // MGMT-SEC: SNMP deshabilitado + sin NTP
        $this->assertNotNull($findings->firstWhere('rule_code', 'MGMT-SEC'));

        // HW-AGE: odómetros de ~12.9 años (4707 días ≥ 7 años) en 4 slots
        $this->assertCount(4, $findings->where('rule_code', 'HW-AGE'));

        // PORT-CRC High en 1:28 (45,561) y 3:40 (322,816); 3:41 (4) y 4:17 (1)
        // quedan bajo el umbral de 100. El script Python de referencia NO veía
        // estos puertos (bug de alias de comando en 12.x): caso golden-file real.
        $crcPorts = $findings->where('rule_code', 'PORT-CRC');
        $this->assertCount(2, $crcPorts);
        $this->assertEqualsCanonicalizing(['1:28', '3:40'], $crcPorts->pluck('entity')->all());
        $this->assertTrue($crcPorts->every(fn ($f) => $f->level === FindingSeverity::High));

        // La evidencia debe ser la fila de rxerrors (con el contador), no la de txerrors
        $crc128 = $crcPorts->firstWhere('entity', '1:28');
        $this->assertStringContainsString('45561', (string) $crc128->evidence);

        // PORT-FRAG High en los mismos puertos (11,420 y 79,167 fragmentos)
        $this->assertEqualsCanonicalizing(
            ['1:28', '3:40'],
            $findings->where('rule_code', 'PORT-FRAG')->pluck('entity')->all()
        );

        // Correlación (b): CRC + fragmentos ⇒ capa física dañada en ambos puertos
        $this->assertEqualsCanonicalizing(
            ['1:28', '3:40'],
            $findings->where('rule_code', 'CORR-PHY')->pluck('entity')->all()
        );

        // Sin falsos positivos:
        $this->assertNull($findings->firstWhere('rule_code', 'ENV-TEMP'));  // 25°C vs máx 55°C
        $this->assertNull($findings->firstWhere('rule_code', 'ENV-FAN'));   // 16 fans OK, trays Empty permitidos
        $this->assertNull($findings->firstWhere('rule_code', 'PWR-PSU'));   // 4 PSU en P
        $this->assertNull($findings->firstWhere('rule_code', 'SEC-AUTH'));  // 2 authFail < umbral 5
    }

    public function test_reprocessing_is_idempotent_and_preserves_manual_findings(): void
    {
        $capture = $this->analyzeFixture('techsupport_standard_x440g2_exos22.txt');
        $automatic = Finding::where('capture_id', $capture->id)->count();

        // Hallazgo manual del ingeniero
        Finding::create([
            'capture_id' => $capture->id,
            'device_id' => $capture->device_id,
            'rule_code' => 'MANUAL',
            'level' => 'low',
            'area' => 'ports',
            'title' => 'Observación manual',
            'description' => 'Anotación del ingeniero.',
            'is_manual' => true,
        ]);

        // Reprocesar la misma captura
        $raw = file_get_contents(base_path('tests/Fixtures/techsupport_standard_x440g2_exos22.txt'));
        app(AnalysisEngine::class)->analyze($capture, (new ExosParser)->parse($raw), $raw);

        // Mismos hallazgos automáticos (sin duplicar) + el manual intacto
        $this->assertSame($automatic, Finding::where('capture_id', $capture->id)->where('is_manual', false)->count());
        $this->assertSame(1, Finding::where('capture_id', $capture->id)->where('is_manual', true)->count());
    }

    public function test_disabled_rule_produces_no_findings(): void
    {
        $this->seed(AnalyzerRuleSeeder::class);
        \App\Models\AnalyzerRule::where('code', 'PORT-CRC')->update(['enabled' => false]);

        $client = Client::factory()->create();
        $capture = Capture::factory()->create(['client_id' => $client->id]);

        $raw = file_get_contents(base_path('tests/Fixtures/techsupport_standard_x440g2_exos22.txt'));
        app(AnalysisEngine::class)->analyze($capture, (new ExosParser)->parse($raw), $raw);

        $this->assertSame(0, Finding::where('capture_id', $capture->id)->where('rule_code', 'PORT-CRC')->count());
        // Las demás reglas siguen operando
        $this->assertGreaterThan(0, Finding::where('capture_id', $capture->id)->count());
    }
}
