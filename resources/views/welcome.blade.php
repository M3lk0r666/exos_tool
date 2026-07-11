<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ App\Models\Setting::get('branding.company_name', config('app.name')) }} — Análisis de tech-support EXOS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">

    @php($companyName = App\Models\Setting::get('branding.company_name', 'EXOS-Tool'))
    @php($companyLogo = App\Models\Setting::get('branding.company_logo'))

    {{-- Navbar --}}
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if ($companyLogo)
                    <img src="{{ Storage::url($companyLogo) }}" alt="{{ $companyName }}" class="h-9">
                @else
                    <span class="text-xl font-bold">{{ $companyName }}</span>
                @endif
                <span class="hidden sm:inline text-sm text-gray-400">· Análisis EXOS</span>
            </div>
            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ url('/admin') }}"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                        Ir al panel
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white hover:text-blue-600">
                        Iniciar sesión
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                            Registrarse
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <header class="max-w-6xl mx-auto px-4 py-16 lg:py-24 text-center">
        <span class="inline-block mb-4 text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/40 px-3 py-1 rounded-full">
            Extreme Networks · EXOS
        </span>
        <h1 class="text-4xl lg:text-5xl font-bold leading-tight mb-4">
            Análisis automatizado de<br>
            <span class="text-blue-700 dark:text-blue-400">show tech-support all</span>
        </h1>
        <p class="max-w-2xl mx-auto text-lg text-gray-600 dark:text-gray-300 mb-8">
            Convierte el archivo de diagnóstico de tus switches Extreme en un reporte técnico
            profesional en minutos: hallazgos con evidencia, severidades, recomendaciones y
            seguimiento histórico por cliente y equipo.
        </p>
        <div class="flex justify-center gap-3">
            @auth
                <a href="{{ url('/admin') }}" class="px-6 py-3 text-base font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                    Abrir la herramienta
                </a>
            @else
                <a href="{{ route('login') }}" class="px-6 py-3 text-base font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                    Iniciar sesión
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="px-6 py-3 text-base font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        Crear cuenta
                    </a>
                @endif
            @endauth
        </div>
    </header>

    {{-- Cómo funciona --}}
    <section class="max-w-6xl mx-auto px-4 pb-16">
        <h2 class="text-2xl font-bold text-center mb-8">¿Cómo funciona?</h2>
        <div class="grid gap-4 md:grid-cols-3">
            @foreach ([
                ['1', 'Sube el archivo', 'Carga el tech-support (.txt/.log) del switch, asociado a un cliente. Soporta equipos individuales y stacks, EXOS 12.x a 22.x.'],
                ['2', 'Análisis automático', 'El motor extrae identificación, seriales, ambiente, puertos y logs; aplica 21 reglas parametrizables y correlaciona eventos para diagnosticar causa raíz.'],
                ['3', 'Reporte profesional', 'Revisa y edita los hallazgos, agrega conclusiones y emite un PDF con tu marca, listo para entregar al cliente.'],
            ] as [$step, $title, $text])
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-700 text-white font-bold mb-3">{{ $step }}</div>
                    <h3 class="font-semibold mb-2">{{ $title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Características --}}
    <section class="bg-white dark:bg-gray-800 border-y border-gray-200 dark:border-gray-700">
        <div class="max-w-6xl mx-auto px-4 py-16">
            <h2 class="text-2xl font-bold text-center mb-2">¿Qué hace la herramienta?</h2>
            <p class="text-center text-gray-500 dark:text-gray-400 mb-10 max-w-2xl mx-auto">
                Cada hallazgo incluye descripción técnica, impacto, recomendación conservadora y la
                evidencia textual con su ubicación exacta en el archivo — todo verificable.
            </p>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Detección de problemas físicos', 'Errores CRC, fragmentos, flapping, puertos degradados a 10 Mbps y congestión activa, con correlación de indicadores para señalar cableado dañado.'],
                    ['Salud del hardware', 'Temperaturas contra límites de fábrica, ventiladores, fuentes de poder, edad del equipo (odómetro) y antigüedad del firmware.'],
                    ['Estabilidad del sistema', 'Reinicios inesperados, core dumps, errores en logs y patrones que sugieren fallas eléctricas del sitio.'],
                    ['Histórico y tendencias', 'Comparativo entre capturas con manejo de reinicio de contadores, gráficos de temperatura, memoria, CPU y errores a lo largo del tiempo.'],
                    ['Reportes con tu marca', 'Semáforo por área, editor de texto enriquecido, evidencias adjuntas, versionado borrador/emitido y PDF con encabezado y pie personalizados.'],
                    ['Multi-cliente y API', 'Organización por cliente y equipo con roles de acceso, dashboards, exportación Excel/JSON, notificaciones de hallazgos críticos y API REST documentada.'],
                ] as [$title, $text])
                    <div class="flex gap-3">
                        <svg class="w-6 h-6 shrink-0 text-blue-700 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold mb-1">{{ $title }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Objetivos --}}
    <section class="max-w-6xl mx-auto px-4 py-16">
        <h2 class="text-2xl font-bold text-center mb-8">Objetivos</h2>
        <div class="grid gap-4 md:grid-cols-3 text-center">
            <div class="p-6">
                <div class="text-3xl font-bold text-blue-700 dark:text-blue-400 mb-2">Menos tiempo</div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Reducir de horas a minutos el análisis manual de archivos tech-support,
                    estandarizando el diagnóstico entre ingenieros.
                </p>
            </div>
            <div class="p-6">
                <div class="text-3xl font-bold text-blue-700 dark:text-blue-400 mb-2">Anticipar fallas</div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Detectar degradación (contadores que crecen, memoria que baja, temperatura que sube)
                    antes de que cause afectaciones en la red del cliente.
                </p>
            </div>
            <div class="p-6">
                <div class="text-3xl font-bold text-blue-700 dark:text-blue-400 mb-2">Informes confiables</div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Entregar reportes veraces y trazables: cada hallazgo con evidencia del propio equipo
                    y umbrales documentados (IEEE 802.3, catálogo EXOS, KB del fabricante).
                </p>
            </div>
        </div>
    </section>

    {{-- CTA final --}}
    <section class="max-w-6xl mx-auto px-4 pb-16 text-center">
        <div class="bg-blue-700 rounded-2xl p-10 text-white">
            <h2 class="text-2xl font-bold mb-2">Comienza a analizar tus equipos</h2>
            <p class="text-blue-100 mb-6">Inicia sesión o crea tu cuenta para subir tu primer tech-support.</p>
            @auth
                <a href="{{ url('/admin') }}" class="inline-block px-6 py-3 font-medium text-blue-700 bg-white rounded-lg hover:bg-blue-50">
                    Ir al panel
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-block px-6 py-3 font-medium text-blue-700 bg-white rounded-lg hover:bg-blue-50">
                    Iniciar sesión
                </a>
            @endauth
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-gray-200 dark:border-gray-700 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ $companyName }} · {{ App\Models\Setting::get('branding.footer_text', '') }}
    </footer>
</body>
</html>
