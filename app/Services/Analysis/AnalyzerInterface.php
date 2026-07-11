<?php

namespace App\Services\Analysis;

/**
 * Contrato del motor modular de análisis (sección 5.5 del prompt maestro).
 * Cada analyzer es independiente, lee sus umbrales desde analyzer_rules
 * (via AnalysisContext) y devuelve hallazgos con evidencia y ubicación.
 */
interface AnalyzerInterface
{
    /**
     * @return array<int, FindingData>
     */
    public function analyze(AnalysisContext $context): array;
}
