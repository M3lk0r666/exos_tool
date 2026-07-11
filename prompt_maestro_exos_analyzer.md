# PROMPT MAESTRO: Plataforma web de análisis de tech-support Extreme Networks EXOS

> **Cómo usar este prompt:** cópialo completo como prompt inicial del proyecto. La
> especificación es extensa: NO pidas el proyecto completo en una sola respuesta.
> Al final se define un plan por fases — pide una fase a la vez, valida y continúa.
> Los Anexos A y B contienen conocimiento de dominio ya validado contra archivos
> reales: son la fuente de verdad del parser y de las reglas de análisis.

---

## 1. Rol

Actúa como Arquitecto de Software Senior, especialista en PHP, Laravel, MySQL,
TailwindCSS, Flowbite, Apache y análisis de información técnica de redes.

## 2. Objetivo del sistema

Desarrollar una aplicación web profesional que automatice el análisis de archivos de
diagnóstico `show tech-support all` de switches Extreme Networks (EXOS), organizados
**por cliente y por equipo**, con seguimiento histórico. Estos archivos contienen una
radiografía completa del estado operativo del switch y son los que solicita el soporte
de Extreme para diagnosticar fallas.

El sistema debe:

1. Permitir cargar archivos por cliente y analizarlos automáticamente.
2. Generar un reporte técnico con hallazgos: problema detectado, descripción técnica,
   impacto, severidad, recomendación, evidencia (fragmento del archivo) y ubicación
   (línea/sección) dentro del archivo.
3. Permitir al ingeniero **revisar, editar y complementar** el reporte antes de emitirlo.
4. Generar un informe profesional en PDF.
5. Mantener histórico por equipo para **comparativos entre capturas y gráficos de
   tendencia** (el diferenciador clave: detectar degradación antes de que cause
   afectaciones en la red).

## 3. Stack tecnológico obligatorio

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3+, Laravel 12, arquitectura MVC + Service Layer |
| Patrones | Repository, Service, DTO, Dependency Injection, Events/Listeners, Policies, Form Requests, Jobs/Queues |
| Base de datos | MySQL 8, Eloquent ORM, migraciones normalizadas |
| Frontend | Blade + TailwindCSS + Flowbite + AlpineJS, dark mode, responsive, Heroicons |
| Gráficos | ApexCharts |
| PDF | DomPDF o Snappy (wkhtmltopdf) |
| Editor | WYSIWYG (negritas, tablas, código, imágenes, hipervínculos, listas) para edición de hallazgos y conclusiones |
| Servidor | Apache |
| Versionamiento | Git |
| Idiomas | Código e identificadores en inglés; UI y reportes en español |

## 4. Arquitectura y calidad

- Principios SOLID; código limpio, documentado, desacoplado, PSR-12, preparado para producción.
- Traits, Factories, Seeders, Migrations.
- Pruebas unitarias y de integración para los componentes críticos, en especial el
  parser y los analyzers (usar archivos tech-support reales anonimizados como fixtures).
- El parser debe ser **tolerante a fallos**: si una sección falta o cambia de formato,
  registra una advertencia y continúa; nunca aborta el procesamiento completo.
- Comentarios solo donde aporten valor.

## 5. Módulos funcionales

### 5.1 Autenticación y roles
- Login (Laravel Breeze/Fortify). Roles: **Administrador, Ingeniero, Lectura**.
- Policies para autorización por recurso. Auditoría de acciones (AuditLogs).

### 5.2 Clientes y equipos
- CRUD de clientes (nombre, contacto, logo para reportes, notas).
- Los equipos se crean automáticamente al procesar un archivo, identificados por
  **System MAC + SysName**; editables (alias, sitio, criticidad). Un cliente tiene N
  equipos; un equipo tiene N capturas a lo largo del tiempo.

### 5.3 Carga de archivos
- Subida .txt/.log con drag & drop y selección múltiple, asociada a un cliente.
- Validaciones: solo texto, tamaño máximo configurable, codificación UTF-8.
- Detección de duplicados por hash SHA-256. Se conserva el archivo original en storage.
- Procesamiento asíncrono con Jobs/Queues; estados: pendiente / procesando /
  completado / error (visibles en UI con progreso).
- La fecha de referencia de la captura es el `Current Time` **del propio archivo**,
  no la fecha de subida.

