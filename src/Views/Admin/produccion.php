<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';

$pagina_activa = 'produccion';
include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/produccion.css">

<div class="d-flex">
    
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        
        <div class="prod-header-section mb-3">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #6B4226; font-family: Georgia, serif; font-size: 28px; margin-bottom: 0;">Producción</h2>
                </div>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <div class="prod-grid-top mb-4">
            
            <!-- Columna Izquierda: Formulario global envolviendo dos tarjetas separadas -->
            <form method="post" action="produccion.php" class="d-flex flex-column gap-3">
                
                <!-- Tarjeta 1: Datos principales de la tanda -->
                <div class="prod-card" style="padding: 16px;">
                    <h3 class="prod-card-title ps-1">Registrar tanda de horneado</h3>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="prod-form-label">Fecha</label>
                            <input type="text" name="fecha" class="form-control prod-input" value="<?= date('d/m/Y') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="prod-form-label">Producto</label>
                            <select name="producto_id" class="form-select prod-input">
                                <option value="1">Pan dulce</option>
                                <option value="2">Pan francés</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="prod-form-label">Harina de trigo (lb)</label>
                            <input type="number" step="any" min="0" name="cantidad_base" class="form-control prod-input" placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="prod-form-label">Piezas horneadas</label>
                            <input type="number" min="0" name="piezas" class="form-control prod-input" placeholder="0">
                        </div>
                    </div>

                    <div>
                        <label class="prod-form-label">Nota (opcional)</label>
                        <input type="text" name="nota" class="form-control prod-input" placeholder="Ej. tanda de la mañana">
                    </div>
                </div>

                <!-- Tarjeta 2: Tabla de insumos y botones de acción -->
                <div class="prod-card" style="padding: 16px;">
                    <label class="prod-form-label mb-2" style="font-size: 13px;">Consumo estimado de insumos</label>
                    <div class="table-responsive mb-3 border rounded" style="border-color: #EADFCE !important;">
                        <table class="table table-sm align-middle mb-0">
                            <thead style="background-color: #fcf9f4;">
                                <tr>
                                    <th class="ps-3 py-1" style="color: #8A7565; font-size: 11px; text-transform: uppercase;">Insumo</th>
                                    <th class="py-1" style="color: #8A7565; font-size: 11px; text-transform: uppercase;">Se usará</th>
                                    <th class="py-1" style="color: #8A7565; font-size: 11px; text-transform: uppercase;">Hay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $insumosProduccion = [
                                    ['nombre' => 'Harina de trigo', 'usar' => '0 lb', 'hay' => '100'],
                                    ['nombre' => 'Azúcar', 'usar' => '0 lb', 'hay' => '30'],
                                    ['nombre' => 'Huevos', 'usar' => '0 unidad', 'hay' => '60'],
                                    ['nombre' => 'Levadura', 'usar' => '0 lb', 'hay' => '3'],
                                    ['nombre' => 'Manteca vegetal', 'usar' => '0 lb', 'hay' => '20'],
                                    ['nombre' => 'Sal', 'usar' => '0 lb', 'hay' => '10'],
                                ];
                                foreach ($insumosProduccion as $ip):
                                ?>
                                <tr>
                                    <td class="ps-3 py-1"><strong style="color: #4A2F20; font-size: 12.5px;"><?= $ip['nombre'] ?></strong></td>
                                    <td class="py-1" style="color: #4A2F20; font-size: 12.5px;"><?= $ip['usar'] ?></td>
                                    <td class="py-1" style="color: #4A2F20; font-size: 12.5px;"><?= $ip['hay'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="forzar_stock" id="forzarStock">
                            <label class="form-check-label text-muted" for="forzarStock" style="font-size: 12px; margin-top: 2px;">
                                Forzar registro sin stock
                            </label>
                        </div>
                        <button type="submit" class="btn text-white px-3 py-1" style="background: #4A2F20; border-radius: 8px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Registrar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Columna Derecha -->
            <div class="d-flex flex-column gap-3">
                <div class="prod-card" style="padding: 16px;">
                    <h3 class="prod-card-title">¿Cómo funciona?</h3>
                    <p class="text-muted mb-0" style="font-size: 12.5px; line-height: 1.5;">
                        El sistema calcula el consumo de cada insumo según la receta y lo descuenta del inventario. Las piezas pasan al <a href="arqueo.php" style="color: #C98A3B; font-weight: 600; text-decoration: none;">arqueo diario</a> para compararse con lo vendido. Si hay un error, puede anular la tanda para restaurar existencias.
                    </p>
                </div>

                <div class="prod-card flex-grow-1" style="padding: 16px;">
                    <h3 class="prod-card-title">Últimas tandas</h3>
                    <div class="text-muted text-center py-4" style="font-size: 13px;">
                        Aún no hay tandas registradas.
                    </div>
                </div>
            </div>

        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>

    </div>
</div>

<script src="/panaderia/Assets/js/produccion.js"></script>