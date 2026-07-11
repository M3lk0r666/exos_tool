<?php

namespace App\Enums;

enum CaptureStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Processing => 'Procesando',
            self::Completed => 'Completado',
            self::Error => 'Error',
        };
    }

    /** Clases de badge Flowbite por estado. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            self::Processing => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            self::Completed => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::Error => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        };
    }
}
