const New = {
    confirm: function ({ title = '¿Confirmar acción?', content = '', confirmText = 'Confirmar', onConfirm }) {
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;inset:0;background:rgba(28,17,10,0.65);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:9999;padding:16px;';

        const card = document.createElement('div');
        card.style.cssText = 'width:100%;max-width:400px;background:#FFFDF9;border-radius:14px;overflow:hidden;box-shadow:0 20px 45px rgba(0,0,0,0.35);font-family:Montserrat,sans-serif;text-align:center;';

        card.innerHTML = `
            <div style="background:#6B4226;padding:26px 20px;display:flex;justify-content:center;align-items:center;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#FEFAE0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div style="background:#FFF8E7;padding:24px 24px 20px;">
                <h2 style="margin:0 0 10px;font-size:21px;font-weight:700;color:#6B4226;">${title}</h2>
                <p style="margin:0;font-size:14.5px;color:#4A4A4A;line-height:1.45;">${content}</p>
            </div>
            <div style="background:#F7F1E8;padding:14px 20px;display:flex;gap:12px;justify-content:center;border-top:1px solid #EADFCE;">
                <button type="button" class="btn-cancel" style="background:#D4A373;color:#4A2F20;border:none;border-radius:8px;padding:9px 22px;font-size:14px;font-weight:700;cursor:pointer;">Cancelar</button>
                <button type="button" class="btn-ok" style="background:#6B4226;color:#FEFAE0;border:none;border-radius:8px;padding:9px 22px;font-size:14px;font-weight:700;cursor:pointer;">${confirmText}</button>
            </div>
        `;

        modal.appendChild(card);
        document.body.appendChild(modal);

        card.querySelector('.btn-cancel').addEventListener('click', () => modal.remove());
        card.querySelector('.btn-ok').addEventListener('click', () => {
            modal.remove();
            if (typeof onConfirm === 'function') onConfirm();
        });
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const btnAdd = document.getElementById('btnAddIngrediente');
    const contenedor = document.getElementById('contenedorIngredientes');
    const tpl = document.getElementById('tplIngrediente');

    if (btnAdd && contenedor && tpl) {
        btnAdd.addEventListener('click', function () {
            const clone = tpl.content.cloneNode(true);
            contenedor.appendChild(clone);
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('js-remove-ing')) {
            const row = e.target.closest('.rec-ing-row');
            if (row) row.remove();
        }

        const toggleBtn = e.target.closest('.js-toggle-receta');
        if (toggleBtn) {
            e.preventDefault();
            const href = toggleBtn.getAttribute('href');
            const esActivar = toggleBtn.textContent.trim().toLowerCase() === 'activar';
            New.confirm({
                title: esActivar ? '¿Activar receta?' : '¿Desactivar receta?',
                content: esActivar
                    ? '¿Estás seguro de que deseas activar esta receta o producto?'
                    : '¿Estás seguro de que deseas desactivar esta receta o producto?',
                confirmText: esActivar ? 'Sí, activar' : 'Sí, desactivar',
                onConfirm: () => {
                    window.location.href = href;
                }
            });
        }
    });
});