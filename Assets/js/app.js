/**
 * Comportamiento común a todas las páginas - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/app.js  (lo carga footer.php)
 *
 *  - confirmarSalida(): botón "Cerrar sesión" (menú lateral y Menú principal)
 *  - appConfirm(): ventana de confirmación con el estilo del sistema
 *  - Transiciones entre vistas: la página se desvanece al salir; la nueva entra
 *    con animación (las animaciones de entrada están en custom-theme.css)
 */
(function () {
    const sinAnimacion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Navegar con transición de salida ---------- */
    function irA(url) {
        if (sinAnimacion) {
            window.location.href = url;
            return;
        }
        document.body.classList.add('page-leave');
        setTimeout(() => { window.location.href = url; }, 240);
    }
    window.irConTransicion = irA;

    /* ---------- Ventana de confirmación ---------- */
    function appConfirm({ icono = 'ph-question', titulo, texto, aceptar = 'Aceptar', cancelar = 'Cancelar', peligro = false, alAceptar }) {
        const modal = document.createElement('div');
        modal.className = 'app-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.innerHTML = `
            <div class="app-dialog">
                <div class="app-dialog-icon ${peligro ? 'is-danger' : ''}">
                    <i class="ph-duotone ${icono}"></i>
                </div>
                <h3 class="app-dialog-title"></h3>
                <p class="app-dialog-text"></p>
                <div class="app-dialog-actions">
                    <button type="button" class="btn btn-outline-secondary" data-accion="cancelar"></button>
                    <button type="button" class="btn ${peligro ? 'btn-danger-solid' : 'btn-brand'}" data-accion="aceptar"></button>
                </div>
            </div>`;

        // Texto con textContent (sin interpretar HTML)
        modal.querySelector('.app-dialog-title').textContent = titulo;
        modal.querySelector('.app-dialog-text').textContent = texto;
        modal.querySelector('[data-accion="cancelar"]').textContent = cancelar;
        modal.querySelector('[data-accion="aceptar"]').textContent = aceptar;

        function cerrar() {
            modal.classList.remove('is-open');
            document.removeEventListener('keydown', teclado);
            setTimeout(() => modal.remove(), 250);
        }

        function teclado(e) {
            if (e.key === 'Escape') cerrar();
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-accion="cancelar"]')) cerrar();
            if (e.target.closest('[data-accion="aceptar"]')) {
                cerrar();
                if (typeof alAceptar === 'function') alAceptar();
            }
        });

        document.addEventListener('keydown', teclado);
        document.body.appendChild(modal);
        requestAnimationFrame(() => modal.classList.add('is-open'));
        modal.querySelector('[data-accion="aceptar"]').focus();
    }
    window.appConfirm = appConfirm;

    /* ---------- Cerrar sesión (disponible en todas las páginas) ---------- */
    window.confirmarSalida = function () {
        appConfirm({
            icono: 'ph-sign-out',
            titulo: '¿Cerrar sesión?',
            texto: 'Saldrás del sistema de la Panadería Don Leo. Tendrás que volver a ingresar tu usuario y contraseña.',
            aceptar: 'Sí, cerrar sesión',
            peligro: true,
            alAceptar: () => irA('/panaderia/Controllers/LogoutController.php')
        });
    };

    /* ---------- Transición al hacer clic en enlaces internos ----------
       Se escucha en "window" para correr DESPUÉS de los demás scripts: si alguno
       canceló el clic (por ejemplo, una confirmación), no se hace nada. */
    window.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        const enlace = e.target.closest('a[href]');
        if (!enlace || enlace.hasAttribute('download')) return;
        if (enlace.target && enlace.target !== '_self') return;

        const url = new URL(enlace.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (url.search.includes('descargar=') || url.search.includes('exportar=')) return;   // descargas (respaldos, reportes)
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;

        e.preventDefault();
        irA(url.href);
    });

    /* Al enviar un formulario (que no fue cancelado), también se desvanece */
    window.addEventListener('submit', (e) => {
        if (e.defaultPrevented || sinAnimacion || e.target.hasAttribute('data-no-transicion')) return;
        document.body.classList.add('page-leave');
    });

    /* Al volver con el botón "Atrás", quitar el desvanecido */
    window.addEventListener('pageshow', () => document.body.classList.remove('page-leave'));
})();
