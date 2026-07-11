<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Dictamen metodológico — Análisis de tech-support EXOS</title>
    <style>
        @page { margin: 130px 50px 85px 50px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10.5px; color: #1f2937; }
        h1 { font-size: 19px; margin: 0 0 4px; }
        h2 { font-size: 13px; color: #1d4ed8; border-bottom: 2px solid #1d4ed8; padding-bottom: 3px; margin: 18px 0 8px; }
        p { margin: 0 0 8px; line-height: 1.45; text-align: justify; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0 10px; font-size: 8.5px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #eff6ff; font-weight: bold; }
        .subtitle { color: #6b7280; font-size: 11px; margin-bottom: 14px; }
        .page-header { position: fixed; top: -100px; left: 0; right: 0; }
        .page-header img { height: 75px; }
        .page-footer { position: fixed; bottom: -50px; left: 0; right: 0; text-align: center;
            font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    @php($logoFile = $companyLogo && is_file(storage_path('app/public/'.$companyLogo)) ? storage_path('app/public/'.$companyLogo) : null)

    @if ($logoFile)
        <div class="page-header"><img src="{{ $logoFile }}" alt="{{ $companyName }}"></div>
    @endif
    <div class="page-footer">{{ $footerText }}</div>

    <h1>Dictamen metodológico</h1>
    <div class="subtitle">
        Análisis automatizado de archivos <i>show tech-support all</i> — Extreme Networks EXOS<br>
        {{ $companyName }} · Generado el {{ $generatedAt->format('d/m/Y H:i') }}
    </div>

    @include('admin.methodology._content')

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
