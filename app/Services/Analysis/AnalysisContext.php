<?php

namespace App\Services\Analysis;

use App\Enums\FindingSeverity;
use App\Models\AnalyzerRule;
use App\Services\Parser\ParsedTechSupport;
use Illuminate\Support\Collection;

/**
 * Contexto compartido entre analyzers: resultado del parser, reglas
 * parametrizadas desde BD y acceso al texto original para evidencia.
 */
class AnalysisContext
{
    /** @var array<int, string>|null */
    private ?array $rawLines = null;

    /**
     * @param  Collection<string, AnalyzerRule>  $rules  indexadas por code
     */
    public function __construct(
        public readonly ParsedTechSupport $parsed,
        public readonly Collection $rules,
        private readonly string $rawText = '',
    ) {}

    /** Regla habilitada por código, o null si no existe o está deshabilitada. */
    public function rule(string $code): ?AnalyzerRule
    {
        $rule = $this->rules->get($code);

        return ($rule !== null && $rule->enabled) ? $rule : null;
    }

    /**
     * Severidad según los umbrales de la regla (dirección configurable:
     * "above" por defecto, "below" para métricas como memoria libre).
     */
    public function severityFor(AnalyzerRule $rule, float $value): ?FindingSeverity
    {
        $direction = $rule->params['direction'] ?? 'above';

        $crossed = function (?string $threshold) use ($value, $direction): bool {
            if ($threshold === null) {
                return false;
            }

            return $direction === 'below'
                ? $value <= (float) $threshold
                : $value >= (float) $threshold;
        };

        if ($crossed($rule->threshold_critical) && $rule->level_critical) {
            return FindingSeverity::from($rule->level_critical);
        }

        if ($crossed($rule->threshold_warning)) {
            return FindingSeverity::from($rule->level_warning);
        }

        return null;
    }

    /**
     * Busca la primera línea que contenga $needle y devuelve el fragmento
     * con su número de línea en el archivo original (evidencia verificable).
     *
     * @return array{text: string, line: int}|null
     */
    public function findEvidence(string $needle, int $contextLines = 0): ?array
    {
        $lines = $this->rawLines ??= preg_split('/\r\n|\r|\n/', $this->rawText);

        foreach ($lines as $i => $line) {
            if (str_contains($line, $needle)) {
                $from = max(0, $i - $contextLines);
                $to = min(count($lines) - 1, $i + $contextLines);
                $fragment = implode("\n", array_slice($lines, $from, $to - $from + 1));

                return ['text' => trim($fragment), 'line' => $i + 1];
            }
        }

        return null;
    }

    /**
     * Evidencia por expresión regular (primera coincidencia).
     *
     * @return array{text: string, line: int}|null
     */
    public function findEvidenceRegex(string $pattern): ?array
    {
        $lines = $this->rawLines ??= preg_split('/\r\n|\r|\n/', $this->rawText);

        foreach ($lines as $i => $line) {
            if (preg_match($pattern, $line)) {
                return ['text' => trim($line), 'line' => $i + 1];
            }
        }

        return null;
    }

    /** Referencia documental del umbral (params.reference), si existe. */
    public function reference(AnalyzerRule $rule): ?string
    {
        return $rule->params['reference'] ?? null;
    }
}
