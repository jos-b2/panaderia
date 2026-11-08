<?php
/**
 * Vista: Hornear - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/hornear.php
 * La prepara Controllers/HornearController.php. Animaciones y avisos en Assets/js/hornear.js
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/HornearController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';

/**
 * Sprite de pan en pixel art (16×8). Cada letra es un píxel:
 *   o = contorno · c = corteza · h = brillo · s = corte · C = corteza baja · . = vacío
 * Los colores salen de variables CSS (--px-o, --px-c...), así el mismo dibujo
 * cambia de crudo a dorado según avanza el horneado.
 */
$mapaPan = [
    '....oooooooo....',
    '..oocchhhhcccoo.',
    '.occhhscchhscco.',
    'occcsscccsscccco',
    'oCCCCCCCCCCCCCCo',
    'oCCCCCCCCCCCCCCo',
    '.oCCCCCCCCCCCCo.',
    '..oooooooooooo..',
];
$colores = ['o' => '--px-o', 'c' => '--px-c', 'h' => '--px-h', 's' => '--px-s', 'C' => '--px-C'];
$rectsPan = '';
foreach ($mapaPan as $y => $fila) {
    // Une píxeles iguales seguidos en un solo rectángulo
    for ($x = 0; $x < strlen($fila); ) {
        $ch = $fila[$x];
        $ancho = 1;
        while ($x + $ancho < strlen($fila) && $fila[$x + $ancho] === $ch) $ancho++;
        if (isset($colores[$ch])) {
            $rectsPan .= "<rect x=\"{$x}\" y=\"{$y}\" width=\"{$ancho}\" height=\"1\" style=\"fill:var({$colores[$ch]})\"/>";
        }
        $x += $ancho;
    }
}

$mesesCortos = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$minutosDesde = fn($fecha) => $fecha ? max(0, (int)round((time() - strtotime($fecha)) / 60)) : null;
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap">
<link rel="stylesheet" href="/panaderia/Assets/css/hornear.css?v=1">

