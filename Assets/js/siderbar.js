/**
 * Lógica estándar para el menú móvil del Sidebar
 * Ubicación: /panaderia/Assets/js/siderbar.js
 */

document.addEventListener('DOMContentLoaded', function () {
    const side = document.querySelector('.side') || document.getElementById('side');
    const scrim = document.getElementById('scrim');
    const burger = document.getElementById('burger');

    function toggleSidebar(open) {
        if (side) side.classList.toggle('open', open);
        if (scrim) scrim.classList.toggle('show', open);
    }

    if (burger) {
        burger.addEventListener('click', function () {
            const isOpen = side && side.classList.contains('open');
            toggleSidebar(!isOpen);
        });
    }

    if (scrim) {
        scrim.addEventListener('click', function () {
            toggleSidebar(false);
        });
    }
});