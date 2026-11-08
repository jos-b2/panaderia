/**
 * Tablas adaptables - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/tablas.js
 *
 * En pantallas pequeñas, custom-theme.css muestra cada fila como una tarjeta.
 * Este script copia el título de cada columna (<th>) en sus celdas (data-label)
 * para que la tarjeta muestre "Columna: valor". También se aplica a filas que
 * se generan después con JavaScript (por ejemplo, el consumo en Producción).
 */
(function () {
    function etiquetar(tabla) {
        const titulos = Array.from(tabla.querySelectorAll('thead th')).map(th => th.textContent.trim());
        if (!titulos.length) return;

        tabla.querySelectorAll('tbody tr').forEach(fila => {
            let col = 0;
            Array.from(fila.children).forEach(celda => {
                const span = parseInt(celda.getAttribute('colspan') || '1', 10);
                if (span > 1) {
                    celda.classList.add('td-full');
                } else if (titulos[col]) {
                    celda.setAttribute('data-label', titulos[col]);
                }
                col += span;
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.main .table').forEach(tabla => {
            if (!tabla.querySelector('thead th')) return;
            tabla.classList.add('table-stack');
            etiquetar(tabla);

            const cuerpo = tabla.querySelector('tbody');
            if (cuerpo) {
                new MutationObserver(() => etiquetar(tabla)).observe(cuerpo, { childList: true });
            }
        });
    });
})();
