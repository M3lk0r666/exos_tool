{{-- Contenido del dictamen (HTML semántico; el estilo lo pone cada envoltorio) --}}

<h2>1. Objeto</h2>
<p>
    Este documento describe la metodología con la que se generan los reportes de análisis de archivos
    <i>show tech-support all</i> de switches Extreme Networks (EXOS): qué información se extrae, qué
    parámetros y umbrales se aplican, contra qué referencias se contrastan y qué controles de calidad
    garantizan la validez de los hallazgos reportados.
</p>

<h2>2. Fuente de los datos</h2>
<p>
    Toda la información proviene <b>exclusivamente del propio equipo</b>: el archivo tech-support es
    generado por el switch y contiene el estado operativo reportado por su sistema operativo. La
    herramienta es de <b>solo lectura</b> (nunca se conecta ni modifica el equipo). Cada archivo se
    conserva íntegro, identificado por su hash SHA-256, y la fecha de referencia del análisis es el
    <i>Current Time</i> registrado por el switch al momento de la captura — no la fecha de carga.
    Cada hallazgo incluye el fragmento textual del archivo (evidencia) y su número de línea, de modo
    que cualquier afirmación del reporte es verificable contra el archivo original.
</p>

<h2>3. Qué se analiza</h2>
<p>
    El análisis cubre: identificación del equipo (modelo, seriales por unidad, firmware y su fecha de
    compilación); ambiente (temperaturas contra límites de fábrica, ventiladores); alimentación
    (estado de cada fuente); CPU (utilización promedio a 1 hora) y memoria por slot; puertos (errores
    CRC, fragmentos, oversize, jabber, transiciones de link, negociación de velocidad, congestión);
    transceivers ópticos; registro de eventos y NVRAM (reinicios inesperados, errores, core dumps,
    intentos de acceso fallidos); stacking; y configuración de gestión (SSH, SNMP, NTP).
</p>

<h2>4. Dos tipos de hallazgo: hechos del equipo y umbrales de ingeniería</h2>
<p>
    <b>a) Hechos declarados por el equipo.</b> Estados que el switch evalúa contra sus propios límites
    de fábrica y reporta de forma explícita: temperatura fuera de rango, ventilador o fuente en falla,
    nodo de stack caído, core dumps, o eventos con código del catálogo oficial de mensajes de EXOS
    (p. ej. <i>EPM.UnexpctRebootDtect</i> para reinicios inesperados, <i>HAL.Port.OpticCfgCflct</i>
    para conflictos de óptica). En estos casos la herramienta no interpreta: presenta lo que el
    fabricante ya diagnosticó.
</p>
<p>
    <b>b) Umbrales cuantitativos.</b> Métricas numéricas contrastadas contra umbrales documentados y
    configurables (tabla de la sección 6). Su respaldo conceptual: el estándar IEEE 802.3 exige tasas
    de error de bit del orden de 10⁻¹² en enlaces Ethernet sanos, por lo que contadores de CRC o
    fragmentos que crecen de forma sostenida indican objetivamente un problema físico; dónde se traza
    la línea entre severidad Media y Alta es criterio de ingeniería, declarado y ajustable.
</p>

<h2>5. Escala de severidades</h2>
<p>
    <b>Crítico</b>: falla activa o riesgo inminente (declarada por el equipo o riesgo inmediato).
    <b>Alto</b>: daño confirmado que degrada el servicio.
    <b>Medio</b>: riesgo latente o desviación preventiva.
    <b>Bajo</b>: desviación menor.
    <b>Informativo</b>: contexto sin acción requerida.
    Las severidades propuestas por el motor son revisadas por el ingeniero responsable antes de la
    emisión del reporte, quien puede ajustarlas, complementarlas o marcarlas como falso positivo.
</p>

<h2>6. Parámetros y umbrales vigentes</h2>
<p>
    Los umbrales se almacenan en base de datos y esta tabla refleja su valor vigente al
    {{ $generatedAt->format('d/m/Y H:i') }}. La columna "Referencia" documenta el origen de cada regla.
