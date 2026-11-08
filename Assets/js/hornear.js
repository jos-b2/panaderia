/**
 * Vista Hornear - Panadería Don Leo
 * Ubicación: /panaderia/Assets/js/hornear.js
 *
 *  - Temporizador de cada carga (sincronizado con la hora del servidor)
 *  - Estados: horneando -> ¡VOLTEAR! -> horneando -> ¡LISTO!  (clases is-voltear / is-listo)
 *  - Color del pan según el avance (crudo, dorando, listo)
 *  - Avisos: sonido de 8 bits, aviso flotante y notificación del navegador
 *  - "Faltan pocos panes": cuando la masa por hornear baja del umbral, avisa para ir apagando hornos
 */
document.addEventListener('DOMContentLoaded', () => {
    const fuente = document.getElementById('datosHornear');
    if (!fuente) return;

    const datos = JSON.parse(fuente.textContent);
    // Diferencia entre el reloj del servidor y el del navegador (en segundos)
    const desfase = datos.ahora - Math.floor(Date.now() / 1000);
    const ahora = () => Math.floor(Date.now() / 1000) + desfase;

    const ls = window.localStorage;
    const ss = window.sessionStorage;

    /* ======================================================================
       Preferencias: umbral, sonido, notificaciones
       ====================================================================== */
    const inputUmbral = document.getElementById('umbralPocos');
    const btnSonido = document.getElementById('btnSonido');
    const btnNotif = document.getElementById('btnNotificaciones');

    let umbral = parseInt(ls.getItem('hornear.umbral') || '20', 10);
    let sonido = ls.getItem('hornear.sonido') !== 'no';
    if (inputUmbral) {
        inputUmbral.value = umbral;
        inputUmbral.addEventListener('change', () => {
            umbral = Math.max(1, parseInt(inputUmbral.value, 10) || 20);
            inputUmbral.value = umbral;
            ls.setItem('hornear.umbral', umbral);
            revisarPocos(true);
        });
    }

    function pintarSonido() {
        if (!btnSonido) return;
        btnSonido.setAttribute('aria-pressed', String(sonido));
        btnSonido.querySelector('i').className = 'ph-duotone ' + (sonido ? 'ph-speaker-high' : 'ph-speaker-slash');
        btnSonido.querySelector('span').textContent = sonido ? 'Sonido' : 'Silencio';
    }
    pintarSonido();
    if (btnSonido) btnSonido.addEventListener('click', () => {
        sonido = !sonido;
        ls.setItem('hornear.sonido', sonido ? 'si' : 'no');
        pintarSonido();
        if (sonido) beep('ok');
    });

    function pintarNotif() {
        if (!btnNotif) return;
        if (!('Notification' in window)) { btnNotif.hidden = true; return; }
        const estado = Notification.permission;
        btnNotif.querySelector('span').textContent =
            estado === 'granted' ? 'Avisos del navegador activos' :
            estado === 'denied' ? 'Avisos bloqueados en el navegador' : 'Activar avisos del navegador';
        btnNotif.disabled = estado !== 'default';
    }
    pintarNotif();
    if (btnNotif) btnNotif.addEventListener('click', () => Notification.requestPermission().then(pintarNotif));

    /* ======================================================================
       Sonido de 8 bits (onda cuadrada, sin archivos de audio)
       ====================================================================== */
    let audio = null;
    function beep(tipo) {
        if (!sonido) return;
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            const notas = { voltear: [660, 880], listo: [523, 659, 784, 1047], pocos: [440, 330, 440], ok: [880] }[tipo] || [660];
            notas.forEach((f, i) => {
                const osc = audio.createOscillator();
                const vol = audio.createGain();
                osc.type = 'square';
                osc.frequency.value = f;
                vol.gain.value = 0.06;
                osc.connect(vol).connect(audio.destination);
                const t = audio.currentTime + i * 0.12;
                osc.start(t);
                osc.stop(t + 0.1);
            });
        } catch (e) { /* el navegador puede bloquear el audio hasta que el usuario haga clic */ }
    }

    /* ======================================================================
       Avisos: flotante + notificación del navegador (una sola vez por evento)
       ====================================================================== */
    let pila = document.querySelector('.hor-toasts');
    if (!pila) {
        pila = document.createElement('div');
        pila.className = 'hor-toasts';
        document.body.appendChild(pila);
    }

    function avisar(clave, tipo, titulo, texto) {
        if (ss.getItem('hornear.aviso.' + clave)) return;   // ya se avisó en esta sesión
        ss.setItem('hornear.aviso.' + clave, '1');

        beep(tipo);

        const toast = document.createElement('div');
        toast.className = 'hor-toast is-' + tipo;
        toast.innerHTML = `<span class="pixel-text"></span><div><strong></strong><small></small></div>`;
        toast.querySelector('.pixel-text').textContent = tipo === 'listo' ? '¡LISTO!' : tipo === 'voltear' ? '¡VOLTEAR!' : '¡OJO!';
        toast.querySelector('strong').textContent = titulo;
        toast.querySelector('small').textContent = texto;
        toast.addEventListener('click', () => toast.remove());
        pila.appendChild(toast);
        setTimeout(() => toast.classList.add('is-saliendo'), 9000);
        setTimeout(() => toast.remove(), 9600);

        if ('Notification' in window && Notification.permission === 'granted') {
            try { new Notification(titulo, { body: texto, tag: 'hornear-' + clave }); } catch (e) { /* algunos navegadores no permiten notificar desde aquí */ }
        }
    }

    /* ======================================================================
       Temporizadores de las cargas
       ====================================================================== */
    const cargas = datos.cargas.map(c => ({ ...c, el: document.querySelector(`.hor-carga[data-carga="${c.id}"]`), fase: null }));
    const nombreHorno = (id) => {
        const h = document.querySelector(`.hor-horno[data-horno="${id}"] h3`);
        return h ? h.textContent.trim() : 'el horno';
    };
    const mmss = (s) => `${String(Math.floor(Math.abs(s) / 60)).padStart(2, '0')}:${String(Math.abs(s) % 60).padStart(2, '0')}`;

    function actualizarCargas() {
        const t = ahora();

        cargas.forEach(c => {
            if (!c.el) return;
            const total = c.minutos * 60;
            const transcurrido = Math.max(0, t - c.inicio);
            const restante = total - transcurrido;
            const avance = total > 0 ? Math.min(1, transcurrido / total) : 1;

            let fase = 'horneando';
            if (restante <= 0) fase = 'listo';
            else if (c.volteo !== null && !c.volteada && transcurrido >= c.volteo * 60) fase = 'voltear';

            // Color del pan según el avance
            const color = avance >= 1 ? 'pan-listo' : avance >= 0.45 ? 'pan-dorando' : 'pan-crudo';
            const svg = c.el.querySelector('.pan-pixel');
            if (svg && !svg.classList.contains(color)) {
                svg.classList.remove('pan-crudo', 'pan-dorando', 'pan-listo');
                svg.classList.add(color);
            }

            c.el.querySelector('.js-progreso').style.width = (avance * 100).toFixed(1) + '%';
            c.el.querySelector('.js-reloj').textContent = restante > 0 ? mmss(restante) : '+' + mmss(restante);

            const textoFase = {
                horneando: c.volteo !== null && !c.volteada ? `Voltear en ${mmss(c.volteo * 60 - transcurrido)}` : 'Horneando',
                voltear: '¡Toca voltear!',
                listo: restante > -60 ? '¡Listo para sacar!' : `Listo hace ${Math.floor(-restante / 60)} min`
            }[fase];
            c.el.querySelector('.js-fase').textContent = textoFase;

            if (fase !== c.fase) {
                c.el.classList.remove('is-horneando', 'is-voltear', 'is-listo');
                c.el.classList.add('is-' + fase);
                c.el.querySelector('.hor-carga-etiqueta').textContent = fase === 'listo' ? '¡LISTO!' : fase === 'voltear' ? '¡VOLTEAR!' : '';

                // Avisar solo cuando la fase cambia mientras la página está abierta (o al entrar, si ya estaba así)
                if (fase === 'voltear') avisar(`voltear-${c.id}`, 'voltear', `Voltear: ${c.producto} × ${c.piezas}`, `En ${nombreHorno(c.horno)}.`);
                if (fase === 'listo') avisar(`listo-${c.id}`, 'listo', `Listo para sacar: ${c.producto} × ${c.piezas}`, `En ${nombreHorno(c.horno)}.`);
                c.fase = fase;
            }

            // Botón "Sacar" resaltado cuando ya está listo
            const btnSacar = c.el.querySelector('.js-btn-sacar');
            if (btnSacar) btnSacar.classList.toggle('is-ahora', fase === 'listo');
            const btnVoltear = c.el.querySelector('.js-btn-voltear');
            if (btnVoltear) btnVoltear.classList.toggle('is-ahora', fase === 'voltear');
        });

        // Horno con algo listo: marco resaltado
        document.querySelectorAll('.hor-horno').forEach(h => {
            h.classList.toggle('tiene-listo', !!h.querySelector('.hor-carga.is-listo'));
            h.classList.toggle('tiene-voltear', !!h.querySelector('.hor-carga.is-voltear'));
        });
    }

    /* ======================================================================
       Pie de cada horno: cuándo se puede apagar
       ====================================================================== */
    function actualizarPies() {
        document.querySelectorAll('.hor-horno').forEach(h => {
            const pie = h.querySelector('.js-pie-horno');
            const tieneCargas = !!h.querySelector('.hor-carga');
            let texto = '', clase = '';

            if (!tieneCargas && datos.pendientes === 0) {
                texto = '<i class="ph-duotone ph-power"></i>Sin masa en espera: este horno se puede apagar.'; clase = 'is-apagar';
            } else if (tieneCargas && datos.pendientes === 0) {
                texto = '<i class="ph-duotone ph-power"></i>Es la última carga: apágalo al sacarla.'; clase = 'is-ultima';
            } else if (!tieneCargas) {
                texto = '<i class="ph-duotone ph-arrow-left"></i>Horno libre: mete masa desde "Listas para hornear".'; clase = 'is-libre';
            }
            pie.innerHTML = texto;
            pie.className = 'hor-horno-pie js-pie-horno ' + clase;
            pie.hidden = texto === '';
        });
    }

    /* ======================================================================
       Aviso de "faltan pocos panes"
       ====================================================================== */
    const caja = document.getElementById('avisoPocos');

    function revisarPocos(forzar) {
        if (!caja) return;
        const pend = datos.pendientes;
        const hayTrabajo = pend > 0 || datos.enHorno > 0;

        if (!hayTrabajo || pend > umbral) {
            caja.hidden = true;
            return;
        }

        caja.hidden = false;
        if (pend === 0) {
            document.getElementById('avisoPocosTitulo').textContent = 'Ya no hay masa en espera';
            document.getElementById('avisoPocosTexto').textContent = `Quedan ${datos.enHorno} piezas dentro de los hornos. Apaga cada horno al sacar su última carga.`;
            caja.className = 'hor-aviso-pocos is-final';
            avisar('pocos-0', 'pocos', 'Última horneada', 'Ya no hay masa en espera: apaga cada horno al sacar su última carga.');
        } else {
            document.getElementById('avisoPocosTitulo').textContent = `Faltan solo ${pend} piezas por hornear`;
            document.getElementById('avisoPocosTexto').textContent = 'Estate pendiente para ir apagando los hornos que se vayan desocupando.';
            caja.className = 'hor-aviso-pocos';
            if (!forzar) avisar('pocos-' + pend, 'pocos', `Faltan ${pend} piezas por hornear`, 'Ve preparándote para apagar los hornos.');
        }
    }

    /* ======================================================================
       Formulario "Meter": ajustar piezas según el horno elegido
       ====================================================================== */
    document.querySelectorAll('.hor-meter').forEach(form => {
        const pendientes = parseInt(form.dataset.pendientes, 10);
        const sel = form.querySelector('.js-horno');
        const inp = form.querySelector('.js-piezas');
        const ajustar = () => {
            const op = sel.options[sel.selectedIndex];
            const libres = op ? parseInt(op.dataset.libres, 10) : 0;
            const max = Math.max(1, Math.min(pendientes, libres));
            inp.max = max;
            if ((parseInt(inp.value, 10) || 0) > max || !inp.dataset.tocado) inp.value = max;
        };
        // Elegir el primer horno con espacio
        const libre = [...sel.options].find(o => !o.disabled);
        if (libre) sel.value = libre.value;
        sel.addEventListener('change', ajustar);
        inp.addEventListener('input', () => { inp.dataset.tocado = '1'; });
        ajustar();
    });

    /* ======================================================================
       Sacar antes de tiempo: pedir confirmación
       ====================================================================== */
    document.querySelectorAll('.js-btn-sacar').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const el = btn.closest('.hor-carga');
            const c = cargas.find(x => x.el === el);
            if (!c || c.fase === 'listo' || typeof window.appConfirm !== 'function') return;
            e.preventDefault();
            const falta = Math.ceil((c.minutos * 60 - (ahora() - c.inicio)) / 60);
            window.appConfirm({
                icono: 'ph-timer',
                titulo: '¿Sacar antes de tiempo?',
                texto: `A ${c.producto} todavía le faltan unos ${falta} min. ¿Seguro que ya está?`,
                aceptar: 'Sí, sacar',
                alAceptar: () => { document.body.classList.add('page-leave'); btn.closest('form').submit(); }
            });
        });
    });

    actualizarCargas();
    actualizarPies();
    revisarPocos(false);
    setInterval(actualizarCargas, 1000);
});
