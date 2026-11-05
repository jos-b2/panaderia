/**
 * Lógica específica para la vista de Insumos - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/insumos.js
 */

document.addEventListener('DOMContentLoaded', function () {
    // Confirmación automática para botones con el atributo data-confirm
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('ins-btn-danger')) {
            if (!window.confirm('¿Estás seguro de realizar esta acción en el insumo?')) {
                e.preventDefault();
            }
        }
    });
});