### 5.4 Parser estructurado (ver Anexo A — fuente de verdad)
- Divide el archivo en secciones delimitadas por líneas `->comando`.
- Extrae y normaliza: identificación (hostname, modelo, seriales, MAC, licencia),
  firmware/BootROM y su fecha de compilación, uptime/boot count, slots y stacking
  (roles master/standby/backup, topología), temperatura, ventiladores, fuentes de
  poder, PoE, CPU, memoria, puertos (estado, transiciones de link, errores CRC/FCS/
  fragmentos/jabber/oversize, congestión, transceivers ópticos), vecinos LLDP/EDP/CDP,
  VLANs, STP/MSTP/RSTP, rutas (OSPF/BGP/RIP/VRRP si existen), FDB/MAC table, ARP,
  logs y logs NVRAM (crash logs, kernel errors, reboots inesperados), NTP, SNMP,
  AAA/RADIUS/TACACS/802.1X, ACL/QoS y cualquier otra información relevante.
- Guarda métricas normalizadas en BD (tabla `metrics`) para habilitar comparativos.

### 5.5 Motor de análisis modular (núcleo del sistema)
- Cada análisis es una clase independiente que implementa `AnalyzerInterface`:
  `HardwareAnalyzer`, `CpuAnalyzer`, `MemoryAnalyzer`, `TemperatureAnalyzer`,
  `PowerAnalyzer`, `FanAnalyzer`, `PortsAnalyzer` (errores físicos, flapping, MTU),
  `OpticsAnalyzer`, `LogsAnalyzer` (reboots, kernel, crash), `StackAnalyzer`,
  `FirmwareAnalyzer`, `LicenseAnalyzer`, `StpAnalyzer`, `VlanAnalyzer`,
  `RoutingAnalyzer`, `ManagementAnalyzer` (SSH/SNMP/NTP), `SecurityAnalyzer` (authFail,
  RADIUS/TACACS), `PoeAnalyzer`, etc.
- Cada hallazgo devuelve: título, descripción técnica, categoría/área, severidad,
  impacto, recomendación, evidencia (fragmento textual del archivo) y ubicación.
- **Severidades:** Critical, High, Medium, Low, Informational — cada una con color
  propio (badges Flowbite). Las reglas y umbrales iniciales están en el Anexo B y
  deben ser **parametrizables desde base de datos** (tabla `analyzer_rules`), con
  valores por defecto sembrados por seeder.
- Inteligencia del análisis: no limitarse a regex sueltas. Combinar parsers
  estructurados + reglas configurables en BD + **motor de correlación de eventos**
  (ej.: reinicios simultáneos en todos los slots sin core dumps ⇒ sugerir causa
  eléctrica externa; CRC + fragmentos + jabber en el mismo puerto ⇒ capa física) +
  puntuación de severidad basada en múltiples indicadores.
- Arquitectura preparada para integrar a futuro modelos de IA/LLM que interpreten el
  archivo, redacten explicaciones en lenguaje natural y sugieran causa raíz.
- Extensible a otros comandos y otros fabricantes (Cisco, Juniper, Aruba, HPE, Dell,
  Huawei) mediante módulos/drivers independientes por vendor.

### 5.6 Reporte editable (antes del PDF)
- Reporte preliminar visualizable en web con semáforo por área (Estabilidad, Puertos,
  Firmware, Ambiente, Alimentación, Stacking, CPU/Memoria, Gestión).
- El ingeniero puede: editar texto, eliminar hallazgos, agregar hallazgos manuales,
  cambiar severidad, agregar comentarios, conclusiones, recomendaciones y observaciones
  (editor WYSIWYG).
- Evidencias por hallazgo: fragmentos del TXT, capturas de pantalla, fotografías,
  logs, archivos adicionales, código/configuraciones, tablas.
- **Versionado del reporte** (borradores y versión emitida).

### 5.7 Reporte PDF profesional
- Portada con logo de la empresa y del cliente, datos del cliente, fecha, ingeniero
  responsable; tabla de contenidos; información del switch; resumen ejecutivo; resumen
  de severidades con gráficos; hallazgos con evidencias; conclusiones;
  recomendaciones; anexos; pie de página y numeración.

