/**
 * Vista de Producción - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/produccion.js
 *  - Calcula las piezas y el consumo de insumos según la receta elegida
 *  - Muestra si alcanza el stock (barra por insumo y aviso general)
 *  - Confirma antes de anular una tanda
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formProduccion');
    if (!form) return;

    const productos   = JSON.parse(form.getAttribute('data-productos') || '{}');
    const inputBase   = document.getElementById('inputCantidadBase');
    const inputPiezas = document.getElementById('inputPiezas');
    const lblBase     = document.getElementById('lblBaseInsumo');
    const unidadBase  = document.getElementById('unidadBase');
    const hintBase    = document.getElementById('hintBase');
    const lista       = document.getElementById('listaConsumo');
    const estado      = document.getElementById('estadoStock');
    const forzarCaja  = document.getElementById('forzarCaja');
    const forzar      = document.getElementById('forzarStock');
    const btnTexto    = document.getElementById('btnRegistrarTexto');

    // Escapa texto antes de insertarlo como HTML
    const esc = (t) => String(t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = (n) => (Math.round(n * 100) / 100).toLocaleString('es', { maximumFractionDigits: 2 });

    function productoActual() {
        const marcado = form.querySelector('input[name="producto_id"]:checked');
        return marcado ? productos[marcado.value] : null;
    }

    function actualizarBoton() {
        if (!btnTexto) return;
        const piezas = parseInt(inputPiezas.value, 10) || 0;
        btnTexto.textContent = piezas > 0 ? `Registrar tanda de ${piezas} piezas` : 'Registrar tanda';
    }

    function actualizar(recalcularPiezas) {
        const prod = productoActual();
        if (!prod || !lista) return;

        // Etiqueta y disponibilidad de la materia base
        lblBase.textContent = prod.base.nombre;
        unidadBase.textContent = prod.base.unidad;
        hintBase.textContent = `Disponible: ${fmt(prod.base.stock)} ${prod.base.unidad}`;

        const cantBase = parseFloat(inputBase.value) || 0;
        if (recalcularPiezas) {
            inputPiezas.value = cantBase > 0 ? Math.round(cantBase * prod.rendimiento) : '';
        }
        actualizarBoton();

        if (cantBase <= 0) {
            lista.innerHTML = '<div class="empty-state py-3"><i class="ph-duotone ph-scales"></i>Ingresa la cantidad de materia base para ver el consumo.</div>';
            estado.className = 'prod-status';
            estado.textContent = '';
            forzarCaja.classList.remove('is-visible');
            forzar.checked = false;
            return;
        }

        const filas = [
            { nombre: prod.base.nombre, unidad: prod.base.unidad, usar: cantBase, hay: prod.base.stock, base: true },
            ...prod.items.map(it => ({ nombre: it.nombre, unidad: it.unidad, usar: cantBase * it.cantidad, hay: it.stock, base: false }))
        ];

        let faltantes = 0;

        lista.innerHTML = filas.map(f => {
            const falta = f.usar > f.hay + 1e-9;
            if (falta) faltantes++;
            const pct = f.hay > 0 ? Math.min(100, (f.usar / f.hay) * 100) : 100;
            const nivel = falta ? 'is-out' : (pct >= 75 ? 'is-low' : 'is-ok');

            return `
                <div class="prod-row ${nivel}">
                    <div class="prod-row-name">
                        ${esc(f.nombre)}${f.base ? '<em>base</em>' : ''}
                    </div>
                    <div class="prod-row-bar"><i style="width:${pct.toFixed(0)}%"></i></div>
                    <div class="prod-row-qty">
                        <strong>${fmt(f.usar)}</strong> / ${fmt(f.hay)} ${esc(f.unidad)}
                    </div>
                    <span class="prod-row-tag">${falta ? `Faltan ${fmt(f.usar - f.hay)}` : 'Suficiente'}</span>
                </div>`;
        }).join('');

        if (faltantes > 0) {
            estado.className = 'prod-status is-out';
            estado.innerHTML = `<i class="ph-duotone ph-warning"></i>${faltantes === 1 ? 'Falta 1 insumo' : `Faltan ${faltantes} insumos`}`;
            forzarCaja.classList.add('is-visible');
        } else {
            estado.className = 'prod-status is-ok';
            estado.innerHTML = '<i class="ph-duotone ph-seal-check"></i>Stock suficiente';
            forzarCaja.classList.remove('is-visible');
            forzar.checked = false;
        }
    }

    form.querySelectorAll('input[name="producto_id"]').forEach(r => r.addEventListener('change', () => actualizar(true)));
    if (inputBase) inputBase.addEventListener('input', () => actualizar(true));
    if (inputPiezas) inputPiezas.addEventListener('input', actualizarBoton);

    actualizar(false);

    /* ---------- Confirmar antes de anular una tanda ---------- */
    document.querySelectorAll('.js-confirm-anular').forEach(boton => {
        boton.addEventListener('click', (e) => {
            e.preventDefault();
            const formAnular = boton.closest('form');
            const confirmar = () => {
                document.body.classList.add('page-leave');
                formAnular.submit();
            };

            if (typeof window.appConfirm === 'function') {
                window.appConfirm({
                    icono: 'ph-arrow-counter-clockwise',
                    titulo: '¿Anular esta tanda?',
                    texto: 'Los insumos que consumió regresarán al inventario. La tanda quedará marcada como anulada.',
                    aceptar: 'Sí, anular',
                    peligro: true,
                    alAceptar: confirmar
                });
            } else if (window.confirm('¿Anular esta tanda y restaurar los insumos al inventario?')) {
                confirmar();
            }
        });
    });
});
