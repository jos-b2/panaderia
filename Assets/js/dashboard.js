/**
 * Menú principal: ventana de Perfil (AJAX) y avisos flotantes. confirmarSalida() está en app.js - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/dashboard.js
 */

// Carga asíncrona de la ventana de perfil (PerfilController)
async function abrirPerfilModal() {
    const modal = document.getElementById('perfilModal');
    const container = document.getElementById('perfilModalContent');
    
    if (modal && container) {
        modal.style.display = 'flex';
        container.innerHTML = '<div class="text-center p-5 text-white fw-bold">Cargando perfil...</div>';
        
        modal.offsetHeight;
        modal.classList.add('active');
        
        try {
            const response = await fetch('/panaderia/Controllers/PerfilController.php');
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
            if (btnText) btnText.textContent = 'Cerrar edición';
            if (btnIcon) btnIcon.className = 'ph-duotone ph-x me-1';
        } else {
            if (btnText) btnText.textContent = 'Editar perfil';
            if (btnIcon) btnIcon.className = 'ph-duotone ph-pencil-simple me-1';
        }
    }
}

// Alerta flotante superpuesta estilo Toast
function mostrarAlertaFlotante(mensaje) {
    const toast = document.createElement('div');
    toast.className = 'toast-floating-alert';
    toast.innerHTML = `
        <i class="ph-duotone ph-check-circle toast-icon"></i>
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

    // Vista previa inmediata al elegir foto o portada (antes de guardar)
    form.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const archivo = this.files && this.files[0];
            const caja = this.closest('.pf-drop');
            if (!archivo) return;

            if (caja) {
                caja.classList.add('has-file');
                const nombre = caja.querySelector('.pf-drop-name');
                if (nombre) nombre.textContent = archivo.name;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                if (this.dataset.preview === 'avatar') {
                    const img = document.getElementById('modalAvatarImg');
                    if (img) {
                        img.src = e.target.result;
                        img.closest('.pf-avatar')?.classList.add('has-photo');
                    }
                } else {
                    const portada = document.getElementById('modalBannerDiv');
                    if (portada) portada.style.backgroundImage = `url('${e.target.result}')`;
                }
            };
            reader.readAsDataURL(archivo);
        });
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph ph-circle-notch spin me-2"></i>Guardando...';

        try {
            const response = await fetch('/panaderia/Controllers/PerfilController.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const result = await response.json();
                
                // 1. Actualizar el nombre en el modal al instante
                const modalNombre = document.getElementById('modalNombreText');
                if (modalNombre) modalNombre.textContent = result.nombre;

                // 2. Actualizar el nombre en la barra superior del menú
                const navNombre = document.querySelector('.menu-user-name');
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