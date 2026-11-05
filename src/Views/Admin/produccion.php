<?php
/**
 * Vista de Producción - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/produccion.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cargar datos del usuario global y sesión
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';

// 2. Definir pestaña activa para iluminar "Producción" en el sidebar
$pagina_activa = 'produccion';

// 3. Incluir el Header global
include_once __DIR__ . '/../Layouts/header.php';
?>

<!-- Hojas de estilos: Sidebar oficial y el CSS específico de Producción -->
<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/produccion.css">

<div class="d-flex">
    
    <!-- Sidebar Modular -->
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <!-- Contenedor Principal (Idéntico al Panel, Insumos y Recetas) -->
    <div class="main flex-grow-1 p-4">
        
        <!-- Cabecera de la sección -->
        <div class="prod-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #6B4226;">Producción</h2>
                </div>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <!-- Cuadrícula Superior (Formulario de Registro y Tarjeta Informativa) -->
        <div class="prod-grid-top mb-4">
            
            <!-- Columna Izquierda: Registrar tanda de horneado -->
            <div class="prod-card">
                <h3 class="prod-card-title">Registrar tanda de horneado</h3>
                
                <form method="post" action="produccion.php">
                    <div class="row g-3 mb-3">
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

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="prod-form-label">Cantidad de lb de harina de trigo</label>
                            <input type="number" step="any" min="0" name="cantidad_base" class="form-control prod-input" placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="prod-form-label">Piezas horneadas</label>
                            <input type="number" min="0" name="piezas" class="form-control prod-input" placeholder="0">
                            <small class="text-muted" style="font-size: 11.5px;">Se sugiere según el rendimiento; corrija con el conteo real.</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="prod-form-label">Nota (opcional)</label>
                        <input type="text" name="nota" class="form-control prod-input" placeholder="Ej. tanda de la mañana">
                    </div>

                    <!-- Tabla de cálculo de insumos dinámicos -->
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Insumo</th>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Se usará</th>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Hay</th>
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
                                    <td><strong style="color: #4A2F20;"><?= $ip['nombre'] ?></strong></td>
                                    <td style="color: #4A2F20;"><?= $ip['usar'] ?></td>
                                    <td style="color: #4A2F20;"><?= $ip['hay'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Checkbox de advertencia -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="forzar_stock" id="forzarStock">
                        <label class="form-check-label text-muted" for="forzarStock" style="font-size: 13.5px;">
                            Registrar aunque falte existencia (el inventario quedará en 0)
                        </label>
                    </div>

                    <!-- Botón de envío -->
                    <button type="submit" class="btn text-white px-4 py-2" style="background: #4A2F20; border-radius: 10px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Registrar tanda
                    </button>
                </form>
            </div>

            <!-- Columna Derecha: ¿Cómo funciona? -->
            <div class="prod-card h-100">
                <h3 class="prod-card-title">¿Cómo funciona?</h3>
                <p class="text-muted" style="font-size: 14px; line-height: 1.6;">
                    Al registrar una tanda, el sistema calcula el consumo de cada insumo según la receta y lo descuenta del inventario. Las piezas horneadas pasan directamente al <a href="arqueo.php" style="color: #C98A3B; font-weight: 600; text-decoration: none;">arqueo diario</a>, donde se comparan con lo vendido.
                </p>
                <p class="text-muted mb-0" style="font-size: 14px; line-height: 1.6;">
                    Si se equivocó, un administrador puede anular la tanda y las existencias regresan al inventario.
                </p>
            </div>

        </div>

        <!-- Tarjeta Inferior: Últimas tandas -->
        <div class="prod-card mb-4">
            <h3 class="prod-card-title">Últimas tandas</h3>
            <div class="text-muted text-center py-4" style="font-size: 14px;">
                Aún no hay tandas registradas.
            </div>
        </div>

        <!-- Footer oficial integrado unificado -->
        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>

    </div>
</div>

<script src="/panaderia/Assets/js/produccion.js"></script>