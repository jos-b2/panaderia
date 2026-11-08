/**
 * Panel - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/panel.js
 *  - Pestañas (Producción vs ventas / Próximas entregas / Alertas)
 *  - Gráfica de los últimos 7 días con Chart.js (estilo común en graficas.js).
 *    La leyenda de Chart.js permite ocultar o mostrar cada serie con un clic.
 */
document.addEventListener('DOMContentLoaded', () => {
    /* ---------- Pestañas ---------- */
    const tabButtons = document.querySelectorAll('.panel-tab-btn');
    const tabContents = document.querySelectorAll('.panel-tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const targetEl = document.getElementById(button.getAttribute('data-target'));
            if (targetEl) targetEl.classList.add('active');
        });
    });

    /* ---------- Gráfica: producción vs ventas ---------- */
    const fuente = document.getElementById('datosPanel');
    const canvas = document.getElementById('chartPanel');
    if (!fuente || !canvas || typeof Chart === 'undefined' || !window.Graficas) return;

    const dias = JSON.parse(fuente.textContent);
    const { C } = window.Graficas;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: dias.map(d => d.label),
            datasets: [
                { label: 'Horneadas', data: dias.map(d => d.horneadas), backgroundColor: C.accent, hoverBackgroundColor: '#A5722C', maxBarThickness: 26 },
                { label: 'Vendidas',  data: dias.map(d => d.vendidas),  backgroundColor: C.ink,    hoverBackgroundColor: '#24170F', maxBarThickness: 26 }
            ]
        },
        options: {
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Piezas' } }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (c) => ` ${c.dataset.label}: ${c.parsed.y} piezas`,
                        // Diferencia del día: lo horneado que no se vendió (o el excedente)
                        footer: (items) => {
                            const h = items.find(i => i.dataset.label === 'Horneadas');
                            const v = items.find(i => i.dataset.label === 'Vendidas');
                            if (!h || !v) return '';
                            const dif = h.parsed.y - v.parsed.y;
                            return dif === 0 ? 'Sin diferencia' : (dif > 0 ? `${dif} sin vender` : `${-dif} vendidas de más`);
                        }
                    }
                }
            }
        }
    });
});
