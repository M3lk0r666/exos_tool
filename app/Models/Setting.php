<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    /** Obtiene un setting tipado por clave (tolerante a BD sin migrar). */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::query()->where('key', $key)->first();
        } catch (\Illuminate\Database\QueryException) {
            return $default;
        }

        if ($setting === null) {
            return $default;
        }

        return match ($setting->type) {
            'int' => (int) $setting->value,
            'float' => (float) $setting->value,
            'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }
}
