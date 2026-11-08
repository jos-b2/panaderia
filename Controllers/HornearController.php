<?php
/**
 * Controlador de Hornear - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/HornearController.php
 *
 * Acciones (POST "accion"):
 *   meter          -> mete piezas de una tanda a un horno
 *   voltear        -> marca una carga como volteada
 *   sacar          -> saca una carga (si era la última, la tanda queda lista)
 *   horno_crear / horno_editar / horno_toggle -> configuración de hornos
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/HornoModel.php';

$hornoModel = new HornoModel($pdo);

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $ok = '';

    try {
        switch ($accion) {
            case 'meter':
                $horno = $hornoModel->meterAlHorno((int)($_POST['produccion_id'] ?? 0), (int)($_POST['horno_id'] ?? 0), (int)($_POST['piezas'] ?? 0), $usuario_id);
                $ok = 'metida';
                break;

            case 'voltear':
                $hornoModel->voltear((int)($_POST['carga_id'] ?? 0));
                $ok = 'volteada';
                break;

            case 'sacar':
                $ok = $hornoModel->sacar((int)($_POST['carga_id'] ?? 0)) ? 'tanda_lista' : 'sacada';
                break;

            case 'horno_crear':
            case 'horno_editar':
                $nombre = trim($_POST['nombre'] ?? '');
                $capacidad = (int)($_POST['capacidad'] ?? 0);
                if ($nombre === '' || $capacidad <= 0) {
                    throw new InvalidArgumentException('Escribe el nombre del horno y una capacidad mayor a 0.');
                }
                if ($accion === 'horno_crear') {
                    $hornoModel->crearHorno($nombre, $capacidad);
                    $ok = 'horno_creado';
                } else {
                    $hornoModel->actualizarHorno((int)($_POST['horno_id'] ?? 0), $nombre, $capacidad);
                    $ok = 'horno_editado';
                }
                break;

            case 'horno_toggle':
                $hornoModel->alternarHorno((int)($_POST['horno_id'] ?? 0));
                $ok = 'horno_toggle';
                break;
        }

        if ($ok !== '') {
            header("Location: HornearController.php?ok={$ok}" . (str_starts_with($ok, 'horno_') ? '#configurar' : ''));
            exit;
        }
    } catch (InvalidArgumentException $e) {
        $mensaje = $e->getMessage();
        $tipo_alerta = 'warning';
    } catch (PDOException $e) {
        $mensaje = $e->getCode() === '23000' ? 'Ya existe un horno con ese nombre.' : 'No se pudo guardar el cambio.';
        $tipo_alerta = 'danger';
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_alerta = 'danger';
    }
}

$mensajesOk = [
    'metida'        => 'Pan dentro del horno. El temporizador ya está corriendo.',
    'volteada'      => 'Carga volteada.',
    'sacada'        => 'Carga fuera del horno.',
    'tanda_lista'   => '¡Tanda lista! Ya cuenta como pan disponible para pedidos y arqueo.',
    'horno_creado'  => 'Horno agregado.',
    'horno_editado' => 'Horno actualizado.',
    'horno_toggle'  => 'Estado del horno actualizado.',
];
if (isset($_GET['ok']) && isset($mensajesOk[$_GET['ok']])) {
    $mensaje = $mensajesOk[$_GET['ok']];
    $tipo_alerta = 'success';
}

$hornos       = $hornoModel->hornos();
$todosHornos  = $hornoModel->hornos(true);
$cola         = $hornoModel->cola();
$cargas       = $hornoModel->cargasActivas();
$listasHoy    = $hornoModel->piezasListasHoy();

// Cargas agrupadas por horno
$cargasPorHorno = [];
foreach ($cargas as $c) {
    $cargasPorHorno[(int)$c['horno_id']][] = $c;
}

// Datos para hornear.js (temporizadores y avisos). La hora del servidor sincroniza los relojes.
$datosHornear = [
    'ahora'      => time(),
    'pendientes' => array_sum(array_map(fn($t) => (int)$t['pendientes'], $cola)),
    'enHorno'    => array_sum(array_map(fn($c) => (int)$c['piezas'], $cargas)),
    'cargas'     => array_map(fn($c) => [
        'id'       => (int)$c['id'],
        'horno'    => (int)$c['horno_id'],
        'producto' => $c['producto'],
        'piezas'   => (int)$c['piezas'],
        'inicio'   => (int)$c['inicio_ts'],
        'minutos'  => (int)$c['minutos_horneado'],
        'volteo'   => $c['minutos_volteo'] !== null ? (int)$c['minutos_volteo'] : null,
        'volteada' => (bool)$c['volteada'],
    ], $cargas),
];

$pagina_activa = 'hornear';

require __DIR__ . '/../src/Views/Admin/hornear.php';
