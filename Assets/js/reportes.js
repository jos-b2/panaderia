/**
 * Vista de Reportes - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/reportes.js
 *  - Dibuja las gráficas con Chart.js usando la paleta del sistema
 *  - "Exportar PDF": envía cada gráfica como imagen PNG al servidor (dompdf)
 */
document.addEventListener('DOMContentLoaded', () => {
    const fuente = document.getElementById('datosReporte');
    if (!fuente || typeof Chart === 'undefined' || !window.Graficas) return;

    const datos = JSON.parse(fuente.textContent);

    // Paleta y utilidades comunes (Assets/js/graficas.js)
    const { C, fechaCorta, dinero, ejeDinero } = window.Graficas;

    const graficas = {};
    const crear = (clave, id, config) => {
        const canvas = document.getElementById(id);
        if (canvas) graficas[clave] = new Chart(canvas, config);
    };

    /* ---------- 1. Producción vs ventas ---------- */
    crear('produccion', 'chartProduccion', {
        type: 'bar',
        data: {
            labels: datos.produccion.map(f => fechaCorta(f.fecha)),
            datasets: [
                { label: 'Horneadas', data: datos.produccion.map(f => f.horneadas), backgroundColor: C.accent, maxBarThickness: 18 },
                { label: 'Vendidas',  data: datos.produccion.map(f => f.vendidas),  backgroundColor: C.ink,    maxBarThickness: 18 }
            ]
        },
        options: {
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${c.parsed.y} piezas` } } }
        }
    });

    /* ---------- 2. Ingresos por día (apilado) ---------- */
    crear('ingresos', 'chartIngresos', {
        type: 'bar',
        data: {
            labels: datos.ingresos.map(f => fechaCorta(f.fecha)),
            datasets: [
                { label: 'Ventas (arqueo)', data: datos.ingresos.map(f => f.ventas), backgroundColor: C.ink,    maxBarThickness: 22 },
                { label: 'Abonos',          data: datos.ingresos.map(f => f.abonos), backgroundColor: C.accent, maxBarThickness: 22 }
            ]
        },
        options: {
            scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ...ejeDinero } },
            plugins: { tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${dinero(c.parsed.y)}` } } }
        }
    });

    /* ---------- 3. Producción por producto (dona) ---------- */
    const paletaDona = [C.accent, C.ink, C.soft, C.green, C.blue, C.muted, C.red];
    crear('productos', 'chartProductos', {
        type: 'doughnut',
        data: {
            labels: datos.productos.map(p => p.producto),
            datasets: [{
                data: datos.productos.map(p => p.horneadas),
                backgroundColor: paletaDona,
                borderColor: '#FFFFFF',
                borderWidth: 3
            }]
        },
        options: {
            cutout: '62%',
            plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: (c) => ` ${c.label}: ${c.parsed} piezas` } } }
        }
    });

    /* ---------- 4. Pedidos por estado (dona) ---------- */
    crear('pedidos', 'chartPedidos', {
        type: 'doughnut',
        data: {
            labels: datos.pedidos.map(p => p.estado),
            datasets: [{
                data: datos.pedidos.map(p => p.cantidad),
                backgroundColor: [C.soft, C.accent, C.blue, C.green, C.red],
                borderColor: '#FFFFFF',
                borderWidth: 3
            }]
        },
        options: {
            cutout: '62%',
            plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: (c) => ` ${c.label}: ${c.parsed} pedidos` } } }
        }
    });

    /* ---------- 5. Inventario: existencia vs mínimo (barras horizontales) ---------- */
    crear('inventario', 'chartInventario', {
        type: 'bar',
        data: {
            labels: datos.inventario.map(i => i.insumo),
            datasets: [
                {
                    label: 'Existencia',
                    data: datos.inventario.map(i => i.stock),
                    backgroundColor: datos.inventario.map(i => i.stock <= i.minimo ? C.red : C.green),
                    maxBarThickness: 16
                },
                { label: 'Mínimo', data: datos.inventario.map(i => i.minimo), backgroundColor: C.soft, maxBarThickness: 16 }
            ]
        },
        options: {
            indexAxis: 'y',
            scales: { x: { beginAtZero: true }, y: { grid: { display: false } } },
            plugins: {
                tooltip: {
                    callbacks: { label: (c) => ` ${c.dataset.label}: ${c.parsed.x} ${datos.inventario[c.dataIndex].unidad}` }
                }
            }
        }
    });

    /* ---------- 6. Caja: esperado vs contado (líneas) ---------- */
    crear('caja', 'chartCaja', {
        type: 'line',
        data: {
            labels: datos.caja.map(f => fechaCorta(f.fecha)),
            datasets: [
                {
                    label: 'Esperado', data: datos.caja.map(f => f.esperado),
                    borderColor: C.ink, backgroundColor: C.ink, borderWidth: 2, pointRadius: 3, tension: 0.25
                },
                {
                    label: 'Contado', data: datos.caja.map(f => f.contado),
                    borderColor: C.accent, backgroundColor: 'rgba(192,138,62,0.12)', fill: true,
                    borderWidth: 2, pointRadius: 3, pointStyle: 'rect', tension: 0.25, spanGaps: true
                }
            ]
        },
        options: {
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ...ejeDinero } },
            plugins: { tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${c.parsed.y === null ? '—' : dinero(c.parsed.y)}` } } }
        }
    });

    /* ---------- Exportar PDF ---------- */
    const btnPdf = document.getElementById('btnExportarPdf');
    const formPdf = document.getElementById('formPdf');
    const contenedor = document.getElementById('pdfImagenes');

    if (btnPdf && formPdf) {
        btnPdf.addEventListener('click', () => {
            contenedor.innerHTML = '';

            Object.entries(graficas).forEach(([clave, grafica]) => {
                const campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = `imagenes[${clave}]`;
                campo.value = grafica.toBase64Image('image/png', 1);
                contenedor.appendChild(campo);
            });

            const original = btnPdf.innerHTML;
            btnPdf.disabled = true;
            btnPdf.innerHTML = '<i class="ph ph-circle-notch spin"></i>Generando PDF…';
            formPdf.submit();

            // La descarga no cambia de página: se restaura el botón
            setTimeout(() => { btnPdf.disabled = false; btnPdf.innerHTML = original; }, 4000);
        });
    }
});
