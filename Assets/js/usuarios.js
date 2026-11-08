/**
 * Vista de Usuarios - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/usuarios.js
 *  - Búsqueda y filtro por rol en el directorio
 *  - Desplegar "Restablecer clave" (uno a la vez)
 *  - Mostrar / ocultar contraseñas
 */
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('usrGrid');
    const buscar = document.getElementById('usrBuscar');
    const sinResultados = document.getElementById('usrSinResultados');
    const filtros = document.querySelectorAll('.usr-filter button');
    let rolActivo = 'todos';

    /* ---------- Búsqueda + filtro ---------- */
    function aplicarFiltro() {
        if (!grid) return;
        const texto = (buscar ? buscar.value : '').trim().toLowerCase();
        let visibles = 0;

        grid.querySelectorAll('.usr-person').forEach(function (tarjeta) {
            const coincideRol = rolActivo === 'todos' || tarjeta.dataset.rol === rolActivo;
            const coincideTexto = texto === '' || tarjeta.dataset.texto.includes(texto);
            const mostrar = coincideRol && coincideTexto;
            tarjeta.hidden = !mostrar;
            if (mostrar) visibles++;
        });

        if (sinResultados) sinResultados.classList.toggle('d-none', visibles > 0);
    }

    if (buscar) buscar.addEventListener('input', aplicarFiltro);

    filtros.forEach(function (boton) {
        boton.addEventListener('click', function () {
            filtros.forEach(b => b.classList.remove('active'));
            boton.classList.add('active');
            rolActivo = boton.dataset.rol;
            aplicarFiltro();
        });
    });

    /* ---------- Restablecer clave: abrir uno a la vez ---------- */
    document.querySelectorAll('.usr-reset-toggle').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const tarjeta = boton.closest('.usr-person');
            const abrir = !tarjeta.classList.contains('is-resetting');

            document.querySelectorAll('.usr-person.is-resetting').forEach(function (t) {
                t.classList.remove('is-resetting');
                t.querySelector('.usr-reset-toggle').setAttribute('aria-expanded', 'false');
            });

            if (abrir) {
                tarjeta.classList.add('is-resetting');
                boton.setAttribute('aria-expanded', 'true');
                const campo = tarjeta.querySelector('input[name="nueva_clave"]');
                if (campo) setTimeout(() => campo.focus(), 200);
            }
        });
    });

    /* ---------- Mostrar / ocultar contraseña ---------- */
    document.querySelectorAll('.usr-eye').forEach(function (ojo) {
        ojo.addEventListener('click', function () {
            const campo = ojo.parentElement.querySelector('input');
            const icono = ojo.querySelector('i');
            const oculto = campo.type === 'password';
            campo.type = oculto ? 'text' : 'password';
            icono.className = 'ph-duotone ' + (oculto ? 'ph-eye-slash' : 'ph-eye');
        });
    });
});