### 5.8 Histórico y comparativos (diferenciador clave)
- Por equipo: línea de tiempo de capturas y comparación entre dos capturas
  seleccionadas — tabla lado a lado con variación resaltada (mejora/empeora): delta de
  CRC por puerto, nuevos hallazgos, hallazgos resueltos, cambio de firmware, uptime,
  temperatura, memoria libre, puertos con flapping.
- Gráficos de tendencia (ApexCharts): temperatura por slot, % memoria libre, CPU 1h,
  errores CRC totales, hallazgos por severidad — eje X = fecha de captura.
- **Manejo de reinicio de contadores:** los contadores de puertos son acumulados desde
  el último boot. Si la captura nueva tiene uptime menor que la anterior, hubo
  reinicio: marcar "reinicio detectado" y no calcular deltas de contadores.
- **Ciclo de vida de hallazgos:** si un hallazgo equivalente (misma área + entidad +
  código de regla) reaparece en la siguiente captura, se vincula al existente en vez
  de duplicarse. Estados: abierto / reconocido / en atención / resuelto / falso
  positivo, con notas.

### 5.9 Dashboard
- Global: cantidad de análisis realizados, últimos reportes, hallazgos por severidad,
  equipos analizados por estado (semáforo), clientes con más incidencias, estadísticas
  y gráficos.
- Por cliente: sus equipos con semáforo y tendencia de hallazgos.

### 5.10 Extras
- Historial y búsqueda de análisis/reportes con filtros y DataTables.
- Exportación a Excel y JSON.
- API REST documentada con Swagger/OpenAPI (subir archivo, consultar análisis,
  hallazgos y métricas) protegida con Sanctum.
- Sistema de configuración (tamaños máximos, umbrales, branding).
- Notificaciones (UI y correo) cuando un análisis detecta hallazgos Critical/High.
- Auditoría completa de acciones.

## 6. Modelo de datos (normalizado; base sugerida)

- `users`, `roles` (o spatie/laravel-permission)
- `clients` (id, name, contact, logo_path, notes)
- `devices` (id, client_id, system_mac UNIQUE, sysname, model, site, criticality)
- `captures` (id, device_id, captured_at, uploaded_at, file_path, file_hash,
  exos_version, uptime_seconds, boot_count, status, raw_summary JSON)
- `metrics` (id, capture_id, category, entity, metric, value DECIMAL, extra JSON)
  — ej.: (ports, "3:27", crc_errors, 1329450); (env, "Slot-1", temperature, 63.0)
- `findings` (id, capture_id, device_id, rule_code, level ENUM(critical, high, medium,
  low, informational), area, entity, title, description, impact, recommendation,
  evidence TEXT, file_location, status ENUM(open, acknowledged, in_progress, resolved,
  false_positive), first_seen_capture_id, edited_by, timestamps)
- `finding_attachments` (finding_id, type, path, caption)
- `reports` (capture_id, version, executive_summary, conclusions, recommendations,
  status ENUM(draft, issued), issued_by, pdf_path)
- `analyzer_rules` (code, analyzer, description, threshold_warning, threshold_critical,
  enabled, params JSON)
- `settings`, `audit_logs`

## 7. Plan de construcción por fases (pedir una por una)

1. **Fase 1 — Fundación:** scaffold Laravel 12 + Breeze + Tailwind/Flowbite/AlpineJS,
   roles y policies, CRUD de clientes, migraciones completas del modelo de datos, seeders.
2. **Fase 2 — Ingesta:** upload drag & drop, validaciones, hash/duplicados, storage,
   Job de procesamiento con estados, servicio `ExosParser` según Anexo A con tests
   unitarios sobre un tech-support real.
3. **Fase 3 — Motor de análisis:** `AnalyzerInterface`, analyzers iniciales con las
   reglas del Anexo B parametrizadas en BD, motor de correlación básico, hallazgos con
   evidencia y ubicación.
4. **Fase 4 — Reporte:** vista web del reporte con semáforo por área, edición completa
   (WYSIWYG, evidencias, severidades, hallazgos manuales), versionado y PDF profesional.
5. **Fase 5 — Histórico:** comparativo entre capturas (con manejo de reinicio de
   contadores), gráficos de tendencia, ciclo de vida y vinculación de hallazgos
   recurrentes.
6. **Fase 6 — Dashboard y extras:** dashboards global/cliente, búsqueda y filtros,
   exportación Excel/JSON, notificaciones de hallazgos críticos.
