/**
 * Lógica JS del Dashboard, Modal, AJAX de Perfil y Alertas Flotantes - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/dashboard.js
 */

// Modal de confirmación para cerrar sesión
function confirmarSalida() {
    const modal = document.createElement('section');
    modal.setAttribute('class', 'alert_modal');
    document.body.append(modal);

    const alertContainer = document.createElement('div');
    alertContainer.setAttribute('class', 'alert_container');
    modal.appendChild(alertContainer);

    alertContainer.innerHTML = `
        <div class="alert_heading" style="background: #6B4226; padding: 20px; text-align: center;">
            <svg width="70" height="70" viewBox="0 0 24 24" fill="none" stroke="#FEFAE0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                <path stroke-dasharray="60" stroke-dashoffset="60" d="M12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3Z">
                    <animate fill="freeze" attributeName="stroke-dashoffset" dur="0.4s" values="60;0"/>
                </path>
                <path stroke-dasharray="20" stroke-dashoffset="20" d="M9.5 9C9.5 7.61929 10.6193 6.5 12 6.5C13.3807 6.5 14.5 7.61929 14.5 9C14.5 10.1046 13.7842 11.0454 12.7933 11.3644C12.2618 11.5367 12 12.0519 12 12.6033V14">
                    <animate fill="freeze" attributeName="stroke-dashoffset" begin="0.4s" dur="0.3s" values="20;0"/>
                </path>
                <circle cx="12" cy="17.5" r="0.75" fill="#FEFAE0">
                    <animate fill="freeze" attributeName="opacity" begin="0.7s" dur="0.2s" values="0;1"/>
                </circle>
            </svg>
        </div>
        <div class="alert_details" style="padding: 20px; text-align: center; background-color: #FEFAE0;">
            <h2 style="font-size: 20px; font-weight: 700; color: #6B4226; margin-bottom: 8px;">¿Cerrar sesión?</h2>
            <p style="font-size: 14px; color: #525252; margin: 0;">¿Estás seguro de que deseas salir del sistema administrativo?</p>
        </div>
        <div class="alert_footer" style="background-color: #f4f1ea; padding: 12px; display: flex; align-items: center; justify-content: space-evenly; border-top: 1px solid #e0d6c3;">
            <span id="btn-cancelar" style="font-weight: 600; cursor: pointer; padding: 6px 20px; border-radius: 6px; background-color: #D4A373; color: #6B4226; transition: background 0.2s;">Cancelar</span>
            <span id="btn-aceptar" style="font-weight: 600; cursor: pointer; padding: 6px 20px; border-radius: 6px; background-color: #6B4226; color: #FEFAE0; transition: background 0.2s;">Sí, salir</span>
        </div>
    `;

    modal.style.cssText = "position: fixed; top: 0; bottom: 0; left: 0; right: 0; z-index: 999999; background-color: rgba(43, 33, 24, 0.85); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; font-family: 'Poppins', sans-serif;";
    alertContainer.style.cssText = "width: 90%; max-width: 380px; background-color: #FEFAE0; border-radius: 6px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5); overflow: hidden;";

    document.getElementById('btn-cancelar').addEventListener('click', () => modal.remove());
    document.getElementById('btn-aceptar').addEventListener('click', () => {
        window.location.href = '../../../Controllers/LogoutController.php';
    });
}

// Carga asíncrona de perfil.php
async function abrirPerfilModal() {
    const modal = document.getElementById('perfilModal');
    const container = document.getElementById('perfilModalContent');
    
    if (modal && container) {
        modal.style.display = 'flex';
        container.innerHTML = '<div class="text-center p-5 text-white fw-bold">Cargando perfil...</div>';
        
        modal.offsetHeight;
        modal.classList.add('active');
        
        try {
            const response = await fetch('../Public/perfil.php');
            if (response.ok) {
                container.innerHTML = await response.text();
                // Adjuntar el listener de guardado AJAX al formulario cargado dinámicamente
                inicializarFormularioPerfil();
            } else {
                container.innerHTML = '<div class="text-center p-4 text-white">Error al cargar el perfil.</div>';
            }
        } catch (error) {
            console.error('Error:', error);
            container.innerHTML = '<div class="text-center p-4 text-white">Error de conexión.</div>';
        }
    }
}

