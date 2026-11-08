/**
 * Vista de Pedidos - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/pedidos.js
 *  - Filtros y cambio de estado que se envían solos al cambiar
 *  - Líneas de producto del nuevo pedido: agregar, quitar y calcular el total
 *    (cantidad × precio). El total se puede corregir a mano.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-auto-submit').forEach(function (select) {
        select.addEventListener('change', function () {
            if (this.form) this.form.submit();
        });
    });

    /* ---------- Cambio de estado: entregar = registrar la venta ---------- */
    document.querySelectorAll('.js-estado').forEach(function (select) {
        select.addEventListener('change', function () {
            const form = this.form;
            const actual = this.dataset.actual;
            const saldo = parseFloat(this.dataset.saldo) || 0;
            const enviar = (cobrar) => {
                form.querySelector('.js-cobrar').value = cobrar ? '1' : '0';
                document.body.classList.add('page-leave');
                form.submit();
            };
            const deshacer = () => { this.value = actual; };

            if (this.value !== 'entregado' || typeof window.appConfirm !== 'function') {
                enviar(false);
                return;
            }

            if (saldo > 0) {
                // Con saldo pendiente: ofrecer cobrarlo al entregar
                window.appConfirm({
                    icono: 'ph-hand-coins',
                    titulo: `¿Cobrar $${saldo.toFixed(2)} al entregar?`,
                    texto: `${this.dataset.cliente} tiene un saldo pendiente de $${saldo.toFixed(2)}. Se registrará la venta y el cobro.`,
                    aceptar: 'Sí, cobrar y entregar',
                    cancelar: 'Entregar sin cobrar',
                    alAceptar: () => enviar(true)
                });
                // "Entregar sin cobrar" (o cerrar) entrega igual, pero deja el saldo pendiente
                setTimeout(() => {
                    const btn = document.querySelector('.app-modal [data-accion="cancelar"]');
                    if (btn) btn.addEventListener('click', () => enviar(false), { once: true });
                    const modal = document.querySelector('.app-modal');
                    if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) deshacer(); });
                }, 0);
            } else {
                window.appConfirm({
                    icono: 'ph-receipt',
                    titulo: '¿Marcar como entregado?',
                    texto: 'Se registrará como venta de hoy y las piezas saldrán del pan disponible.',
                    aceptar: 'Sí, entregar',
                    alAceptar: () => enviar(false)
                });
                setTimeout(() => {
                    const btn = document.querySelector('.app-modal [data-accion="cancelar"]');
                    if (btn) btn.addEventListener('click', deshacer, { once: true });
                    const modal = document.querySelector('.app-modal');
                    if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) deshacer(); });
                }, 0);
            }
        });
    });

    const contenedor = document.querySelector('.ped-items');
    const lista = document.getElementById('listaItems');
    const plantilla = document.getElementById('tplItem');
    const total = document.getElementById('totalPedido');
    if (!contenedor || !lista || !plantilla || !total) return;

    const precios = JSON.parse(contenedor.dataset.precios || '{}');
    const dinero = (v) => '$' + v.toFixed(2);
    let totalEditado = false;   // si la persona cambia el total a mano, ya no se recalcula

    function recalcular() {
        let suma = 0;
        lista.querySelectorAll('.ped-item').forEach(fila => {
            const precio = parseFloat(precios[fila.querySelector('select').value] || 0);
            const cant = parseInt(fila.querySelector('input').value, 10) || 0;
            const sub = precio * cant;
            fila.querySelector('.ped-item-subtotal').textContent = dinero(sub);
            suma += sub;
        });
        if (!totalEditado) total.value = suma.toFixed(2);

        // Con una sola línea no se puede quitar
        const filas = lista.querySelectorAll('.ped-item');
        filas.forEach(f => { f.querySelector('.js-quitar-item').disabled = filas.length === 1; });
    }

    document.getElementById('btnAgregarItem').addEventListener('click', () => {
        lista.appendChild(plantilla.content.cloneNode(true));
        recalcular();
        lista.lastElementChild.querySelector('input').focus();
    });

    lista.addEventListener('click', (e) => {
        const quitar = e.target.closest('.js-quitar-item');
        if (quitar && !quitar.disabled) {
            quitar.closest('.ped-item').remove();
            recalcular();
        }
    });

    lista.addEventListener('input', recalcular);
    lista.addEventListener('change', recalcular);
    total.addEventListener('input', () => { totalEditado = true; });

    recalcular();
});
