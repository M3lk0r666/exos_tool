<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'upload.max_size_mb', 'value' => '50', 'type' => 'int', 'description' => 'Tamaño máximo de archivo tech-support (MB)'],
            ['key' => 'upload.allowed_extensions', 'value' => '["txt","log"]', 'type' => 'json', 'description' => 'Extensiones permitidas para carga'],
            ['key' => 'branding.company_name', 'value' => 'EXOS-Tool', 'type' => 'string', 'description' => 'Nombre de la empresa en reportes'],
            ['key' => 'branding.company_logo', 'value' => '', 'type' => 'string', 'description' => 'Ruta del logo de la empresa para reportes'],
            ['key' => 'branding.footer_text', 'value' => 'Colonia Tacubaya, Miguel Hidalgo, 11870. CDMX. Tel.: 55 1054-1184', 'type' => 'string', 'description' => 'Texto del pie de página en reportes PDF'],
            ['key' => 'notifications.notify_on_critical', 'value' => 'true', 'type' => 'bool', 'description' => 'Notificar hallazgos Critical/High al completar análisis'],
        ];

        foreach ($settings as $setting) {
            // firstOrCreate: no sobrescribe valores ya configurados por el usuario
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