7. **Fase 7 — API y administración:** API REST + Swagger, sistema de configuración,
   umbrales editables por UI, auditoría.
8. **Fase 8 — Endurecimiento:** pruebas de integración, rendimiento con archivos
   grandes, documentación e instrucciones de instalación y despliegue en Apache + MySQL.

## Resultado esperado

Una herramienta robusta para ingeniería de redes que reduzca el tiempo de análisis de
archivos `show tech-support all`, estandarice informes técnicos de alta calidad y
permita anticipar fallas mediante el seguimiento histórico por cliente y equipo, con
diseño preparado para ampliaciones (otros comandos y fabricantes) y mantenimiento a
largo plazo.

---

## ANEXO A — Especificación del parser EXOS (validada contra archivos reales de EXOS 16.2, SummitStack X460-G2)

El archivo es texto plano. Cada comando inicia con `->comando`; los grupos con `@@@ nombre:`.

| Sección | Datos a extraer |
|---|---|
| `show switch` | SysName, System Type, System MAC, System UpTime, Boot Time, Boot Count, **Current Time** (fecha de captura), Image Booted, versiones primaria/secundaria, config activa y fecha de guardado |
| `show version detail` | Versión EXOS, **fecha de compilación de la imagen** (para antigüedad del firmware), BootROM, seriales por slot |
| `show slot` / `show slot detail` | Estado por slot (Operational/Empty/Failed), rol (MASTER/STANDBY), restart count |
| `show odometers` | Días de servicio por slot (edad del hardware) |
| `show fans detail` | Estado por FanTray y ventilador individual, RPM |
| `show temperature` | Temperatura actual, estado y máximo por slot |
| `show power` / `show power detail` | Estado de cada PSU (Powered On/Failed/Empty), voltaje de entrada, consumo total |
| `show cpu-monitoring` | Fila "System": utilización a 1 hora; procesos con utilización 1h ≥ 20% |
| `show memory` | Total DRAM y Free (KB) por slot |
| `show ports information` | Estado del link por puerto y **transiciones de link acumuladas** (columna Link UPS; ¡cuidado!: el número puede venir pegado al guion previo, ej. `- / -23830`) |
| `show port rxerrors` | CRC, oversize, undersize, fragmentos, jabber, align, lost por puerto |
| `show port txerrors` | Colisiones, late collisions, deferred, errores |
| Sección `Port Congestion` | Descartes acumulados y **descartes del último segundo** por puerto (congestión activa vs histórica) |
| `show port transceiver info` | Temperatura y potencias ópticas Tx/Rx (dBm) por puerto de fibra |
| `show log` + `show log messages nvram` | Formato `MM/DD/YYYY HH:MM:SS.ss <Sev:Componente> Slot-x: mensaje`. Detectar: `EPM.UnexpctRebootDtect` ("Booting after System Failure", con fechas), severidad Erro/Crit (`Kern.Card.Error`, kernel/crash), `HAL.Port.OpticCfgCflct` (conflicto de óptica), `AAA.authFail`, `vlan.msgs.portLinkStateDown/Up` (flapping por puerto y enlaces que negocian a 10 Mbps) |
| `show stacking`, `show stacking stack-ports`, `show port stack-ports rxerrors` | Topología (Ring/Daisy-chain), estado/rol de nodos, errores en puertos de stack |
| `show debug system-dump slot N` | Presencia de core dumps por slot |
| `show fdb stats` | Total de MACs, descartadas |
| `show inline-power` | Estado PoE por slot, watts presupuestados vs medidos |
| `show management` + `show configuration` | SSH/Telnet habilitados (y validez de llave SSH), `disable snmp access`, presencia de NTP/SNTP, puertos con velocidad forzada (`configure ports X auto off speed N`) |
| `show license` | Nivel de licencia y feature packs |
| `show lldp neighbors` / `show edp ports` / `show cdp neighbor` | Vecinos (inventario de topología) |
| `show vlan`, `show iproute summary`, `show stpd` (si existe) | VLANs, rutas, STP |

**Notas de formato validadas contra archivos reales de `ejemplos-tech/` (EXOS 12.5 stack X460-48p y EXOS 22.7 X440G2):**

