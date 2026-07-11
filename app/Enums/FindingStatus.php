<?php

namespace App\Enums;

enum FindingStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case FalsePositive = 'false_positive';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::Acknowledged => 'Reconocido',
            self::InProgress => 'En atención',
            self::Resolved => 'Resuelto',
            self::FalsePositive => 'Falso positivo',
        };
    }
}
