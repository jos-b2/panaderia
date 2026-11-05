/**
 * Lógica para la vista de Recetas - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/recetas.js
 */
document.addEventListener('DOMContentLoaded', function () {
    // Confirmación al desactivar recetas
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('btn-outline-danger')) {
            if (!window.confirm('¿Estás seguro de desactivar esta receta o producto?')) {
                e.preventDefault();
            }
        }
    });
});