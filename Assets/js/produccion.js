/**
 * Vista de Producción - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/produccion.js
 *  - Calcula las piezas y el consumo de insumos según la receta elegida
 *  - Muestra si alcanza el stock (barra por insumo y aviso general)
 *  - Botón "!", y confirmaciones de "Empezar a amasar" y "Eliminar tanda"
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

    const editando = form.classList.contains('is-editing');

    function actualizarBoton() {
        if (!btnTexto || editando) return;   // al modificar, el botón dice "Guardar cambios"
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

});

/* ==========================================================================
   Botón "!" (¿Cómo funciona?) y confirmaciones de las acciones de las tandas
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    /** Pide confirmación con la ventana del sistema (o la del navegador si no está) y ejecuta. */
    function confirmar(opciones, alAceptar) {
        if (typeof window.appConfirm === 'function') {
            window.appConfirm({ ...opciones, alAceptar });
        } else if (window.confirm(opciones.titulo + '\n\n' + opciones.texto)) {
            alAceptar();
        }
    }

    const enviar = (form) => { document.body.classList.add('page-leave'); form.submit(); };

    /* ---------- "!" abre y cierra la explicación ---------- */
    const btnInfo = document.getElementById('btnInfo');
    const panelInfo = document.getElementById('panelInfo');
    if (btnInfo && panelInfo) {
        btnInfo.addEventListener('click', () => {
            const abrir = panelInfo.hidden;
            panelInfo.hidden = !abrir;
            btnInfo.setAttribute('aria-expanded', String(abrir));
            btnInfo.classList.toggle('is-open', abrir);
        });
    }

    /* ---------- Empezar a amasar: avisa que se descuenta el inventario ---------- */
    document.querySelectorAll('.js-amasar').forEach(boton => {
        boton.addEventListener('click', (e) => {
            e.preventDefault();
            confirmar({
                icono: 'ph-hand-grabbing',
                titulo: '¿Empezar a amasar?',
                texto: `Se descontarán del inventario los insumos de ${boton.dataset.piezas} piezas de ${boton.dataset.producto}. Después ya no se podrá modificar la tanda.`,
                aceptar: 'Sí, empezar a amasar'
            }, () => enviar(boton.closest('form')));
        });
    });

    /* ---------- Eliminar una tanda registrada ---------- */
    const btnEliminar = document.getElementById('btnEliminarTanda');
    const formEliminar = document.getElementById('formEliminarTanda');
    if (btnEliminar && formEliminar) {
        btnEliminar.addEventListener('click', () => {
            confirmar({
                icono: 'ph-trash',
                titulo: '¿Eliminar esta tanda?',
                texto: 'Todavía no se había amasado, así que el inventario no cambia.',
                aceptar: 'Sí, eliminar',
                peligro: true
            }, () => {
                document.getElementById('eliminarTandaId').value = btnEliminar.dataset.id;
                enviar(formEliminar);
            });
        });
    }
});
