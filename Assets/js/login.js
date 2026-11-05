/**
 * Lógica JS y Sistema de Alertas (Paleta estricta de la panadería) - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/login.js
 */

const New = {
    status: 'success',
    title: '',
    content: '',
    alert: function ({ status, title, content, confirmbtn = true }) {
        var modal = document.createElement('section');
        modal.setAttribute('class', 'alert_modal');
        document.body.append(modal);

        var alert = document.createElement('div');
        alert.setAttribute('class', 'alert_container');
        modal.appendChild(alert);

        title = title || this.title;
        status = status || this.status;
        content = content || this.content;

        alert.innerHTML = `
            <div class="alert_heading"></div>
            <div class="alert_details">
                <h2>${title}</h2>
                <p>${content}</p>
            </div>
            <div class="alert_footer"></div>
        `;

        var alert_heading = alert.querySelector('.alert_heading');
        var alert_footer = alert.querySelector('.alert_footer');

        // Asignación estricta utilizando únicamente la paleta corporativa autorizada
        if (status === '' || status === 'success') {
            alert_heading.innerHTML = `
                <svg width="70" height="70" viewBox="0 0 24 24"><g fill="none" stroke="#FEFAE0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path stroke-dasharray="60" stroke-dashoffset="60" d="M3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.4s" values="60;0"/></path><path stroke-dasharray="14" stroke-dashoffset="14" d="M8 12L11 15L16 10"><animate fill="freeze" attributeName="stroke-dashoffset" begin="0.4s" dur="0.2s" values="14;0"/></path></g></svg>
            `;
            alert_footer.innerHTML = `<span class="close" title="Ok">Aceptar</span>`;
            // Éxito: Fondo Color Trigo (#D4A373) y texto/elementos en Crema/Blanco
            alert_heading.style.background = '#D4A373';
            alert.querySelector('.alert_details > h2').style.color = '#6B4226'; // Título en Corteza

        } else {
            alert_heading.innerHTML = `
                <svg width="70" height="70" viewBox="0 0 24 24"><g fill="none" stroke="#FEFAE0" stroke-linecap="round" stroke-width="2"><path stroke-dasharray="60" stroke-dashoffset="60" d="M12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.4s" values="60;0"/></path><path stroke-dasharray="8" stroke-dashoffset="8" d="M12 12L16 16M12 12L8 8M12 12L8 16M12 12L16 8"><animate fill="freeze" attributeName="stroke-dashoffset" begin="0.4s" dur="0.2s" values="8;0"/></path></g></svg>
            `;
            alert_footer.innerHTML = `<span class="close" title="Ok">Entendido</span>`;
            // Error: Fondo Color Corteza (#6B4226)
            alert_heading.style.background = '#6B4226';
            alert.querySelector('.alert_details > h2').style.color = '#6B4226'; // Título en Corteza
        }

        // Evento para cerrar la alerta y redirigir si el login fue exitoso
        alert_footer.querySelector('.close').addEventListener('click', function () {
            modal.remove();
            if (window.redirectAfterAlert) {
                window.location.href = window.redirectAfterAlert;
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.querySelector('.form-box.login form');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const userInput = loginForm.querySelector('input[type="text"]').value;
            const passwordInput = loginForm.querySelector('input[type="password"]').value;
            const submitBtn = loginForm.querySelector('.btn');

            // Feedback visual en el botón café oscuro
            const btnOriginalText = submitBtn.innerText;
            submitBtn.innerText = 'Verificando...';
            submitBtn.disabled = true;

            const formData = new FormData();
            formData.append('usuario', userInput);
            formData.append('clave', passwordInput);

            try {
                // Petición al controlador ubicado en la raíz
                const response = await fetch('../../../Controllers/AuthController.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    window.redirectAfterAlert = data.redirect;
                    New.alert({
                        status: 'success',
                        title: '¡Bienvenido!',
                        content: data.message
                    });
                } else {
                    New.alert({
                        status: 'error',
                        title: 'Acceso Denegado',
                        content: data.message
                    });
                    submitBtn.innerText = btnOriginalText;
                    submitBtn.disabled = false;
                }

            } catch (error) {
                console.error('Error:', error);
                New.alert({
                    status: 'error',
                    title: 'Error de Conexión',
                    content: 'Ocurrió un problema al conectar con el servidor.'
                });
                submitBtn.innerText = btnOriginalText;
                submitBtn.disabled = false;
            }
        });
    }
});