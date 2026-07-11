#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
exos_health_report.py
=====================
Analiza un archivo "show tech-support all" de Extreme Networks EXOS (Switch Engine
legado / SummitStack) y genera un reporte de salud en PDF con clasificación por
semáforo: crítico / advertencia / normal.

Uso:
    python exos_health_report.py tech-support.txt
    python exos_health_report.py tech-support.txt -o reporte.pdf

Requisitos:
    pip install reportlab

Umbrales de clasificación (ajustables abajo en THRESHOLDS):
    - CRC/fragmentos por puerto, transiciones de link (flapping), temperatura,
      CPU 1h, memoria libre, antigüedad de firmware, reinicios inesperados, etc.

Autor: generado con Claude para análisis preventivo de switches EXOS.
"""

import argparse
import re
import sys
from datetime import datetime

# ----------------------------------------------------------------------------
# Umbrales ajustables
# ----------------------------------------------------------------------------
THRESHOLDS = {
    "crc_critical": 10000,       # errores CRC acumulados -> crítico
    "crc_warning": 100,          # errores CRC acumulados -> advertencia
    "frag_critical": 10000,      # fragmentos -> crítico
    "oversize_warning": 10000,   # tramas oversize -> advertencia (MTU mismatch)
    "flap_warning": 1000,        # transiciones de link acumuladas -> advertencia
    "flap_severe": 10000,        # transiciones de link -> advertencia alta
    "cpu_warning": 40.0,         # % CPU sistema (1 h)
    "cpu_critical": 70.0,
    "mem_warning": 20.0,         # % memoria libre mínima
    "mem_critical": 10.0,
    "temp_margin": 15.0,         # °C de margen respecto al máximo -> advertencia
    "fw_years_warning": 2,       # antigüedad del firmware en años
    "fw_years_critical": 5,
    "reboot_warning": 1,         # reinicios inesperados registrados
    "reboot_critical": 2,
}

CRIT, WARN, OK, INFO = "CRIT", "WARN", "OK", "INFO"


# ============================================================================
# 1) PARSEO
# ============================================================================
def split_sections(text):
    """Divide el archivo en secciones {comando: contenido} usando '->' como marca."""
    sections = {}
    current, buf = None, []
    for line in text.splitlines():
        if line.startswith("->"):
            if current:
                sections.setdefault(current, []).append("\n".join(buf))
            current = line[2:].strip()
            buf = []
        else:
            buf.append(line)
    if current:
        sections.setdefault(current, []).append("\n".join(buf))
    # une secciones repetidas
    return {k: "\n".join(v) for k, v in sections.items()}


def find_section(sections, prefix):
    """Primera sección cuyo comando empieza con prefix."""
    for k, v in sections.items():
        if k.startswith(prefix):
            return v
    return ""


def kv(text, key):
    m = re.search(rf"^{re.escape(key)}\s*:\s*(.+)$", text, re.M)
    return m.group(1).strip() if m else ""


def parse_tech_support(text):
    s = split_sections(text)
    d = {}

    # --- show switch ---
    sw = find_section(s, "show switch")
    d["sysname"] = kv(sw, "SysName")
    d["systype"] = kv(sw, "System Type")
    d["sysmac"] = kv(sw, "System MAC")
    d["uptime"] = kv(sw, "System UpTime")
    d["boot_time"] = kv(sw, "Boot Time")
    d["boot_count"] = kv(sw, "Boot Count")
    d["current_time"] = kv(sw, "Current Time")
    d["image_booted"] = kv(sw, "Image Booted")
    d["primary_ver"] = kv(sw, "Primary ver")
    d["config"] = kv(sw, "Config Booted") or kv(sw, "Config Selected")
    m = re.search(r"(\S+\.cfg)\s+Created by \S+ version (\S+)\s+(\d+) bytes saved on (.+)$", sw, re.M)
    d["config_saved"] = m.group(4).strip() if m else ""

    # fecha de captura
    d["capture_dt"] = None
    if d["current_time"]:
        try:
            d["capture_dt"] = datetime.strptime(d["current_time"], "%a %b %d %H:%M:%S %Y")
        except ValueError:
            pass

    # --- versión / fecha de build del firmware ---
    ver = find_section(s, "show version detail")
    m = re.search(r"Image\s*:\s*ExtremeXOS version (\S+).*?on\s+(\w+\s+\w+\s+\d+\s+[\d:]+\s+\S+\s+(\d{4}))",
                  ver, re.S)
    d["fw_version"] = m.group(1) if m else d["primary_ver"]
    d["fw_build_date"] = m.group(2) if m else ""
    d["fw_build_year"] = int(m.group(3)) if m else None
    d["bootrom"] = ""
    m = re.search(r"^BootROM\s*:\s*(\S+)", ver, re.M)
    if m:
        d["bootrom"] = m.group(1)

    # --- slots ---
    d["slots"] = []
    for m in re.finditer(r"^(Slot-\d+)\s+(\S+)\s+(\S+)\s+(Operational|Empty|Failed|\S+)\s+(\d+)\s*$",
                         find_section(s, "show slot"), re.M):
        d["slots"].append({"slot": m.group(1), "type": m.group(2),
                           "state": m.group(4), "ports": int(m.group(5))})
    d["slot_serials"] = dict(re.findall(r"^(Slot-\d+)\s*:\s*(\S+ \S+)\s+Rev", ver, re.M))
    d["slot_roles"] = {}
    for m in re.finditer(r"^(Slot-\d+) information:.*?Current State:\s+(\S+)",
                         find_section(s, "show slot detail"), re.S | re.M):
        d["slot_roles"][m.group(1)] = m.group(2)

    # --- odómetro ---
    d["odometer_days"] = {}
    for m in re.finditer(r"^(Slot-\d+)\s*:\s*\S+\s+(\d+)\s+(\S+)",
                         find_section(s, "show odometers"), re.M):
        d["odometer_days"][m.group(1)] = int(m.group(2))

    # --- temperatura ---
    d["temps"] = []
    for m in re.finditer(r"^(\S+)\s*:\s*(\S+)\s+([\d.]+)\s+(\w+)\s+(\d+)\s+[\d-]+\s+(\d+)",
                         find_section(s, "show temperature"), re.M):
        d["temps"].append({"unit": m.group(1), "temp": float(m.group(3)),
                           "status": m.group(4), "max": float(m.group(6))})

    # --- ventiladores ---
    fans = find_section(s, "show fans")
    d["fan_ok"] = len(re.findall(r"Fan-\d+:\s+Operational at \d+ RPM", fans))
    d["fan_bad"] = len(re.findall(r"Fan-\d+:\s+(?!Operational)\S+", fans))
    d["fantray_bad"] = [t for t in re.findall(r"(FanTray-\d+) information:\s*\n\s*State:\s+(\S+)", fans)
                        if t[1] not in ("Operational", "Empty")]

    # --- fuentes de poder ---
    pw = find_section(s, "show power detail") or find_section(s, "show power")
    d["psu_on"] = len(re.findall(r"State\s*:\s*Powered On", pw))
    d["psu_failed"] = len(re.findall(r"State\s*:\s*(Failed|Powered Off)", pw))
    m = re.search(r"System Power Usage\s*:\s*([\d.]+)\s*W", pw)
    d["power_usage"] = m.group(1) if m else ""

    # --- CPU (proceso System, columna 1 hora) ---
    d["cpu_1h"] = None
    d["cpu_high_procs"] = []
    for m in re.finditer(r"^(Slot-\d+|\S+)\s+(\S+)\s+((?:[\d.]+\s+){7})([\d.]+)",
                         find_section(s, "show cpu-monitoring"), re.M):
        cols = m.group(3).split()
        proc, util_1h = m.group(2), float(cols[6])
        if proc == "System" and d["cpu_1h"] is None:
            d["cpu_1h"] = util_1h
        elif proc != "System" and util_1h >= 20.0:
            d["cpu_high_procs"].append((proc, util_1h))

    # --- memoria ---
    mem = find_section(s, "show memory")
    tot = re.search(r"Total DRAM \(KB\):\s*(\d+)", mem)
    fre = re.search(r"Free\s+\(KB\):\s*(\d+)", mem)
    d["mem_total_kb"] = int(tot.group(1)) if tot else None
    d["mem_free_kb"] = int(fre.group(1)) if fre else None

    # --- puertos: transiciones de link (flapping) ---
    d["port_flaps"] = {}
    d["ports_active"] = 0
    for m in re.finditer(r"^(\d+:\d+)\s+\S+\s+(\w+)\s+.*?/\s*-\s*(\d+)",
                         find_section(s, "show ports information"), re.M):
        port, state, ups = m.group(1), m.group(2), int(m.group(3))
        d["port_flaps"][port] = ups
        if state == "active":
            d["ports_active"] += 1

    # --- errores rx por puerto ---
    d["rxerrors"] = []
    for m in re.finditer(r"^(\d+:\d+)\s+(\w+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)",
                         find_section(s, "show port rxerrors"), re.M):
        d["rxerrors"].append({"port": m.group(1), "crc": int(m.group(3)),
                              "over": int(m.group(4)), "under": int(m.group(5)),
                              "frag": int(m.group(6)), "jabber": int(m.group(7))})

    # --- errores en stack-ports ---
    d["stack_rxerrors"] = []
    for m in re.finditer(r"^(\d+:\d+)\s+(\w+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)",
                         find_section(s, "show port stack-ports rxerrors"), re.M):
        if sum(int(m.group(i)) for i in range(3, 10)) > 0:
            d["stack_rxerrors"].append(m.group(1))

    # --- congestión ---
    d["congestion"] = []
    cong = find_section(s, "Port Congestion")
    for m in re.finditer(r"^(\d+:\d+)\s+(\d+)\s+(\d+)\s*$", cong, re.M):
        d["congestion"].append({"port": m.group(1), "drops": int(m.group(2)),
                                "last_sec": int(m.group(3))})

    # --- log (buffer + NVRAM) ---
    logtxt = find_section(s, "show log") + "\n" + find_section(s, "show log messages nvram")
    events = re.findall(r"^(\d{2}/\d{2}/\d{4}) [\d:.]+\s+<(\w+):([\w.]+)>\s+(\S+):\s*(.*)$",
                        logtxt, re.M)
    d["log_total"] = len(re.findall(r"^\d{2}/\d{2}/\d{4}", find_section(s, "show log"), re.M))
    d["reboots"] = sorted({date for date, sev, comp, slot, msg in events
                           if "UnexpctRebootDtect" in comp},
                          key=lambda x: datetime.strptime(x, "%m/%d/%Y"))
    d["log_errors"] = sorted({f"{date} <{sev}:{comp}> {msg}" for date, sev, comp, slot, msg in events
                              if sev in ("Erro", "Crit")})
    d["optic_conflicts"] = sorted({msg for _, _, comp, _, msg in events if "OpticCfgCflct" in comp})
    d["auth_fails"] = sum(1 for _, _, comp, _, _ in events if "authFail" in comp)
    d["link_downs"] = {}
    for _, _, comp, _, msg in events:
        if "portLinkStateDown" in comp:
            pm = re.search(r"Port (\d+:\d+)", msg)
            if pm:
                d["link_downs"][pm.group(1)] = d["link_downs"].get(pm.group(1), 0) + 1
    d["speed_10m"] = sorted({re.search(r"Port (\d+:\d+)", msg).group(1)
                             for _, _, comp, _, msg in events
                             if "portLinkStateUp" in comp and "10 Mbps" in msg
                             and re.search(r"Port (\d+:\d+)", msg)})

    # --- stacking ---
    stk = find_section(s, "show stacking\n") or find_section(s, "show stacking")
    d["stack_ring"] = "Active Topology is a Ring" in stk
    d["stack_nodes"] = re.findall(r"^\*?\s*([0-9a-f:]{17})\s+(\d+)\s+(\S+)\s+(\S+)", stk, re.M)
    d["stack_failed_nodes"] = [n for n in d["stack_nodes"] if n[2] not in ("Active",)]

    # --- core dumps ---
    dumps = [k for k in s if k.startswith("show debug system-dump")]
    d["core_dumps"] = []
    for k in dumps:
        if "No core dump" not in s[k] and "not present" not in s[k] and s[k].strip():
            d["core_dumps"].append(k)

    # --- fdb ---
    m = re.search(r"Total:\s*(\d+).*Dyn:\s*(\d+)", find_section(s, "show fdb stats"))
    d["fdb_total"] = m.group(1) if m else ""

    # --- gestión ---
    mgmt = find_section(s, "show management")
    d["ssh"] = kv(mgmt, "SSH access")
    d["telnet"] = kv(mgmt, "Telnet access")
    cfg = find_section(s, "show configuration")
    d["snmp_disabled"] = "disable snmp access" in cfg
    d["ntp_configured"] = bool(re.search(r"^(configure|enable) sntp", cfg, re.M)) or \
                          bool(re.search(r"^configure ntp", cfg, re.M))

    # --- licencia ---
    m = re.search(r"Enabled License Level:\s*\n\s*(\S+)", find_section(s, "show license"))
    d["license"] = m.group(1) if m else ""

    # --- PoE ---
    d["poe"] = re.findall(r"^(\d+)\s+(Enabled|Disabled)\s+(\S+)\s+(\d+)\s*W\s+(\d+)\s*W",
                          find_section(s, "show inline-power"), re.M)
    return d


# ============================================================================
# 2) CLASIFICACIÓN
# ============================================================================
def classify(d):
    """Devuelve lista de hallazgos (nivel, área, título, detalle) y estado por área."""
    F = []
    T = THRESHOLDS

    # Reinicios inesperados
    n = len(d["reboots"])
    if n >= T["reboot_critical"]:
        F.append((CRIT, "Estabilidad",
                  f"{n} reinicio(s) inesperado(s) del sistema (\"Booting after System Failure\")",
                  "Fechas: " + ", ".join(d["reboots"]) +
                  ". Si afectan a todos los nodos a la vez y no hay core dumps, revisar la "
                  "alimentación eléctrica del sitio; abrir caso con TAC."))
    elif n >= T["reboot_warning"]:
        F.append((WARN, "Estabilidad", f"{n} reinicio inesperado registrado",
                  "Fecha: " + ", ".join(d["reboots"])))
    if d["log_errors"]:
        lv = WARN if len(d["log_errors"]) <= 4 else CRIT
        F.append((lv, "Estabilidad", "Eventos Error/Critical en el log",
                  "; ".join(d["log_errors"][:6])))
    if d["core_dumps"]:
        F.append((WARN, "Estabilidad", "Core dumps presentes",
                  ", ".join(d["core_dumps"]) + " — recolectarlos y adjuntarlos al caso TAC."))

    # Errores físicos
    for r in d["rxerrors"]:
        if r["crc"] >= T["crc_critical"] or r["frag"] >= T["frag_critical"]:
            F.append((CRIT, "Puertos",
                      f"Puerto {r['port']}: capa física dañada",
                      f"CRC={r['crc']:,} oversize={r['over']:,} fragmentos={r['frag']:,} "
                      f"jabber={r['jabber']:,}. Reemplazar/certificar cableado y revisar NIC."))
        elif r["crc"] >= T["crc_warning"]:
            F.append((WARN, "Puertos", f"Puerto {r['port']}: errores CRC moderados",
                      f"CRC={r['crc']:,} fragmentos={r['frag']:,}. Vigilar tendencia."))
        elif r["over"] >= T["oversize_warning"]:
            F.append((WARN, "Puertos", f"Puerto {r['port']}: tramas oversize",
                      f"{r['over']:,} tramas mayores al MTU del puerto — revisar MTU/jumbo del "
                      "dispositivo conectado."))

    # Flapping
    flappers = sorted([(p, u) for p, u in d["port_flaps"].items() if u >= T["flap_warning"]],
                      key=lambda x: -x[1])
    if flappers:
        sev = WARN
        det = ", ".join(f"{p} ({u:,})" for p, u in flappers[:10])
        recent = sorted(d["link_downs"].items(), key=lambda x: -x[1])[:5]
        if recent:
            det += ". Caídas en log reciente: " + ", ".join(f"{p} ({c})" for p, c in recent)
        if d["speed_10m"]:
            det += ". Negocian a 10 Mbps (posible par de cable dañado): " + ", ".join(d["speed_10m"])
        title = "Flapping en puertos (transiciones de link acumuladas)"
        if any(u >= T["flap_severe"] for _, u in flappers):
            title = "Flapping SEVERO en puertos"
        F.append((sev, "Puertos", title, det))

    # Firmware
    if d["fw_build_year"] and d["capture_dt"]:
        age = d["capture_dt"].year - d["fw_build_year"]
        if age >= T["fw_years_critical"]:
            F.append((CRIT, "Firmware",
                      f"Firmware EXOS {d['fw_version']} con ~{age} años de antigüedad",
                      f"Compilado: {d['fw_build_date']}. Probablemente fuera de soporte; "
                      "planear actualización en ventana de mantenimiento."))
        elif age >= T["fw_years_warning"]:
            F.append((WARN, "Firmware",
                      f"Firmware EXOS {d['fw_version']} con ~{age} años de antigüedad",
                      f"Compilado: {d['fw_build_date']}. Verificar últimas versiones y avisos de seguridad."))
    days = max(d["odometer_days"].values()) if d["odometer_days"] else 0
    if days > 2555:  # ~7 años
        F.append((WARN, "Firmware", f"Hardware con {days:,} días de servicio (~{days/365:.1f} años)",
                  "Considerar plan de ciclo de vida / reemplazo."))

    # Óptica / configuración
    for msg in d["optic_conflicts"]:
        F.append((WARN, "Puertos", "Conflicto de configuración de óptica", msg))

    # Ambiente
    for t in d["temps"]:
        if t["status"].lower() != "normal":
            F.append((CRIT, "Ambiente", f"{t['unit']}: temperatura {t['status']}",
                      f"{t['temp']} °C (máx {t['max']} °C)"))
        elif t["max"] - t["temp"] <= T["temp_margin"]:
            F.append((WARN, "Ambiente", f"{t['unit']}: temperatura cerca del límite",
                      f"{t['temp']} °C de un máximo de {t['max']} °C"))
    if d["fan_bad"] or d["fantray_bad"]:
        F.append((CRIT, "Ambiente", "Ventiladores con falla",
                  f"{d['fan_bad']} ventilador(es) no operativos; bandejas: {d['fantray_bad']}"))
    if d["psu_failed"]:
        F.append((CRIT, "Alimentación", f"{d['psu_failed']} fuente(s) de poder en falla",
                  "Revisar PSU y alimentación de entrada."))

    # Slots / stacking
    for sl in d["slots"]:
        if sl["state"] not in ("Operational", "Empty"):
            F.append((CRIT, "Stacking", f"{sl['slot']} en estado {sl['state']}", sl["type"]))
    if d["stack_nodes"] and not d["stack_ring"]:
        F.append((WARN, "Stacking", "La topología de stack NO es un anillo completo",
                  "Un enlace de stack caído elimina la redundancia."))
    if d["stack_failed_nodes"]:
        F.append((CRIT, "Stacking", "Nodos de stack fuera de estado Active",
                  str(d["stack_failed_nodes"])))
    if d["stack_rxerrors"]:
        F.append((WARN, "Stacking", "Errores en puertos de stack",
                  "Puertos: " + ", ".join(d["stack_rxerrors"])))

    # CPU / memoria
    if d["cpu_1h"] is not None:
        if d["cpu_1h"] >= T["cpu_critical"]:
            F.append((CRIT, "CPU/Memoria", f"CPU del sistema al {d['cpu_1h']}% (1 h)", ""))
        elif d["cpu_1h"] >= T["cpu_warning"]:
            F.append((WARN, "CPU/Memoria", f"CPU del sistema al {d['cpu_1h']}% (1 h)", ""))
    if d["cpu_high_procs"]:
        F.append((WARN, "CPU/Memoria", "Procesos con CPU sostenida alta (1 h)",
                  ", ".join(f"{p} ({u}%)" for p, u in d["cpu_high_procs"])))
    if d["mem_total_kb"] and d["mem_free_kb"]:
        pct = 100.0 * d["mem_free_kb"] / d["mem_total_kb"]
        if pct <= T["mem_critical"]:
            F.append((CRIT, "CPU/Memoria", f"Memoria libre {pct:.0f}%", "Posible fuga de memoria."))
        elif pct <= T["mem_warning"]:
            F.append((WARN, "CPU/Memoria", f"Memoria libre {pct:.0f}%", "Vigilar tendencia."))

    # Gestión
    mg = []
    if d["ssh"].startswith("Disabled"):
        mg.append("SSH deshabilitado" + (" (llave inválida)" if "invalid" in d["ssh"] else ""))
    if d["snmp_disabled"]:
        mg.append("acceso SNMP deshabilitado")
    if not d["ntp_configured"]:
        mg.append("sin NTP/SNTP configurado")
    if mg:
        F.append((WARN, "Gestión", "Gestión y monitoreo limitados",
                  "; ".join(mg) + ". Sin gestión remota no hay monitoreo proactivo."))
    if d["auth_fails"] >= 5:
        F.append((INFO, "Gestión", f"{d['auth_fails']} intentos de login fallidos en el log",
                  "Verificar que correspondan a personal autorizado."))

    # Congestión (informativo)
    heavy = [c for c in d["congestion"] if c["drops"] > 100_000_000]
    active_cong = [c for c in d["congestion"] if c["last_sec"] > 0]
    if active_cong:
        F.append((WARN, "Puertos", "Congestión ACTIVA al momento de la captura",
                  ", ".join(f"{c['port']} ({c['last_sec']}/s)" for c in active_cong[:8])))
    elif heavy:
        F.append((INFO, "Puertos", "Descartes acumulados altos por congestión (histórico)",
                  ", ".join(f"{c['port']} ({c['drops']:,})" for c in
                            sorted(heavy, key=lambda x: -x['drops'])[:8]) +
                  ". Sin congestión activa en la captura; evaluar capacidad de uplinks."))
    return F


def area_status(findings):
    """Estado agregado por área para la tabla semáforo."""
    areas = ["Estabilidad", "Puertos", "Firmware", "Ambiente", "Alimentación",
             "Stacking", "CPU/Memoria", "Gestión"]
    status = {}
    for a in areas:
        lv = [f[0] for f in findings if f[1] == a]
        status[a] = CRIT if CRIT in lv else (WARN if WARN in lv else OK)
    return status


# ============================================================================
# 3) PDF
# ============================================================================
def build_pdf(d, findings, outfile):
    from reportlab.lib.pagesizes import letter
    from reportlab.lib.units import cm
    from reportlab.lib import colors
    from reportlab.platypus import (SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle)
    from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
    from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY

    PURPLE = colors.HexColor("#6C3FA0")
    RED, AMBER, GREEN = colors.HexColor("#C0392B"), colors.HexColor("#E67E22"), colors.HexColor("#1E8449")
    BLUE = colors.HexColor("#2471A3")
    GREY_BG, ROW_ALT = colors.HexColor("#F2F0F5"), colors.HexColor("#FAF9FC")
    LV_COLOR = {CRIT: RED, WARN: AMBER, OK: GREEN, INFO: BLUE}
    LV_NAME = {CRIT: "CRÍTICO", WARN: "ADVERTENCIA", OK: "NORMAL", INFO: "INFORMATIVO"}

    styles = getSampleStyleSheet()
    st_title = ParagraphStyle('t', parent=styles['Title'], fontSize=18, textColor=PURPLE, spaceAfter=2)
    st_sub = ParagraphStyle('s', parent=styles['Normal'], fontSize=10, alignment=TA_CENTER)
    st_h1 = ParagraphStyle('h1', parent=styles['Heading1'], fontSize=13, textColor=PURPLE,
                           spaceBefore=14, spaceAfter=6)
    st_body = ParagraphStyle('b', parent=styles['Normal'], fontSize=9.5, leading=13, alignment=TA_JUSTIFY)
    st_cell = ParagraphStyle('c', parent=styles['Normal'], fontSize=8.6, leading=11.5)
    st_cellb = ParagraphStyle('cb', parent=st_cell, fontName='Helvetica-Bold')
    st_cellw = ParagraphStyle('cw', parent=st_cellb, textColor=colors.white)
    st_small = ParagraphStyle('sm', parent=styles['Normal'], fontSize=8, leading=10.5,
                              textColor=colors.HexColor("#555555"))

    def P(x, st=None): return Paragraph(x, st or st_cell)
    def dot(lv): return Paragraph(f'<font color="{LV_COLOR[lv].hexval()[2:] and "#"+LV_COLOR[lv].hexval()[2:]}" size="11">&#9679;</font>', st_cell)

    doc = SimpleDocTemplate(outfile, pagesize=letter, leftMargin=1.9*cm, rightMargin=1.9*cm,
                            topMargin=1.6*cm, bottomMargin=1.6*cm,
                            title=f"Reporte de salud - {d['sysname'] or 'switch EXOS'}")
    E = []
    E.append(Paragraph("Reporte de Salud del Equipo", st_title))
    E.append(Paragraph(f"{d['systype'] or 'Extreme Networks EXOS'} &nbsp;|&nbsp; "
                       f"SysName: <b>{d['sysname'] or 'N/D'}</b>", st_sub))
    E.append(Spacer(1, 3))
    E.append(Paragraph(f"Fuente: show tech-support all capturado el {d['current_time'] or 'N/D'} "
                       f"&nbsp;•&nbsp; Reporte generado el {datetime.now():%d-%b-%Y}", st_sub))
    E.append(Spacer(1, 10))

    # Resumen semáforo
    E.append(Paragraph("1. Resumen por área", st_h1))
    status = area_status(findings)
    rows = [[P("Área", st_cellw), P("Estado", st_cellw), P("Resumen", st_cellw)]]
    for area, lv in status.items():
        rel = [f for f in findings if f[1] == area and f[0] in (CRIT, WARN)]
        txt = "; ".join(f[2] for f in rel[:3]) if rel else "Sin hallazgos — dentro de parámetros"
        if len(rel) > 3:
            txt += f" (+{len(rel)-3} más)"
        rows.append([P(area), dot(lv), P(txt)])
    t = Table(rows, colWidths=[4.2*cm, 1.5*cm, 11.4*cm])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), PURPLE),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, ROW_ALT]),
        ('GRID', (0, 0), (-1, -1), 0.4, colors.HexColor("#D5CCE3")),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'), ('ALIGN', (1, 0), (1, -1), 'CENTER'),
        ('TOPPADDING', (0, 0), (-1, -1), 3.5), ('BOTTOMPADDING', (0, 0), (-1, -1), 3.5)]))
    E.append(t)
    E.append(Spacer(1, 4))
    E.append(Paragraph('<font color="#C0392B">&#9679;</font> Crítico &nbsp;&nbsp; '
                       '<font color="#E67E22">&#9679;</font> Advertencia &nbsp;&nbsp; '
                       '<font color="#1E8449">&#9679;</font> Normal &nbsp;&nbsp; '
                       '<font color="#2471A3">&#9679;</font> Informativo', st_small))

    # Identificación
    E.append(Paragraph("2. Identificación del equipo", st_h1))
    slots_op = sum(1 for x in d["slots"] if x["state"] == "Operational")
    days = max(d["odometer_days"].values()) if d["odometer_days"] else 0
    ident = [
        [P("Modelo", st_cellb), P(d["systype"] or "N/D"),
         P("Licencia", st_cellb), P(d["license"] or "N/D")],
        [P("MAC de sistema", st_cellb), P(d["sysmac"] or "N/D"),
         P("Versión EXOS", st_cellb), P(f"{d['fw_version']} ({d['fw_build_date'] or 's/f'})")],
        [P("Uptime", st_cellb), P(f"{d['uptime'] or 'N/D'} · Boot count: {d['boot_count'] or 'N/D'}"),
         P("BootROM", st_cellb), P(d["bootrom"] or "N/D")],
        [P("Slots/nodos", st_cellb), P(f"{slots_op} operativo(s) de {len(d['slots'])} configurados"),
         P("Odómetro", st_cellb), P(f"{days:,} días (~{days/365:.1f} años)" if days else "N/D")],
        [P("Configuración", st_cellb), P(f"{d['config'] or 'N/D'}" +
                                         (f", guardada {d['config_saved']}" if d['config_saved'] else "")),
         P("Boot", st_cellb), P(d["boot_time"] or "N/D")],
    ]
    t = Table(ident, colWidths=[2.7*cm, 6.4*cm, 2.7*cm, 5.3*cm])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, -1), GREY_BG), ('BACKGROUND', (2, 0), (2, -1), GREY_BG),
        ('GRID', (0, 0), (-1, -1), 0.4, colors.HexColor("#D5CCE3")),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 3), ('BOTTOMPADDING', (0, 0), (-1, -1), 3)]))
    E.append(t)

    # Hallazgos por nivel
    order = [(CRIT, "3. Hallazgos críticos — requieren atención"),
             (WARN, "4. Advertencias preventivas"),
             (INFO, "5. Notas informativas")]
    num = 3
    for lv, heading in order:
        rel = [f for f in findings if f[0] == lv]
        if not rel:
            continue
        E.append(Paragraph(heading, st_h1))
        rows = [[P("#", st_cellw), P("Área", st_cellw), P("Hallazgo", st_cellw), P("Detalle", st_cellw)]]
        for i, (l, area, title, det) in enumerate(rel, 1):
            rows.append([P(str(i)), P(area), P(f"<b>{title}</b>"), P(det or "—")])
        t = Table(rows, colWidths=[0.8*cm, 2.2*cm, 5.5*cm, 8.6*cm])
        t.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, 0), LV_COLOR[lv]),
            ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, ROW_ALT]),
            ('GRID', (0, 0), (-1, -1), 0.4, colors.HexColor("#DDDDDD")),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'), ('ALIGN', (0, 0), (0, -1), 'CENTER'),
            ('TOPPADDING', (0, 0), (-1, -1), 3), ('BOTTOMPADDING', (0, 0), (-1, -1), 3)]))
        E.append(t)
        num += 1

    # Parámetros normales
    E.append(Paragraph(f"{num}. Parámetros verificados sin hallazgos", st_h1))
    ok_rows = [[P("Parámetro", st_cellw), P("Valor observado", st_cellw), P("", st_cellw)]]
    checks = []
    if d["temps"] and all(t["status"].lower() == "normal" for t in d["temps"]):
        rng = f"{min(t['temp'] for t in d['temps']):.1f}–{max(t['temp'] for t in d['temps']):.1f} °C " \
              f"(máx. {d['temps'][0]['max']:.0f} °C)"
        checks.append(("Temperatura", rng))
    if d["fan_ok"] and not d["fan_bad"]:
        checks.append(("Ventiladores", f"{d['fan_ok']}/{d['fan_ok']} operativos"))
    if d["psu_on"] and not d["psu_failed"]:
        checks.append(("Fuentes de poder", f"{d['psu_on']} PSU con energía · consumo {d['power_usage']} W"))
    if d["cpu_1h"] is not None and d["cpu_1h"] < THRESHOLDS["cpu_warning"]:
        checks.append(("CPU", f"Sistema {d['cpu_1h']}% (promedio 1 h)"))
    if d["mem_total_kb"] and d["mem_free_kb"]:
        pct = 100.0 * d["mem_free_kb"] / d["mem_total_kb"]
        if pct > THRESHOLDS["mem_warning"]:
            checks.append(("Memoria", f"{d['mem_free_kb']//1024} MB libres de "
                                      f"{d['mem_total_kb']//1024} MB ({pct:.0f}%)"))
    if d["stack_nodes"]:
        if d["stack_ring"] and not d["stack_failed_nodes"]:
            checks.append(("Stacking", f"Anillo activo, {len(d['stack_nodes'])}/"
                                       f"{len(d['stack_nodes'])} nodos Active"))
        if not d["stack_rxerrors"]:
            checks.append(("Puertos de stack", "Sin errores Rx/Tx"))
    if not d["core_dumps"]:
        checks.append(("Core dumps", "Ninguno"))
    if d["fdb_total"]:
        checks.append(("Tabla MAC (FDB)", f"{d['fdb_total']} entradas"))
    if d["poe"]:
        used = sum(int(x[4]) for x in d["poe"])
        budg = sum(int(x[3]) for x in d["poe"])
        checks.append(("PoE", f"{used} W medidos de {budg} W presupuestados"))
    checks.append(("Puertos activos", f"{d['ports_active']} enlaces activos"))
    for name, val in checks:
        ok_rows.append([P(name), P(val), dot(OK)])
    t = Table(ok_rows, colWidths=[4.5*cm, 11.3*cm, 1.3*cm])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), GREEN),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, ROW_ALT]),
        ('GRID', (0, 0), (-1, -1), 0.4, colors.HexColor("#C8DCC9")),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'), ('ALIGN', (2, 0), (2, -1), 'CENTER'),
        ('TOPPADDING', (0, 0), (-1, -1), 3), ('BOTTOMPADDING', (0, 0), (-1, -1), 3)]))
    E.append(t)

    # Metodología
    E.append(Paragraph(f"{num+1}. Metodología", st_h1))
    E.append(Paragraph(
        "Reporte generado automáticamente con exos_health_report.py a partir de las secciones del "
        "show tech-support all: switch, version, slot, odometers, fans, temperature, power, "
        "cpu-monitoring, memory, ports information/rxerrors, congestión, log y log NVRAM, stacking y "
        "sus contadores, fdb, inline-power, management, configuración y system-dump. La clasificación "
        "aplica umbrales configurables (ver THRESHOLDS en el script). Los contadores de error son "
        "acumulados desde el último arranque salvo indicación contraria. Nota: en EXOS un load average "
        "alto (~7) es normal; la referencia válida es el porcentaje de utilización de CPU.", st_body))

    doc.build(E)


# ============================================================================
# main
# ============================================================================
def main():
    ap = argparse.ArgumentParser(description="Reporte de salud PDF desde show tech-support all (EXOS)")
    ap.add_argument("archivo", help="archivo de texto del show tech-support all")
    ap.add_argument("-o", "--output", default=None, help="nombre del PDF de salida")
    args = ap.parse_args()

    try:
        with open(args.archivo, encoding="utf-8", errors="replace") as f:
            text = f.read()
    except OSError as e:
        sys.exit(f"No se pudo leer el archivo: {e}")

    if "show tech-support" not in text and "->show switch" not in text:
        print("Aviso: el archivo no parece un show tech-support de EXOS; se intentará de todas formas.")

    d = parse_tech_support(text)
    findings = classify(d)
    out = args.output or (re.sub(r"\.\w+$", "", args.archivo) + "_reporte.pdf")
    build_pdf(d, findings, out)

    # resumen en consola
    ncrit = sum(1 for f in findings if f[0] == CRIT)
    nwarn = sum(1 for f in findings if f[0] == WARN)
    print(f"Equipo: {d['sysname']} ({d['systype']}) EXOS {d['fw_version']} — uptime {d['uptime']}")
    print(f"Hallazgos: {ncrit} críticos, {nwarn} advertencias.")
    for lv, area, title, _ in findings:
        print(f"  [{lv}] {area}: {title}")
    print(f"PDF generado: {out}")


if __name__ == "__main__":
    main()
