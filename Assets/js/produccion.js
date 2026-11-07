document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formProduccion');
    const prodSelect = document.getElementById('prodSelect');
    const lblBase = document.getElementById('lblBaseInsumo');
    const inputBase = document.getElementById('inputCantidadBase');
    const inputPiezas = document.getElementById('inputPiezas');
    const tbodyConsumo = document.getElementById('tbodyConsumo');

    if (!form || !prodSelect) return;

    const productosData = JSON.parse(form.getAttribute('data-productos') || '{}');

    function actualizarVista(recalcularPiezas) {
        const pid = prodSelect.value;
        const prod = productosData[pid];

        if (!prod) {
            tbodyConsumo.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3" style="font-size: 12.5px;">Sin datos de receta.</td></tr>';
            return;
        }

        lblBase.textContent = `${prod.base.nombre} (${prod.base.unidad})`;

        const cantBase = parseFloat(inputBase.value) || 0;

        if (recalcularPiezas && cantBase > 0) {
            inputPiezas.value = Math.round(cantBase * prod.rendimiento);
        }

        const filas = [
            {
                nombre: prod.base.nombre,
                unidad: prod.base.unidad,
                usar: cantBase,
                hay: prod.base.stock
            },
            ...prod.items.map(item => ({
                nombre: item.nombre,
                unidad: item.unidad,
                usar: cantBase * item.cantidad,
                hay: item.stock
            }))
        ];

        tbodyConsumo.innerHTML = filas.map(f => {
            const falta = f.usar > f.hay;
            const colorUsar = falta ? 'text-danger fw-bold' : '';
            return `
                <tr>
                    <td class="ps-3 py-1"><strong style="color: #4A2F20; font-size: 12.5px;">${f.nombre}</strong></td>
                    <td class="py-1 ${colorUsar}" style="font-size: 12.5px;">${f.usar.toFixed(2)} ${f.unidad}</td>
                    <td class="py-1" style="color: #4A2F20; font-size: 12.5px;">${f.hay.toFixed(2)} ${f.unidad}</td>
                </tr>
            `;
        }).join('');
    }

    prodSelect.addEventListener('change', () => actualizarVista(true));
    inputBase.addEventListener('input', () => actualizarVista(true));

    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('js-confirm-anular')) {
            if (!window.confirm('¿Anular esta tanda y restaurar los insumos al inventario?')) {
                e.preventDefault();
            }
        }
    });

    actualizarVista(false);
});