- **Tres estilos de delimitador de sección** que el parser debe reconocer:
  1. `->comando` sin espacio (EXOS 22.x).
  2. `-> comando` con espacio (EXOS 12.x).
  3. Bloques `!  comando` enmarcados entre líneas de `======` (secciones agregadas
     manualmente, ej. `show fans` en el archivo stack).
- **Alias de comandos por versión** (resolver por prefijo con tabla de alias):
  `show port rxerror no-refresh` (12.x) ≡ `show ports rxerrors no-refresh` (22.x);
  `show version` (12.x) ≡ `show version detail` (22.x); `show config` (12.x) ≡
  `show configuration` (22.x); `show fans` (12.x) ≡ `show fans detail` (22.x).
- **Filtros `| exclude` (22.x):** los comandos de errores/utilización vienen con pipes
  que ocultan filas en cero (ej. `show ports rxerrors  no-refresh | exclude "0\s{7}..."`).
  Implicaciones: (a) el matching de sección debe ignorar el sufijo del pipe; (b) un
  puerto ausente en la salida = contadores en cero, NO dato faltante.
- **Detección de stack sin `show stacking`:** en EXOS 12.x no existe el comando; el
  stack se detecta por `System Type: ... (Stack)` y el rol master se infiere de las
  tablas de procesos (`Slot-N snmpMaster`). Secciones por slot 1–8 aunque solo haya
  2 nodos activos (los demás reportan "Slot N is not present").
- **Formatos de fans distintos:** `Slot-1 FanTray information` (22.x) vs
  `Fan Tray-1 FanTray-1 information` / `FanTray-2 information` (12.x), con campos
  opcionales (PartInfo, Revision) y trays `Empty` que NO son hallazgo en chasis que
  los permiten. El estado de disipadores (hot spot) viene en `show temperature`.
- **Tamaño variable:** un stack multiplica la información por nodo; el parser y los
  Jobs deben dimensionarse para archivos grandes (streaming/chunks, sin cargar
  supuestos de tamaño fijo).

**Notas de dominio (importantes para no generar falsos positivos):**
- Un load average alto (~7) es **normal** en EXOS (hilos de kernel); usar el % de
  utilización de CPU, nunca el load average.
- Los contadores de error de puertos son **acumulados desde el último boot**.
- Reinicios "Booting after System Failure" **simultáneos en todos los slots y sin core
  dumps** sugieren causa eléctrica externa (acometida/UPS), no software.
- Óptica SX (1G) en puerto configurado `auto off speed 10000` genera
  `OpticCfgCflct`: recomendar `auto on speed 1000`.
- Un puerto que negocia a 10 Mbps en red gigabit casi siempre indica un par del cable dañado.

## ANEXO B — Reglas y umbrales iniciales (sembrar en `analyzer_rules`, editables por UI)

Mapeo de severidad: Critical = falla activa o riesgo inminente · High = daño confirmado
que degrada servicio · Medium = riesgo latente/preventivo · Low = desviación menor ·
Informational = contexto.

| Código | Regla | Medium | High/Critical |
|---|---|---|---|
| PORT-CRC | CRC acumulados por puerto | ≥ 100 | ≥ 10,000 (High) |
| PORT-FRAG | Fragmentos por puerto | — | ≥ 10,000 (High) |
| PORT-OVERSIZE | Tramas oversize (MTU mismatch) | ≥ 10,000 | — |
| PORT-FLAP | Transiciones de link acumuladas | ≥ 1,000 | ≥ 10,000 (High) |
| PORT-10M | Puerto negociando a 10 Mbps en red GbE | sí | — |
| PORT-CONG | Congestión activa (drops último segundo > 0) | sí | — |
| SYS-REBOOT | Reinicios inesperados registrados | 1 | ≥ 2 (Critical) |
| LOG-ERR | Eventos log severidad Erro/Crit | 1–4 | ≥ 5 (High) |
| SYS-CORE | Core dumps presentes | sí (Medium) | — |
| CPU-1H | CPU sistema promedio 1 h | ≥ 40% | ≥ 70% (High) |
| MEM-FREE | Memoria libre | ≤ 20% | ≤ 10% (High) |
| ENV-TEMP | Temperatura | a ≤ 15 °C del máximo | estado ≠ Normal (Critical) |
| ENV-FAN | Ventilador en falla | — | cualquiera (Critical) |
| PWR-PSU | PSU en falla | — | cualquiera (Critical) |
| FW-AGE | Antigüedad del firmware | ≥ 2 años | ≥ 5 años (High) |
| HW-AGE | Edad del hardware (odómetro) | ≥ 7 años | — |
| STK-RING | Stack sin anillo completo | sí | nodo caído (Critical) |
| STK-ERR | Errores en puertos de stack | sí | — |
| OPT-CFG | Conflicto configuración de óptica | sí | — |
| MGMT-SEC | SSH/SNMP deshabilitados o sin NTP | sí | — |
| SEC-AUTH | Intentos de login fallidos ≥ 5 | Informational | — |

