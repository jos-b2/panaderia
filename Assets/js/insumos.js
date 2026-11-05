document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.ins-tab-btn');
    const tabContents = document.querySelectorAll('.ins-tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const targetId = button.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });

    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('ins-btn-danger')) {
            if (!window.confirm('¿Estás seguro de realizar esta acción en el insumo?')) {
                e.preventDefault();
            }
        }
    });
});