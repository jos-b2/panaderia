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

            // La foto de perfil pasa primero por el recorte (como WhatsApp)
            if (this.dataset.preview === 'avatar' && typeof Cropper !== 'undefined') {
                abrirRecorte(archivo, (recortado) => {
                    // Reemplaza el archivo elegido por el recorte
                    const dt = new DataTransfer();
                    dt.items.add(recortado);
                    this.files = dt.files;
                    mostrarArchivo(this, recortado, caja);
                }, () => { this.value = ''; });
                return;
            }

            mostrarArchivo(this, archivo, caja);
        });
    });

    /** Marca la caja como lista y muestra la vista previa en la tarjeta. */
    function mostrarArchivo(input, archivo, caja) {
        if (caja) {
            caja.classList.add('has-file');
            const nombre = caja.querySelector('.pf-drop-name');
            if (nombre) nombre.textContent = input.dataset.preview === 'avatar' ? 'Foto recortada' : archivo.name;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            if (input.dataset.preview === 'avatar') {
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
    }

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
                        // Foto del botón de usuario en la barra superior
                        const fotoBarra = document.querySelector('.menu-user-foto');
                        if (fotoBarra) fotoBarra.innerHTML = `<img src="${e.target.result}" alt="">`;
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
/* ==========================================================================
   Recorte de la foto de perfil (estilo WhatsApp) con Cropper.js
   Marco cuadrado fijo: se arrastra la imagen y se hace zoom (rueda, pellizco
   o la barra) y se puede girar. Devuelve un JPG de 600×600.
   ========================================================================== */
function abrirRecorte(archivo, alListo, alCancelar) {
    const url = URL.createObjectURL(archivo);
    const capa = document.createElement('div');
    capa.className = 'recorte-capa';
    capa.innerHTML = `
        <div class="recorte-caja" role="dialog" aria-modal="true" aria-label="Ajustar foto de perfil">
            <header class="recorte-head">
                <button type="button" class="recorte-x" data-r="cancelar" aria-label="Cancelar"><i class="ph ph-x"></i></button>
                <div>
                    <strong>Ajusta tu foto</strong>
                    <small>Arrastra para moverla y usa la rueda o la barra para acercar</small>
                </div>
            </header>
            <div class="recorte-area"><img alt=""></div>
            <div class="recorte-controles">
                <button type="button" class="recorte-btn" data-r="girar-izq" title="Girar a la izquierda"><i class="ph-duotone ph-arrow-counter-clockwise"></i></button>
                <i class="ph ph-magnifying-glass-minus"></i>
                <input type="range" min="0" max="1" step="0.001" value="0" class="recorte-zoom" aria-label="Zoom">
                <i class="ph ph-magnifying-glass-plus"></i>
                <button type="button" class="recorte-btn" data-r="girar-der" title="Girar a la derecha"><i class="ph-duotone ph-arrow-clockwise"></i></button>
            </div>
            <footer class="recorte-pie">
                <button type="button" class="btn btn-outline-secondary" data-r="cancelar">Cancelar</button>
                <button type="button" class="btn btn-brand" data-r="listo"><i class="ph ph-check"></i>Listo</button>
            </footer>
        </div>`;
    document.body.appendChild(capa);
    requestAnimationFrame(() => capa.classList.add('is-open'));

    const img = capa.querySelector('.recorte-area img');
    const zoom = capa.querySelector('.recorte-zoom');
    let zoomMin = 0, zoomMax = 1, ajustandoBarra = false;

    img.src = url;
    const cropper = new Cropper(img, {
        aspectRatio: 1,
        viewMode: 1,               // la imagen siempre cubre el marco
        dragMode: 'move',          // se arrastra la imagen, no el marco
        autoCropArea: 1,
        cropBoxMovable: false,
        cropBoxResizable: false,
        toggleDragModeOnDblclick: false,
        guides: false,
        center: false,
        highlight: false,
        background: false,
        responsive: true,
        ready() {
            // Rango de la barra: desde "llenar el marco" hasta 4 veces más cerca
            const datos = cropper.getImageData();
            zoomMin = datos.width / datos.naturalWidth;
            zoomMax = zoomMin * 4;
            zoom.min = zoomMin; zoom.max = zoomMax; zoom.step = (zoomMax - zoomMin) / 200;
            zoom.value = zoomMin;
        },
        zoom(e) {
            // No alejar más allá de llenar el marco
            if (e.detail.ratio < zoomMin) { e.preventDefault(); cropper.zoomTo(zoomMin); }
            if (e.detail.ratio > zoomMax) { e.preventDefault(); cropper.zoomTo(zoomMax); }
            if (!ajustandoBarra) zoom.value = Math.min(zoomMax, Math.max(zoomMin, e.detail.ratio));
        }
    });

    zoom.addEventListener('input', () => {
        ajustandoBarra = true;
        cropper.zoomTo(parseFloat(zoom.value));
        ajustandoBarra = false;
    });

    function cerrar() {
        capa.classList.remove('is-open');
        document.removeEventListener('keydown', teclado);
        setTimeout(() => { cropper.destroy(); capa.remove(); URL.revokeObjectURL(url); }, 250);
    }

    function teclado(e) {
        if (e.key === 'Escape') { cerrar(); alCancelar(); }
    }
    document.addEventListener('keydown', teclado);

    capa.addEventListener('click', (e) => {
        const accion = e.target.closest('[data-r]')?.dataset.r;
        if (accion === 'cancelar') { cerrar(); alCancelar(); }
        if (accion === 'girar-izq') cropper.rotate(-90);
        if (accion === 'girar-der') cropper.rotate(90);
        if (accion === 'listo') {
            const canvas = cropper.getCroppedCanvas({ width: 600, height: 600, fillColor: '#FFFFFF', imageSmoothingQuality: 'high' });
            canvas.toBlob((blob) => {
                cerrar();
                alListo(new File([blob], 'perfil.jpg', { type: 'image/jpeg' }));
            }, 'image/jpeg', 0.9);
        }
    });
}
