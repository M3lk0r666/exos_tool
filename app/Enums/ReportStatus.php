<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Issued => 'Emitido',
        };
    }
}
