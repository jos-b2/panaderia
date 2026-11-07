document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-auto-submit').forEach(function (select) {
        select.addEventListener('change', function () {
            if (this.form) {
                this.form.submit();
            }
        });
    });

    const cantidadPanes = document.getElementById('cantidadPanes');
    const totalPedido = document.getElementById('totalPedido');

    if (cantidadPanes && totalPedido) {
        cantidadPanes.addEventListener('input', function () {
            const panes = parseInt(this.value, 10) || 0;
            const total = (panes / 4) * 0.25;
            totalPedido.value = total.toFixed(2);
        });
    }
});