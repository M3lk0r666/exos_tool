<?php

namespace App\Enums;

enum FindingSeverity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Informational = 'informational';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Crítico',
            self::High => 'Alto',
            self::Medium => 'Medio',
            self::Low => 'Bajo',
            self::Informational => 'Informativo',
        };
    }

    /** Clases de badge Flowbite por severidad. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Critical => 'bg-red-600 text-white dark:bg-red-700 dark:text-white',
            self::High => 'bg-red-100 text-red-800 border border-red-300 dark:bg-red-900 dark:text-red-300',
            self::Medium => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            self::Low => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            self::Informational => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /** Peso para ordenar de mayor a menor severidad. */
    public function weight(): int
    {
        return match ($this) {
            self::Critical => 5,
            self::High => 4,
            self::Medium => 3,
            self::Low => 2,
            self::Informational => 1,
        };
    }
}
