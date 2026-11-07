document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.arq-tab-btn');
    const tabContents = document.querySelectorAll('.arq-tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const targetId = button.getAttribute('data-target');
            const targetEl = document.getElementById(targetId);
            if (targetEl) targetEl.classList.add('active');
        });
    });

    const fechaInput = document.getElementById('fechaArqueo');
    if (fechaInput) {
        fechaInput.addEventListener('change', () => {
            window.location.href = `arqueo.php?fecha=${encodeURIComponent(fechaInput.value)}`;
        });
    }

    const rows = document.querySelectorAll('.arq-row');
    const resEsperado = document.getElementById('resEsperado');
    const resDiferencia = document.getElementById('resDiferencia');
    const resDescuadre = document.getElementById('resDescuadre');
    const dineroContado = document.getElementById('dineroContado');

    function recalcular() {
        let totalEsperado = 0;
        let totalDifValor = 0;

        rows.forEach(row => {
            const precio = parseFloat(row.dataset.precio) || 0;
            const ayer = parseInt(row.dataset.ayer, 10) || 0;
            const horn = parseInt(row.dataset.horn, 10) || 0;
            const vend = parseInt(row.querySelector('.js-arq-vend').value, 10) || 0;
            const sobr = parseInt(row.querySelector('.js-arq-sobr').value, 10) || 0;

            const dif = ayer + horn - vend - sobr;
            const val = vend * precio;

            row.querySelector('.js-arq-dif').textContent = dif;
            row.querySelector('.js-arq-val').textContent = `$${val.toFixed(2)}`;

            totalEsperado += val;
            totalDifValor += dif * precio;
        });

        if (resEsperado) resEsperado.textContent = `$${totalEsperado.toFixed(2)}`;
        if (resDiferencia) resDiferencia.textContent = `$${totalDifValor.toFixed(2)}`;

        if (resDescuadre && dineroContado) {
            const contadoVal = dineroContado.value.trim();
            if (contadoVal === '') {
                resDescuadre.textContent = '—';
            } else {
                const desc = (parseFloat(contadoVal) || 0) - totalEsperado;
                resDescuadre.textContent = `$${desc.toFixed(2)}`;
            }
        }
    }

    rows.forEach(row => {
        row.querySelectorAll('.js-arq-vend, .js-arq-sobr').forEach(input => {
            input.addEventListener('input', recalcular);
        });
    });

    if (dineroContado) {
        dineroContado.addEventListener('input', recalcular);
    }

    recalcular();
});