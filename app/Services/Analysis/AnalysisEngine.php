<?php

namespace App\Services\Analysis;

use App\Models\AnalyzerRule;
use App\Models\Capture;
use App\Models\Finding;
use App\Services\Analysis\Analyzers\CpuAnalyzer;
use App\Services\Analysis\Analyzers\FanAnalyzer;
use App\Services\Analysis\Analyzers\FirmwareAnalyzer;
use App\Services\Analysis\Analyzers\HardwareAnalyzer;
use App\Services\Analysis\Analyzers\LogsAnalyzer;
use App\Services\Analysis\Analyzers\ManagementAnalyzer;
use App\Services\Analysis\Analyzers\MemoryAnalyzer;
use App\Services\Analysis\Analyzers\OpticsAnalyzer;
use App\Services\Analysis\Analyzers\PortsAnalyzer;
use App\Services\Analysis\Analyzers\PowerAnalyzer;
use App\Services\Analysis\Analyzers\SecurityAnalyzer;
use App\Services\Analysis\Analyzers\StackAnalyzer;
use App\Services\Analysis\Analyzers\TemperatureAnalyzer;
use App\Services\Parser\ParsedTechSupport;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orquestador del motor de análisis: ejecuta los analyzers registrados,
 * aplica el motor de correlación y persiste los hallazgos de la captura.
 */
class AnalysisEngine
{
    /** @var array<int, class-string<AnalyzerInterface>> */
    private const ANALYZERS = [
        PortsAnalyzer::class,
        LogsAnalyzer::class,
        CpuAnalyzer::class,
        MemoryAnalyzer::class,
        TemperatureAnalyzer::class,
        FanAnalyzer::class,
        PowerAnalyzer::class,
        FirmwareAnalyzer::class,
        HardwareAnalyzer::class,
        StackAnalyzer::class,
        OpticsAnalyzer::class,
        ManagementAnalyzer::class,
        SecurityAnalyzer::class,
    ];

    public function __construct(
        private readonly CorrelationEngine $correlationEngine = new CorrelationEngine,
        private readonly FindingLifecycleService $lifecycle = new FindingLifecycleService,
    ) {}

    /**
     * Analiza una captura ya parseada y persiste los hallazgos.
     * Reprocesar es idempotente: reemplaza los hallazgos automáticos previos.
     *
     * @return int cantidad de hallazgos generados
     */
    public function analyze(Capture $capture, ParsedTechSupport $parsed, string $rawText = ''): int
    {
        $rules = AnalyzerRule::query()->get()->keyBy('code');
        $context = new AnalysisContext($parsed, $rules, $rawText);

        /** @var array<int, FindingData> $findings */
        $findings = [];

        foreach (self::ANALYZERS as $analyzerClass) {
            try {
                $analyzer = app($analyzerClass);
                $findings = array_merge($findings, $analyzer->analyze($context));
            } catch (Throwable $e) {
                Log::warning('Analyzer falló y se omitió', [
                    'analyzer' => $analyzerClass,
                    'capture_id' => $capture->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Motor de correlación sobre los hallazgos individuales.
        $findings = array_merge($findings, $this->correlationEngine->correlate($context, $findings));

        // Idempotencia: elimina hallazgos automáticos previos de esta captura
        // (los manuales del ingeniero se conservan).
        Finding::where('capture_id', $capture->id)->where('is_manual', false)->delete();

        // Ciclo de vida (sección 5.8): vincular hallazgos recurrentes con su
        // primera aparición y heredar el estado de seguimiento del ingeniero.
        $previousFindings = $this->lifecycle->previousFindingsFor($capture);

        foreach ($findings as $finding) {
            Finding::create(
                $finding->toArray()
                + $this->lifecycle->resolve($capture, $finding, $previousFindings)
                + [
                    'capture_id' => $capture->id,
                    'device_id' => $capture->device_id,
                    'is_manual' => false,
                ]
            );
        }

        return count($findings);
    }
}
