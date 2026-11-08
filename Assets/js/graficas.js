/**
 * Estilo común de las gráficas (Chart.js) - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/graficas.js
 * Lo usan el Panel y Reportes. Debe cargarse después de Chart.js.
 *
 * Expone window.Graficas con la paleta y utilidades de formato.
 */
(function () {
    if (typeof Chart === 'undefined') return;

    const C = {
        ink: '#3B2417', accent: '#C08A3E', soft: '#E8C089', green: '#3F7046',
        blue: '#2F4A6B', red: '#A33A2C', muted: '#8C7868', line: '#EFE7DA'
    };

    /* ---------- Valores por defecto ---------- */
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = C.muted;
    Chart.defaults.borderColor = C.line;
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.plugins.legend.labels.boxHeight = 12;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.tooltip.backgroundColor = '#24170F';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 0;
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.devicePixelRatio = 2;            // nitidez al exportar
    Chart.defaults.elements.bar.borderRadius = 0;    // esquinas rectas, como el resto del sistema

    /* ---------- Fondo blanco y aviso "Sin datos" ---------- */
    Chart.register({
        id: 'fondoYVacio',
        beforeDraw(chart) {
            // Se pinta el lienzo completo en píxeles reales (sin la escala de devicePixelRatio),
            // para que el PNG exportado no tenga zonas transparentes
            const { ctx, canvas } = chart;
            ctx.save();
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.restore();
        },
        afterDraw(chart) {
            const hayDatos = chart.data.datasets.some(ds => ds.data.some(v => v !== null && Number(v) !== 0));
            if (hayDatos) return;
            const { ctx, chartArea } = chart;
            const area = chartArea || { left: 0, right: chart.width, top: 0, bottom: chart.height };
            ctx.save();
            ctx.fillStyle = 'rgba(255,255,255,0.85)';
            ctx.fillRect(area.left, area.top, area.right - area.left, area.bottom - area.top);
            ctx.fillStyle = C.muted;
            ctx.font = "600 13px 'Inter', sans-serif";
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('Sin datos en este periodo', (area.left + area.right) / 2, (area.top + area.bottom) / 2);
            ctx.restore();
        }
    });

    /* ---------- Utilidades ---------- */
    const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    window.Graficas = {
        C,
        /** "2026-11-07" → "7 nov" */
        fechaCorta: (f) => { const [, m, d] = f.split('-'); return `${+d} ${meses[+m - 1]}`; },
        /** 1234.5 → "$1,234.50" */
        dinero: (v) => '$' + Number(v).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
        /** Eje Y con signo de dólar */
        ejeDinero: { ticks: { callback: (v) => '$' + v } }
    };
})();
