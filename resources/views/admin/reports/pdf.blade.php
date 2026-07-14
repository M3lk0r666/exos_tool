<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reporte técnico — {{ $device?->displayName() ?? 'Equipo' }}</title>
    <style>
        @page {
            margin: 130px 50px 85px 50px;
        }

        /* Logo del encabezado: ajusta el tamaño cambiando el height de .page-header img
           (si lo haces mucho más grande, sube también el margin superior de @page). */
        .page-header {
            position: fixed;
            top: -100px;
            left: 0;
            right: 0;
        }

        /*.page-header img { height: 75px; }*/
        .page-header img {
            height: 90px;
        }

        .page-footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }

        * {
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            font-size: 11px;
            color: #1f2937;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 6px;
        }

        h2 {
            font-size: 15px;
            color: #1d4ed8;
            border-bottom: 2px solid #1d4ed8;
            padding-bottom: 4px;
            margin: 22px 0 10px;
        }

        h3 {
            font-size: 12px;
            margin: 12px 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eff6ff;
            font-weight: bold;
        }

        .cover {
            text-align: center;
            margin-top: 140px;
        }

        .cover .logos {
            margin-bottom: 10px;
        }

        .cover img {
            max-height: 150px;
            margin: 0 25px;
        }

        .cover .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .cover table {
            width: 70%;
            margin: 30px auto 0;
        }

        .page-break {
            page-break-after: always;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 8px;
            color: #fff;
            font-size: 9px;
            font-weight: bold;
        }

        .muted {
            color: #6b7280;
        }

        .evidence {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 8.5px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .bar-track {
            background: #e5e7eb;
            width: 100%;
            height: 12px;
            border-radius: 3px;
        }

        .bar {
            height: 12px;
            border-radius: 3px;
        }

        .finding {
            margin-bottom: 14px;
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
        }

        .finding h3 {
            margin: 0 0 6px;
        }

        .area-cell {
            font-weight: bold;
            color: #fff;
            text-align: center;
            border-radius: 4px;
        }

        .foot-note {
            font-size: 9px;
            color: #6b7280;
        }

        .attachment-img {
            max-width: 300px;
            max-height: 200px;
            margin: 4px 8px 4px 0;
            border: 1px solid #d1d5db;
        }

        ol.toc li {
            margin-bottom: 4px;
        }
    </style>
</head>

<body>

    @php
        $severityMeta = [
            'critical' => ['label' => 'Crítico', 'color' => '#dc2626'],
            'high' => ['label' => 'Alto', 'color' => '#ea580c'],
            'medium' => ['label' => 'Medio', 'color' => '#ca8a04'],
            'low' => ['label' => 'Bajo', 'color' => '#2563eb'],
            'informational' => ['label' => 'Informativo', 'color' => '#6b7280'],
        ];
        $logo = function (?string $path) {
            if (!$path) {
                return null;
            }
            $full =
                str_starts_with($path, 'client-logos') || str_starts_with($path, 'branding')
                    ? storage_path('app/public/' . $path)
                    : $path;
            return is_file($full) ? $full : null;
        };
    @endphp

    {{-- ============ ENCABEZADO Y PIE EN TODAS LAS PÁGINAS ============ --}}
    @if ($logo($companyLogo))
        <div class="page-header">
            <img src="{{ $logo($companyLogo) }}" alt="{{ $companyName }}">
        </div>
    @endif
    <div class="page-footer">{{ $footerText }}</div>

    {{-- ============ PORTADA ============ --}}
    <div class="cover">
        <div class="logos">
            @if ($logo($client?->logo_path))
                <img src="{{ $logo($client->logo_path) }}" alt="cliente">
            @endif
        </div>

        <h1>Reporte técnico de análisis</h1>
        <div class="subtitle">show tech-support all &middot; Extreme Networks EXOS</div>

        <table>
            <tr>
                <th style="width:35%">Cliente</th>
                <td>{{ $client?->name ?? '—' }}</td>
            </tr>
            <tr>
                <th>Equipo</th>
                <td>{{ $device?->displayName() ?? '—' }} ({{ $summary['system_type'] ?? '' }})</td>
            </tr>
            <tr>
                <th>Fecha de la captura</th>
                <td>{{ $capture->captured_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
            <tr>
                <th>Versión del reporte</th>
                <td>v{{ $report->version }} — {{ $report->status->label() }}</td>
            </tr>
            <tr>
                <th>Emitido por</th>
                <td>{{ $report->issuer?->name ?? 'Borrador (sin emitir)' }}</td>
            </tr>
            <tr>
                <th>Fecha de emisión</th>
                <td>{{ $report->issued_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
            <tr>
                <th>Elaborado con</th>
                <td>{{ $companyName }}</td>
            </tr>
        </table>
    </div>
    <div class="page-break"></div>

    {{-- ============ CONTENIDO ============ --}}
    <h2>Contenido</h2>
    <ol class="toc">
        <li>Información del equipo</li>
        <li>Resumen ejecutivo</li>
        <li>Estado por área y resumen de severidades</li>
        <li>Hallazgos y evidencias</li>
        <li>Conclusiones</li>
        <li>Recomendaciones</li>
        <li>Nota metodológica</li>
        <li>Anexo: advertencias del análisis</li>
    </ol>

    {{-- ============ 1. INFO DEL EQUIPO ============ --}}
    <h2>1. Información del equipo</h2>
    <table>
        <tr>
            <th style="width:30%">Nombre (SysName)</th>
            <td>{{ $summary['sysname'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>Modelo</th>
            <td>{{ $summary['system_type'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>MAC del sistema</th>
            <td>{{ $summary['system_mac'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>Número(s) de serie</th>
            <td>
                @php($serials = $summary['serial_numbers'] ?? [])
                @if ($serials === [])
                    —
                @elseif (isset($serials['Switch']))
                    {{ $serials['Switch'] }}
                @else
                    @foreach ($serials as $unit => $serial)
                        {{ $unit }}: <b>{{ $serial }}</b>
                        @if (!$loop->last)
                            <br>
                        @endif
                    @endforeach
                @endif
            </td>
        </tr>
        <tr>
            <th>Versión EXOS</th>
            <td>{{ $summary['exos_version'] ?? '—' }} @if (!empty($summary['firmware_build_date']))
                    <span class="muted">(compilada: {{ $summary['firmware_build_date'] }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>BootROM</th>
            <td>{{ $summary['bootrom'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>Licencia</th>
            <td>{{ $summary['license'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>Uptime</th>
            <td>{{ $summary['uptime_text'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>Boot count</th>
            <td>{{ $summary['boot_count'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>Stack</th>
            <td>{{ !empty($summary['is_stack']) ? 'Sí' : 'No' }}</td>
        </tr>
        <tr>
            <th>Archivo analizado</th>
            <td>{{ $capture->original_filename }} <span class="muted">(SHA-256:
                    {{ substr($capture->file_hash, 0, 16) }}…)</span></td>
        </tr>
    </table>

    {{-- ============ 2. RESUMEN EJECUTIVO ============ --}}
    <h2>2. Resumen ejecutivo</h2>
    @if ($report->executive_summary)
        {!! $report->executive_summary !!}
    @else
        <p class="muted">Sin resumen ejecutivo capturado.</p>
    @endif

    {{-- ============ 3. SEMÁFORO Y SEVERIDADES ============ --}}
    <h2>3. Estado por área y resumen de severidades</h2>

    <table>
        @foreach (array_chunk($areas, 5, true) as $chunk)
            <tr>
                @foreach ($chunk as $area)
                    <td class="area-cell"
                        style="width:20%; background: {{ App\Services\Reporting\AreaStatusService::pdfColor($area['status']) }};">
                        {{ $area['label'] }}<br>
                        <span style="font-weight:normal; font-size:9px;">
                            {{ $area['count'] === 0 ? 'Sin hallazgos' : $area['count'] . ' hallazgo(s)' }}
                        </span>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>

    @php($pdfTemps = $summary['temperatures'] ?? [])
@php($pdfFanDetail = $summary['fans']['detail'] ?? [])

@if ($pdfTemps !== [])
    <h3>Temperaturas por unidad</h3>
    <table>
        <thead>
            <tr><th>Unidad</th><th>Actual</th><th>Estado</th><th>Máx. de fábrica</th><th>Margen</th></tr>
        </thead>
        <tbody>
            @foreach ($pdfTemps as $t)
                <tr>
                    <td>{{ $t['unit'] }}</td>
                    <td>{{ $t['temp'] }} °C</td>
                    <td style="{{ $t['status'] === 'Normal' ? 'color:#16a34a;' : 'color:#dc2626; font-weight:bold;' }}">{{ $t['status'] }}</td>
                    <td>{{ $t['max'] }} °C</td>
                    <td>{{ round($t['max'] - $t['temp'], 1) }} °C</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if ($pdfFanDetail !== [])
    <h3>Ventiladores (show fans detail)</h3>
    <table>
        <thead>
            <tr><th>Fan tray</th><th>Estado</th><th>Ventiladores (RPM)</th></tr>
        </thead>
        <tbody>
            @foreach ($pdfFanDetail as $tray => $detail)
                <tr>
                    <td>{{ $tray }}</td>
                    <td style="{{ in_array($detail['state'], ['Operational', 'Empty', null], true) ? 'color:#16a34a;' : 'color:#dc2626; font-weight:bold;' }}">
                        {{ $detail['state'] ?? '—' }}
                    </td>
                    <td>
                        @forelse ($detail['fans'] as $fan)
                            {{ $fan['fan'] }}: {{ $fan['rpm'] !== null ? number_format($fan['rpm']).' RPM' : $fan['state'] }}@if (! $loop->last) · @endif
                        @empty
                            —
                        @endforelse
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h3>Hallazgos por severidad</h3>
    @php($maxCount = max(1, $severityCounts->max() ?? 1))
    <table style="border:none;">
        @foreach ($severityMeta as $key => $meta)
            @php($count = $severityCounts[$key] ?? 0)
            <tr>
                <td style="border:none; width:90px;"><span class="badge"
                        style="background: {{ $meta['color'] }};">{{ $meta['label'] }}</span></td>
                <td style="border:none;">
                    <div class="bar-track">
                        <div class="bar"
                            style="background: {{ $meta['color'] }}; width: {{ max(2, round(($count / $maxCount) * 100)) }}%;">
                        </div>
                    </div>
                </td>
                <td style="border:none; width:30px; text-align:right;"><b>{{ $count }}</b></td>
            </tr>
        @endforeach
    </table>

    {{-- ============ 4. HALLAZGOS ============ --}}
    <h2>4. Hallazgos y evidencias</h2>

    @forelse ($findings as $i => $finding)
        <div class="finding">
            <h3>
                {{ $i + 1 }}. {{ $finding->title }}
                <span class="badge" style="background: {{ $severityMeta[$finding->level->value]['color'] }};">
                    {{ $finding->level->label() }}
                </span>
                @if ($finding->is_manual)
                    <span class="badge" style="background:#7c3aed;">Manual</span>
                @endif
            </h3>
            <p class="muted" style="margin:0 0 6px;">
                Área: {{ App\Services\Reporting\AreaStatusService::label($finding->area) }}
                @if ($finding->entity)
                    · Entidad: {{ $finding->entity }}
                @endif
                · Regla: {{ $finding->rule_code }}
                @if ($finding->status !== App\Enums\FindingStatus::Open)
                    · Estado: {{ $finding->status->label() }}
                @endif
            </p>

            <p>{{ $finding->description }}</p>

            @if ($finding->impact)
                <p><b>Impacto:</b> {{ $finding->impact }}</p>
            @endif
            @if ($finding->recommendation)
                <p><b>Recomendación:</b> {{ $finding->recommendation }}</p>
            @endif

            @if ($finding->evidence)
                <div class="evidence">{{ $finding->evidence }}</div>
                @if ($finding->file_location)
                    <div class="foot-note">Evidencia: {{ $finding->file_location }} del archivo analizado.</div>
                @endif
            @endif

            @foreach ($finding->attachments as $attachment)
                @if ($attachment->type === 'image' && is_file(storage_path('app/public/' . $attachment->path)))
                    <img class="attachment-img" src="{{ storage_path('app/public/' . $attachment->path) }}"
                        alt="">
                    @if ($attachment->caption)
                        <div class="foot-note">{{ $attachment->caption }}</div>
                    @endif
                @elseif ($attachment->original_filename)
                    <div class="foot-note">Adjunto: {{ $attachment->original_filename }}
                        {{ $attachment->caption ? '— ' . $attachment->caption : '' }}</div>
                @endif
            @endforeach
        </div>
    @empty
        <p class="muted">No se registraron hallazgos para esta captura.</p>
    @endforelse

    {{-- ============ 5 y 6. CONCLUSIONES Y RECOMENDACIONES ============ --}}
    <h2>5. Conclusiones</h2>
    @if ($report->conclusions)
        {!! $report->conclusions !!}
    @else
        <p class="muted">Sin conclusiones capturadas.</p>
    @endif

    <h2>6. Recomendaciones</h2>
    @if ($report->recommendations)
        {!! $report->recommendations !!}
    @else
        <p class="muted">Sin recomendaciones capturadas.</p>
    @endif

    {{-- ============ 7. NOTA METODOLÓGICA ============ --}}
    <h2>7. Nota metodológica</h2>
    <p class="foot-note">
        Este reporte se generó a partir del archivo de diagnóstico <i>show tech-support all</i> proporcionado por el
        propio equipo, cuya fecha de referencia es la registrada por el switch al momento de la captura. Los estados de
        hardware (temperatura, ventiladores, fuentes de poder, nodos de stack) corresponden a lo declarado por el equipo
        contra sus límites de fábrica. Los umbrales de análisis cuantitativos (errores de puerto, CPU, memoria,
        antigüedad de firmware) son parametrizables y provienen de estándares (IEEE 802.3), del catálogo de mensajes de
        EXOS y de práctica de ingeniería de redes; su origen está documentado en la configuración de reglas del sistema.
        Los contadores de puertos son acumulados desde el último arranque del equipo. Las severidades y el contenido de
        este reporte fueron revisados por el ingeniero responsable antes de su emisión. Las recomendaciones deben
        ejecutarse en ventana de mantenimiento.
    </p>

    {{-- ============ 8. ANEXO ============ --}}
    <h2>8. Anexo: advertencias del análisis</h2>
    @if (!empty($capture->parser_warnings))
        <ul class="foot-note">
            @foreach ($capture->parser_warnings as $warning)
                <li>{{ $warning }}</li>
            @endforeach
        </ul>
    @else
        <p class="foot-note">El análisis no generó advertencias: todas las secciones esperadas fueron interpretadas.</p>
    @endif

    {{-- Numeración de páginas: esquina inferior derecha, en su propia línea
     (debajo del texto del pie para no encimarse) --}}
    <script type="text/php">
    if (isset($pdf)) {
        $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
        $font = $fontMetrics->getFont("DejaVu Sans", "normal");
        $width = $fontMetrics->getTextWidth($text, $font, 8);
        $pdf->page_text($pdf->get_width() - $width - 40, $pdf->get_height() - 22, $text, $font, 8, [0.42, 0.45, 0.5]);
    }
</script>

</body>

</html>
