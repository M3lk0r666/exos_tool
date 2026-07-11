<?php

namespace App\Services\Analysis;

use App\Enums\FindingSeverity;

/**
 * Hallazgo producido por un analyzer, listo para persistirse en `findings`.
 */
class FindingData
{
    public function __construct(
        public readonly string $ruleCode,
        public readonly FindingSeverity $level,
        public readonly string $area,
        public readonly string $title,
        public readonly string $description,
        public readonly ?string $entity = null,
        public readonly ?string $impact = null,
        public readonly ?string $recommendation = null,
        public readonly ?string $evidence = null,
        public readonly ?string $fileLocation = null,
    ) {}

    public function toArray(): array
    {
        return [
            'rule_code' => $this->ruleCode,
            'level' => $this->level->value,
            'area' => $this->area,
            'entity' => $this->entity,
            'title' => $this->title,
            'description' => $this->description,
            'impact' => $this->impact,
            'recommendation' => $this->recommendation,
            'evidence' => $this->evidence,
            'file_location' => $this->fileLocation,
        ];
    }
}
