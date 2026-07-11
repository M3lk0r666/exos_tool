import './bootstrap';

// Quill 2 (editor WYSIWYG local, sin dependencia de internet)
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

window.Quill = Quill;

/**
 * Inicializa editores Quill sobre elementos .quill-editor.
 * Cada editor sincroniza su HTML al input oculto indicado en data-target
 * al enviar cualquier formulario contenedor.
 */
function initQuillEditors() {
    document.querySelectorAll('.quill-editor').forEach((el) => {
        if (el.dataset.quillReady) return;
        el.dataset.quillReady = '1';

        const quill = new Quill(el, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ color: [] }, { background: [] }],
                    ['link', 'image', 'code-block'],
                    ['clean'],
                ],
            },
        });

        const target = document.getElementById(el.dataset.target);
        if (target && target.value) {
            quill.clipboard.dangerouslyPasteHTML(target.value);
        }

        const form = el.closest('form');
        if (form && target) {
            form.addEventListener('submit', () => {
                target.value = quill.getSemanticHTML();
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', initQuillEditors);

// ApexCharts (gráficos de tendencia locales, Fase 5)
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

function initTrendCharts() {
    const baseOptions = {
        chart: { height: 260, zoom: { enabled: false }, toolbar: { show: false }, animations: { enabled: false } },
        stroke: { curve: 'straight', width: 2 },
        markers: { size: 4 },
        xaxis: { type: 'category', labels: { rotate: -35, style: { fontSize: '10px' } } },
        tooltip: { shared: true },
        legend: { position: 'top' },
        noData: { text: 'Sin datos' },
    };

    document.querySelectorAll('[data-chart="line"]').forEach((el) => {
        if (el.dataset.chartReady) return;
        el.dataset.chartReady = '1';

        const series = JSON.parse(el.dataset.series || '[]');
        new ApexCharts(el, { ...baseOptions, chart: { ...baseOptions.chart, type: 'line' }, series }).render();
    });

    document.querySelectorAll('[data-chart="severity"]').forEach((el) => {
        if (el.dataset.chartReady) return;
        el.dataset.chartReady = '1';

        const series = JSON.parse(el.dataset.series || '[]');
        const labels = JSON.parse(el.dataset.labels || '[]');

        new ApexCharts(el, {
            chart: { type: 'bar', height: 280, stacked: true, toolbar: { show: false }, animations: { enabled: false } },
            series,
            xaxis: { categories: labels, labels: { rotate: -35, style: { fontSize: '10px' } } },
            colors: ['#dc2626', '#ea580c', '#ca8a04', '#2563eb', '#6b7280'],
            legend: { position: 'top' },
            dataLabels: { enabled: false },
        }).render();
    });
}

document.addEventListener('DOMContentLoaded', initTrendCharts);

// Gráficos del dashboard (donut y barras)
function initDashboardCharts() {
    document.querySelectorAll('[data-chart="donut"]').forEach((el) => {
        if (el.dataset.chartReady) return;
        el.dataset.chartReady = '1';

        new ApexCharts(el, {
            chart: { type: 'donut', height: 280, animations: { enabled: false } },
            series: JSON.parse(el.dataset.values || '[]'),
            labels: JSON.parse(el.dataset.labels || '[]'),
            colors: ['#dc2626', '#ea580c', '#ca8a04', '#2563eb', '#6b7280'],
            legend: { position: 'bottom' },
            noData: { text: 'Sin datos' },
        }).render();
    });

    document.querySelectorAll('[data-chart="bar"]').forEach((el) => {
        if (el.dataset.chartReady) return;
        el.dataset.chartReady = '1';

        new ApexCharts(el, {
            chart: { type: 'bar', height: 280, toolbar: { show: false }, animations: { enabled: false } },
            series: [{ name: 'Análisis', data: JSON.parse(el.dataset.values || '[]') }],
            xaxis: { categories: JSON.parse(el.dataset.labels || '[]') },
            colors: ['#2563eb'],
            dataLabels: { enabled: false },
            noData: { text: 'Sin datos' },
        }).render();
    });
}

document.addEventListener('DOMContentLoaded', initDashboardCharts);