Correlaciones iniciales del motor: (a) SYS-REBOOT en todos los slots misma fecha + sin
SYS-CORE ⇒ sugerir revisión eléctrica del sitio; (b) PORT-CRC + PORT-FRAG + jabber en el
mismo puerto ⇒ capa física dañada (cable/conector/NIC); (c) PORT-FLAP + PORT-10M en el
mismo puerto ⇒ cableado defectuoso; (d) FW-AGE crítico + SYS-REBOOT ⇒ recomendar
actualización antes de escalar a TAC.

## ANEXO C — Referencia funcional

Existe un script Python validado (`scripts/exos_health_report.py`) que ya implementa el
parser del Anexo A y las reglas del Anexo B y genera el reporte PDF. Úsalo como
referencia funcional y para generar casos de prueba al portar la lógica a PHP.
Nota: el script usa 4 niveles (CRIT/WARN/OK/INFO); al portar se usa la escala de 5
severidades según el mapeo por regla del Anexo B.

En `ejemplos-tech/` hay dos archivos tech-support reales para fixtures de tests:
`show tech-support all_standard.txt` (X440G2, EXOS 22.7, 1 switch) y
`show tech-support all_stack.txt` (X460-48p, EXOS 12.5, stack de 3 nodos).

## ANEXO D — Decisiones de implementación registradas

- **Autenticación:** se conserva **Jetstream** (ya instalado; incluye Fortify, 2FA y
  tokens API) en lugar de Breeze.
- **Roles/permisos:** **spatie/laravel-permission** con roles `admin`, `engineer`,
  `reader` (UI en español: Administrador, Ingeniero, Lectura).
- **Frontend:** **Tailwind v4** + Flowbite 4 (plugin `@tailwindcss/vite`).
- **PDF:** DomPDF (Snappy/wkhtmltopdf es problemático en Windows/XAMPP).
- **Colas:** `QUEUE_CONNECTION=database`; en XAMPP `php artisan queue:work` manual,
  en Ubuntu producción systemd/supervisor (documentar en Fase 8).

### Veracidad y confiabilidad del análisis (obligatorio en todas las fases)

1. **Trazabilidad de umbrales:** cada regla en `analyzer_rules` lleva en `params` un
   campo `reference` que documenta el origen del umbral (IEEE 802.3, datasheet del
   modelo, KB de GTAC, catálogo de mensajes EXOS, práctica de ingeniería). Puede
   mostrarse en el reporte.
2. **Tests golden-file:** el parser y los analyzers PHP se validan contra la salida
   del script Python de referencia sobre los archivos reales de `ejemplos-tech/`;
   cualquier divergencia rompe la suite de tests.
3. **Nota metodológica en el PDF:** sección breve que aclara que los datos provienen
   del propio equipo (tech-support), qué umbrales son configurables y que las
   severidades fueron revisadas por el ingeniero responsable antes de emitir.
4. **Preferir umbrales del propio equipo:** cuando el hardware reporta sus límites
   (rangos min/normal/max de `show temperature`, umbrales de alarma DDM de los
   transceivers según SFF-8472), el analyzer usa esos valores; los del Anexo B son
   respaldo. Los estados que el switch ya evaluó (fan/PSU Failed, estado de
   temperatura, nodo de stack caído, core dumps, códigos de evento del catálogo
   EXOS) se reportan como hechos del equipo, no como interpretación.
5. **Recomendaciones conservadoras:** redactadas para verificación en ventana de
   mantenimiento o escalamiento a GTAC; nunca comandos intrusivos sin contexto. La
   herramienta es de solo lectura y el reporte siempre pasa por revisión del
   ingeniero (estados de hallazgo incluyen "falso positivo" para retroalimentar).
