# EXOS-Tool

Plataforma web para el análisis automatizado de archivos de diagnóstico
`show tech-support all` de switches **Extreme Networks (EXOS)**, organizada por
cliente y equipo, con seguimiento histórico, comparativos entre capturas,
reportes técnicos editables y PDF profesional.

## Características

- **Parser tolerante a fallos** para EXOS 12.x / 16.x / 22.x (3 estilos de delimitador,
  alias de comandos por versión, filtros `| exclude`).
- **Motor de análisis modular**: 13 analyzers con las 21 reglas del Anexo B,
  umbrales parametrizables en BD y motor de correlación de eventos.
- **Reportes**: semáforo por área, edición de hallazgos, WYSIWYG local (Quill),
  evidencias adjuntas, versionado borrador/emitido y PDF con marca de la empresa.
- **Histórico**: comparativo entre capturas (con manejo de reinicio de contadores),
  gráficos de tendencia (ApexCharts) y ciclo de vida de hallazgos recurrentes.
- **Dashboards** global y por cliente, exportación Excel/JSON, notificaciones
  de hallazgos Critical/High (UI + correo).
- **API REST v1** (Sanctum) documentada con OpenAPI (`public/openapi.yaml`).
- Roles: Administrador / Ingeniero / Lectura (spatie/laravel-permission) y auditoría completa.

## Stack

PHP 8.2+ · Laravel 12 · MySQL 8 · Jetstream (Livewire) · TailwindCSS 4 + Flowbite 4 ·
AlpineJS · Quill 2 · ApexCharts · DomPDF · maatwebsite/excel.

## Instalación (desarrollo, XAMPP)

```bash
composer install
composer require spatie/laravel-permission barryvdh/laravel-dompdf maatwebsite/excel
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
cp .env.example .env            # configura DB_* (MySQL) y APP_URL
php artisan key:generate
php artisan storage:link
php artisan migrate --seed      # roles, usuarios demo, reglas Anexo B, settings
npm install && npm run build
php artisan queue:work          # en una terminal aparte (procesa los análisis)
```

Usuarios demo: `admin@exostool.local`, `ingeniero@exostool.local`,
`lectura@exostool.local` (contraseña `password`). Cámbialos en producción.

## Tests

```bash
php artisan test
```

Incluye tests golden-file del parser y del motor de análisis contra archivos
tech-support reales (`tests/Fixtures/`), flujo E2E y prueba de rendimiento con
archivo de +5 MB. El script Python de referencia vive en `scripts/`.

## Estructura relevante

```
app/Services/Parser/       SectionSplitter, ExosParser, ParsedTechSupport (Anexo A)
app/Services/Analysis/     AnalysisEngine, analyzers, CorrelationEngine, ciclo de vida
app/Services/Reporting/    ReportService (versionado + PDF), AreaStatusService (semáforo)
app/Services/History/      ComparisonService, TrendService (Fase 5)
app/Jobs/ProcessCaptureJob Pipeline: parser → equipo → métricas → análisis → notificación
database/seeders/          Reglas del Anexo B con referencias de umbral
prompt_maestro_exos_analyzer.md   Especificación completa del proyecto
docs/DEPLOY_UBUNTU.md      Guía de despliegue en producción
```

## Despliegue en producción

Ver **[docs/DEPLOY_UBUNTU.md](docs/DEPLOY_UBUNTU.md)** (Ubuntu + Apache + MySQL +
systemd para el queue worker). Archivos de ejemplo en `deploy/`.