</p>
<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Regla</th>
            <th>Umbral advertencia</th>
            <th>Umbral crítico</th>
            <th>Referencia del umbral</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rules as $rule)
            <tr>
                <td><b>{{ $rule->code }}</b>{{ $rule->enabled ? '' : ' (deshabilitada)' }}</td>
                <td>{{ $rule->description }}</td>
                <td>{{ $rule->threshold_warning !== null ? number_format((float) $rule->threshold_warning) : '—' }}
                    ({{ App\Enums\FindingSeverity::from($rule->level_warning)->label() }})</td>
                <td>{{ $rule->threshold_critical !== null ? number_format((float) $rule->threshold_critical) : '—' }}
                    {{ $rule->level_critical ? '('.App\Enums\FindingSeverity::from($rule->level_critical)->label().')' : '' }}</td>
                <td>{{ $rule->params['reference'] ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>7. Correlación de eventos</h2>
<p>
    Además de las reglas individuales, el motor combina indicadores independientes para robustecer el
    diagnóstico, siguiendo patrones que el propio soporte del fabricante (GTAC) aplica:
    CRC + fragmentos + jabber en el mismo puerto ⇒ capa física dañada; flapping + negociación a
    10 Mbps en el mismo puerto ⇒ par del cable dañado; reinicios inesperados sin core dumps ⇒ probable
    causa eléctrica externa; firmware antiguo + reinicios ⇒ actualización previa a escalamiento.
    Un diagnóstico correlacionado nunca sustituye a los hallazgos individuales que lo sustentan.
</p>

<h2>8. El histórico como contraste principal</h2>
<p>
    Los contadores de puertos son acumulados desde el último arranque: un valor absoluto solo cobra
    significado frente al tiempo. Por ello el sistema conserva todas las capturas por equipo y
    contrasta cada una contra las anteriores: deltas por puerto entre capturas, tendencias de
    temperatura, memoria y CPU, y ciclo de vida de hallazgos (un hallazgo recurrente se vincula a su
    primera aparición; uno resuelto que reaparece se reabre). Si el equipo se reinició entre capturas
    (uptime menor), los deltas de contadores se suspenden explícitamente para no reportar
    comparaciones inválidas. Un puerto con 5,000 CRC acumulados en 3 años es una condición estable;
    el mismo contador acumulado en una semana es degradación activa — esta distinción solo es posible
    con el seguimiento histórico.
</p>

<h2>9. Controles de calidad</h2>
<p>
    El intérprete del archivo se valida con pruebas automatizadas contra archivos tech-support reales
    (EXOS 12.x y 22.x, equipos individuales y stacks) con valores esperados verificados manualmente;
    cualquier divergencia impide liberar cambios. El procesamiento es tolerante a fallos: si una
    sección falta o cambia de formato, se registra como advertencia visible en el reporte (Anexo) en
    lugar de inventar datos. Los reportes son versionados (borrador → emitido, inmutable tras
    emisión), todas las acciones quedan auditadas, y las recomendaciones se redactan de forma
    conservadora: verificación en ventana de mantenimiento o escalamiento al soporte del fabricante.
</p>

<h2>10. Limitaciones</h2>
<p>
    El análisis refleja el estado del equipo <b>al momento de la captura</b>; eventos posteriores no
    están cubiertos. Los contadores acumulados no registran el instante exacto de cada error (solo el
    log). La verificación física (cableado, ópticas, ambiente del sitio) requiere inspección en campo;
    por ello los hallazgos de capa física se expresan como diagnóstico probable con recomendación de
    verificación.
</p>

<h2>11. Referencias y bibliografía</h2>
<table>
    <thead>
        <tr><th>Referencia</th><th>Uso en el análisis</th><th>Enlace</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>IEEE 802.3 — Standard for Ethernet</td>
            <td>Tasas de error esperadas en enlaces sanos (BER ~10⁻¹²); fundamento de las reglas de CRC y fragmentos.</td>
            <td>https://standards.ieee.org/ieee/802.3/</td>
        </tr>
        <tr>
            <td>Portal de soporte de Extreme Networks</td>
            <td>Acceso a documentación oficial, casos y herramientas del fabricante.</td>
            <td>https://www.extremenetworks.com/support/</td>
        </tr>
        <tr>
            <td>Documentación y Release Notes de EXOS / Switch Engine</td>
            <td>Guías de usuario por versión, defectos corregidos y versiones recomendadas (reglas FW-AGE y recomendaciones de actualización).</td>
            <td>https://supportdocs.extremenetworks.com/</td>
        </tr>
        <tr>
            <td>Base de conocimiento GTAC (Extreme Portal)</td>
            <td>Artículos de diagnóstico del fabricante; origen de patrones como OpticCfgCflct y negociación a 10 Mbps.</td>
            <td>https://extremeportal.force.com/</td>
        </tr>
        <tr>
            <td>Avisos End of Sale / End of Service Life de Extreme</td>
            <td>Estado de soporte del hardware y software (contexto de HW-AGE y FW-AGE).</td>
            <td>https://www.extremenetworks.com/support/end-of-sale-and-end-of-service-life-products/</td>
        </tr>
        <tr>
            <td>SFF-8472 (SNIA) — Diagnóstico digital de transceivers</td>
            <td>Umbrales de alarma/advertencia grabados de fábrica en las ópticas (potencias dBm y temperatura).</td>
            <td>https://www.snia.org/sff/specifications</td>
        </tr>
        <tr>
            <td>Datasheets y guías de instalación por modelo (Extreme)</td>
            <td>Rangos operativos de temperatura y alimentación; los límites que el propio switch reporta en <i>show temperature</i> provienen de estas especificaciones.</td>
            <td>https://www.extremenetworks.com/resources/</td>
        </tr>
    </tbody>
</table>
<p>
    <i>Nota: los enlaces corresponden a los portales oficiales vigentes a la fecha de generación de
    este documento; el fabricante puede reorganizar su sitio. El catálogo de mensajes de EXOS está
    disponible dentro de la documentación oficial de cada versión.</i>
</p>