<!-- Sprite del pan (se reutiliza con <use>) -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="sprite-pan" viewBox="0 0 16 8" shape-rendering="crispEdges"><?= $rectsPan ?></symbol>
</svg>

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head hor-header-section">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold page-title">Hornear</h2>
                    <p class="mb-0">Mete la masa a los hornos; te avisamos cuándo voltear y cuándo sacar.</p>
                </div>
                <div class="text-muted small">
                    <?= date('j') . ' ' . $mesesCortos[(int)date('n')] . ' ' . date('Y') ?>
                </div>
            </div>
        </div>

        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <!-- Avisos: pocos panes por hornear -->
        <div class="hor-avisos">
            <div class="hor-aviso-pocos" id="avisoPocos" hidden>
                <span class="hor-aviso-icono"><i class="ph-duotone ph-bell-ringing"></i></span>
                <div>
                    <strong id="avisoPocosTitulo"></strong>
                    <small id="avisoPocosTexto"></small>
                </div>
            </div>

            <div class="hor-config-avisos">
                <label for="umbralPocos">Avisarme cuando falten</label>
                <input type="number" id="umbralPocos" min="1" class="form-control form-control-sm" value="20">
                <span>piezas o menos</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnNotificaciones">
                    <i class="ph-duotone ph-bell"></i><span>Activar avisos del navegador</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSonido" aria-pressed="true">
                    <i class="ph-duotone ph-speaker-high"></i><span>Sonido</span>
                </button>
            </div>
        </div>

        <div class="hor-layout">

            <!-- Hornos -->
            <section class="hor-hornos">
                <?php if (empty($hornos)): ?>
                    <div class="hor-card empty-state py-5">
                        <i class="ph-duotone ph-oven"></i>
                        No hay hornos activos. Agrégalos en <a href="#configurar">Configurar hornos</a>.
                    </div>
                <?php endif; ?>

                <?php foreach ($hornos as $h): ?>
                    <?php
                        $hid = (int)$h['id'];
                        $ocupadas = (int)$h['ocupadas'];
                        $capacidad = (int)$h['capacidad'];
                        $pct = $capacidad > 0 ? min(100, round($ocupadas / $capacidad * 100)) : 0;
                        $cargasHorno = $cargasPorHorno[$hid] ?? [];
                    ?>
                    <article class="hor-horno <?= $cargasHorno ? 'is-encendido' : 'is-vacio' ?>" data-horno="<?= $hid ?>">
                        <header class="hor-horno-head">
                            <span class="hor-horno-icono"><i class="ph-duotone ph-oven"></i></span>
                            <div class="hor-horno-nombre">
                                <h3><?= htmlspecialchars($h['nombre']) ?></h3>
                                <small><span class="js-ocupadas"><?= $ocupadas ?></span> / <?= $capacidad ?> piezas</small>
                            </div>
                            <span class="hor-horno-estado js-estado-horno"><?= $cargasHorno ? 'Encendido' : 'Vacío' ?></span>
                        </header>

                        <!-- Capacidad en bloques pixelados -->
                        <div class="hor-capacidad" title="<?= $ocupadas ?> de <?= $capacidad ?> piezas">
                            <i style="width: <?= $pct ?>%"></i>
                        </div>

                        <!-- Cámara del horno -->
                        <div class="hor-camara">
                            <?php if (empty($cargasHorno)): ?>
                                <div class="hor-camara-vacia">
                                    <span class="pixel-text">HORNO VACÍO</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cargasHorno as $c): ?>
                                    <div class="hor-carga" data-carga="<?= (int)$c['id'] ?>">
                                        <div class="hor-carga-sprite">
                                            <span class="hor-chispas"><i></i><i></i><i></i><i></i></span>
                                            <span class="hor-vapor"><i></i><i></i><i></i></span>
                                            <svg class="pan-pixel" viewBox="0 0 16 8"><use href="#sprite-pan"/></svg>
                                            <span class="pixel-text hor-carga-etiqueta"></span>
                                        </div>

                                        <div class="hor-carga-info">
                                            <strong><?= htmlspecialchars($c['producto']) ?> <span>× <?= (int)$c['piezas'] ?></span></strong>
                                            <div class="hor-carga-tiempo">
                                                <span class="pixel-text js-reloj">--:--</span>
                                                <small class="js-fase">Horneando</small>
                                            </div>
                                            <div class="hor-progreso"><i class="js-progreso"></i>
                                                <?php if ($c['minutos_volteo'] !== null): ?>
                                                    <b class="hor-marca-volteo" style="left: <?= min(100, round($c['minutos_volteo'] / max(1, $c['minutos_horneado']) * 100)) ?>%" title="Voltear"></b>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="hor-carga-acciones">
                                            <?php if ($c['minutos_volteo'] !== null && !$c['volteada']): ?>
                                                <form method="post" action="HornearController.php" class="m-0">
                                                    <input type="hidden" name="accion" value="voltear">
                                                    <input type="hidden" name="carga_id" value="<?= (int)$c['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary js-btn-voltear"><i class="ph-duotone ph-arrows-clockwise"></i>Voltear</button>
                                                </form>
                                            <?php elseif ($c['volteada']): ?>
                                                <span class="hor-volteada"><i class="ph-duotone ph-check"></i>Volteada</span>
                                            <?php endif; ?>
                                            <form method="post" action="HornearController.php" class="m-0">
                                                <input type="hidden" name="accion" value="sacar">
                                                <input type="hidden" name="carga_id" value="<?= (int)$c['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-brand js-btn-sacar"><i class="ph-duotone ph-hand-grabbing"></i>Sacar</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Llamas pixeladas -->
                            <div class="hor-llamas" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                        </div>

                        <footer class="hor-horno-pie js-pie-horno"></footer>
                    </article>
                <?php endforeach; ?>
            </section>

            <!-- Masa lista para hornear -->
            <aside class="hor-aside">
                <section class="hor-card">
                    <header class="hor-cola-head">
                        <h3 class="hor-card-title">Listas para hornear</h3>
                        <span class="hor-total"><?= array_sum(array_map(fn($t) => (int)$t['pendientes'], $cola)) ?> pzs</span>
                    </header>

                    <?php if (empty($cola)): ?>
                        <div class="empty-state py-4">
                            <i class="ph-duotone ph-hand-grabbing"></i>
                            No hay masa esperando. Empieza a amasar una tanda en <a href="ProduccionController.php">Producción</a>.
                        </div>
                    <?php else: ?>
                        <ul class="hor-cola">
                            <?php foreach ($cola as $t): ?>
                                <?php
                                    $pend = (int)$t['pendientes'];
                                    $hace = $minutosDesde($t['inicio_amasado']);
                                    $mejorLibre = 0;
                                    foreach ($hornos as $h) $mejorLibre = max($mejorLibre, (int)$h['capacidad'] - (int)$h['ocupadas']);
                                ?>
                                <li class="hor-masa">
                                    <div class="hor-masa-top">
                                        <svg class="pan-pixel pan-crudo" viewBox="0 0 16 8"><use href="#sprite-pan"/></svg>
                                        <div>
                                            <strong><?= htmlspecialchars($t['producto']) ?></strong>
                                            <small>
                                                Tanda #<?= (int)$t['id'] ?> · <?= (int)$t['minutos_horneado'] ?> min de horno
                                                <?= $t['minutos_volteo'] !== null ? '· voltear a los ' . (int)$t['minutos_volteo'] : '' ?>
                                                <?= $hace !== null ? '<br>Amasada hace ' . ($hace < 60 ? $hace . ' min' : floor($hace / 60) . ' h ' . ($hace % 60) . ' min') : '' ?>
                                            </small>
                                        </div>
                                        <span class="hor-masa-piezas"><strong><?= $pend ?></strong><small>por hornear</small></span>
                                    </div>

                                    <form method="post" action="HornearController.php" class="hor-meter" data-pendientes="<?= $pend ?>">
                                        <input type="hidden" name="accion" value="meter">
                                        <input type="hidden" name="produccion_id" value="<?= (int)$t['id'] ?>">
                                        <select name="horno_id" class="form-select form-select-sm js-horno" required>
                                            <?php foreach ($hornos as $h): ?>
                                                <?php $libres = (int)$h['capacidad'] - (int)$h['ocupadas']; ?>
                                                <option value="<?= (int)$h['id'] ?>" data-libres="<?= $libres ?>" <?= $libres <= 0 ? 'disabled' : '' ?>>
                                                    <?= htmlspecialchars($h['nombre']) ?> · <?= $libres > 0 ? $libres . ' libres' : 'lleno' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" name="piezas" class="form-control form-control-sm js-piezas" min="1" max="<?= $pend ?>"
                                               value="<?= max(1, min($pend, $mejorLibre)) ?>" aria-label="Piezas a meter" required>
                                        <button type="submit" class="btn btn-sm btn-accent" <?= $mejorLibre <= 0 ? 'disabled title="Todos los hornos están llenos"' : '' ?>>
                                            <i class="ph-duotone ph-oven"></i>Meter
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <p class="hor-listas-hoy"><i class="ph-duotone ph-storefront"></i>Hoy salieron del horno <strong><?= number_format($listasHoy) ?></strong> piezas.</p>
                </section>
            </aside>
        </div>

        <!-- Configurar hornos -->
        <details class="hor-card hor-config" id="configurar" <?= (isset($_GET['ok']) && str_starts_with($_GET['ok'], 'horno_')) || empty($hornos) ? 'open' : '' ?>>
            <summary><i class="ph-duotone ph-gear-six"></i>Configurar hornos <small>(<?= count($todosHornos) ?>)</small></summary>

            <div class="hor-config-body">
                <?php foreach ($todosHornos as $h): ?>
                    <div class="hor-config-fila <?= $h['activo'] ? '' : 'is-inactivo' ?>">
                        <form method="post" action="HornearController.php" class="hor-config-form">
                            <input type="hidden" name="accion" value="horno_editar">
                            <input type="hidden" name="horno_id" value="<?= (int)$h['id'] ?>">
                            <input type="text" name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($h['nombre']) ?>" aria-label="Nombre" required>
                            <div class="hor-cap-input">
                                <input type="number" name="capacidad" class="form-control form-control-sm" min="1" value="<?= (int)$h['capacidad'] ?>" aria-label="Capacidad" required>
                                <span>pzs</span>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="ph-duotone ph-floppy-disk"></i>Guardar</button>
                        </form>
                        <form method="post" action="HornearController.php" class="m-0">
                            <input type="hidden" name="accion" value="horno_toggle">
                            <input type="hidden" name="horno_id" value="<?= (int)$h['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $h['activo'] ? 'btn-outline-danger' : 'btn-outline-secondary' ?>">
                                <i class="ph-duotone <?= $h['activo'] ? 'ph-power' : 'ph-check-circle' ?>"></i><?= $h['activo'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>

                <form method="post" action="HornearController.php" class="hor-config-fila hor-config-nuevo">
                    <input type="hidden" name="accion" value="horno_crear">
                    <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre del horno (ej. Horno 3)" required>
                    <div class="hor-cap-input">
                        <input type="number" name="capacidad" class="form-control form-control-sm" min="1" placeholder="Capacidad" required>
                        <span>pzs</span>
                    </div>
                    <button type="submit" class="btn btn-sm btn-brand"><i class="ph ph-plus"></i>Agregar horno</button>
                </form>
            </div>
        </details>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<!-- Datos para los temporizadores y avisos -->
<script id="datosHornear" type="application/json"><?= json_encode($datosHornear, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="/panaderia/Assets/js/hornear.js?v=1"></script>