// Cierre del modal con animación de salida
function cerrarPerfilModal() {
    const modal = document.getElementById('perfilModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            if (!modal.classList.contains('active')) {
                modal.style.display = 'none';
            }
        }, 350);
    }
}

// Alternar despliegue del formulario de edición de perfil
function toggleEdicionPerfil() {
    const editSection = document.getElementById('secEditPerfil');
    const btnText = document.getElementById('btnEditText');
    const btnIcon = document.getElementById('btnEditIcon');

    if (editSection) {
        editSection.classList.toggle('open');
        
        if (editSection.classList.contains('open')) {
            if (btnText) btnText.textContent = ' Ocultar Edición';
            if (btnIcon) btnIcon.className = 'fa-solid fa-xmark me-1';
        } else {
            if (btnText) btnText.textContent = ' Editar Perfil';
            if (btnIcon) btnIcon.className = 'fa-solid fa-pen-to-square me-1';
        }
    }
}

// Alerta flotante superpuesta estilo Toast
function mostrarAlertaFlotante(mensaje) {
    const toast = document.createElement('div');
    toast.className = 'toast-floating-alert';
    toast.innerHTML = `
        <i class="fa-solid fa-circle-check fs-4" style="color: #6B4226;"></i>
        <div>
            <strong class="d-block" style="font-size: 0.95rem;">¡Operación Exitosa!</strong>
            <span style="font-size: 0.85rem; color: #525252;">${mensaje}</span>
        </div>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

// Interceptar el envío del formulario de perfil y procesarlo con AJAX en tiempo real
function inicializarFormularioPerfil() {
    const form = document.getElementById('formEditarPerfil');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando...';

        try {
            const response = await fetch('../../../Controllers/PerfilController.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const result = await response.json();
                
                // 1. Actualizar el nombre en el modal al instante
                const modalNombre = document.getElementById('modalNombreText');
                if (modalNombre) modalNombre.textContent = result.nombre;

                // 2. Actualizar el nombre en la barra del navbar del dashboard
                const navNombre = document.querySelector('.navbar .lh-1 .fw-bold');
                if (navNombre) navNombre.textContent = result.nombre;

                // 3. Previsualizar Foto de Perfil en tiempo real
                const fotoInput = this.querySelector('input[name="foto_archivo"]');
                if (fotoInput && fotoInput.files && fotoInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const avatarImg = document.getElementById('modalAvatarImg');
                        if (avatarImg) avatarImg.src = e.target.result;
                    };
                    reader.readAsDataURL(fotoInput.files[0]);
                }

                // 4. Previsualizar Banner en tiempo real
                const bannerInput = this.querySelector('input[name="banner_archivo"]');
                if (bannerInput && bannerInput.files && bannerInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const bannerDiv = document.getElementById('modalBannerDiv');
                        if (bannerDiv) bannerDiv.style.backgroundImage = `url('${e.target.result}')`;
                    };
                    reader.readAsDataURL(bannerInput.files[0]);
                }

                // Lanzar la alerta flotante y replegar sección de edición
                mostrarAlertaFlotante('El perfil se ha actualizado correctamente.');
                toggleEdicionPerfil();

            } else {
                mostrarAlertaFlotante('Ocurrió un error al guardar los cambios.');
            }
        } catch (error) {
            console.error('Error AJAX:', error);
            mostrarAlertaFlotante('Error de conexión al servidor.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
}

// Event Listeners globales
window.addEventListener('click', (e) => {
    const modal = document.getElementById('perfilModal');
    if (e.target === modal) {
        cerrarPerfilModal();
    }
});