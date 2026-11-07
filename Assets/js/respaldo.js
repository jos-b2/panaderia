document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formRespaldo');
    const btn = document.getElementById('btnCrearRespaldo');

    if (form && btn) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.textContent = 'Generando respaldo...';
        });
    }
});