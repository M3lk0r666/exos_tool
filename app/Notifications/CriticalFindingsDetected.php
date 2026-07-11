<?php

namespace App\Notifications;

use App\Models\Capture;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación UI + correo cuando un análisis detecta hallazgos
 * Critical/High (sección 5.10).
 */
class CriticalFindingsDetected extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Capture $capture,
        public readonly int $criticalCount,
        public readonly int $highCount,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (Setting::get('notifications.notify_on_critical', true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $device = $this->capture->device?->displayName() ?? 'Equipo';
        $client = $this->capture->client?->name ?? '';

        return (new MailMessage)
            ->subject("[EXOS-Tool] Hallazgos críticos en {$device} ({$client})")
            ->greeting("Hola {$notifiable->name},")
            ->line("El análisis de la captura #{$this->capture->id} del equipo {$device} ".
                "({$client}) detectó hallazgos que requieren atención:")
            ->line("Críticos: {$this->criticalCount} · Altos: {$this->highCount}")
            ->action('Ver análisis', route('admin.captures.show', $this->capture))
            ->line('Revisa el detalle y genera el reporte para el cliente.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'capture_id' => $this->capture->id,
            'device' => $this->capture->device?->displayName(),
            'client' => $this->capture->client?->name,
            'critical' => $this->criticalCount,
            'high' => $this->highCount,
            'url' => route('admin.captures.show', $this->capture),
            'message' => sprintf(
                '%s (%s): %d crítico(s), %d alto(s) en la captura #%d',
                $this->capture->device?->displayName() ?? 'Equipo',
                $this->capture->client?->name ?? 's/cliente',
                $this->criticalCount,
                $this->highCount,
                $this->capture->id,
            ),
        ];
    }
}
