<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';

$pagina_activa = 'arqueo';
include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/arqueo.css">

<div class="d-flex">
    
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        
        <div class="arq-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #6B4226; font-family: Georgia, serif; font-size: 28px; margin-bottom: 4px;">Arqueo diario</h2>
                </div>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <div class="arq-tabs mb-4 d-flex gap-2">
            <button type="button" class="arq-tab-btn active" data-target="tab-cuadre">Cuadre de Productos</button>
            <button type="button" class="arq-tab-btn" data-target="tab-cierre">Ingresos y Cierre</button>
            <button type="button" class="arq-tab-btn" data-target="tab-historial">Historial</button>
        </div>

        <form method="post" action="arqueo.php">
            
            <div class="arq-tab-content active" id="tab-cuadre">
                <div class="arq-card mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                        <h3 class="arq-card-title mb-0">Registro de piezas</h3>
                        <div class="arq-date-picker-group">
                            <input type="date" name="fecha_arqueo" class="form-control arq-input-date" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Producto</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Sobrante ayer</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Horneadas hoy</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Vendidas</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Sobrante hoy (contado)</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Diferencia</th>
                                    <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong style="color: #4A2F20; display: block;">Pan dulce</strong>
                                        <span class="text-muted" style="font-size: 12px;">$0.25 c/u</span>
                                    </td>
                                    <td class="text-center" style="color: #4A2F20;">0</td>
                                    <td class="text-center" style="color: #4A2F20;">0</td>
                                    <td class="text-center">
                                        <input type="number" min="0" name="vendidas[1]" class="form-control arq-table-input mx-auto" value="0">
                                    </td>
                                    <td class="text-center">
                                        <input type="number" min="0" name="sobrante[1]" class="form-control arq-table-input mx-auto" value="0">
                                    </td>
                                    <td class="text-center" style="color: #4A2F20;">0</td>
                                    <td class="text-end" style="color: #4A2F20; font-weight: 600;">$0.00</td>
                                </tr>

                                <tr>
                                    <td>
                                        <strong style="color: #4A2F20; display: block;">Pan francés</strong>
                                        <span class="text-muted" style="font-size: 12px;">$0.12 c/u</span>
                                    </td>
                                    <td class="text-center" style="color: #4A2F20;">0</td>
                                    <td class="text-center" style="color: #4A2F20;">0</td>
                                    <td class="text-center">
                                        <input type="number" min="0" name="vendidas[2]" class="form-control arq-table-input mx-auto" value="0">
                                    </td>
                                    <td class="text-center">
                                        <input type="number" min="0" name="sobrante[2]" class="form-control arq-table-input mx-auto" value="0">
                                    </td>
                                    <td class="text-center" style="color: #4A2F20;">0</td>
                                    <td class="text-end" style="color: #4A2F20; font-weight: 600;">$0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted" style="font-size: 12px; line-height: 1.5;">
                        Diferencia = sobrante de ayer + horneadas – vendidas – sobrante contado. Positiva: pan que no aparece (merma o faltante). Negativa: excedente (revise si faltó anotar una tanda).
                    </div>
                </div>
            </div>

            <div class="arq-tab-content" id="tab-cierre">
                <div class="arq-card mb-4">
                    <h3 class="arq-card-title mb-4">Resumen de ingresos</h3>
                    
                    <div class="arq-summary-grid mb-4">
                        <div class="arq-sub-card">
                            <span class="arq-sub-label">Ingreso esperado</span>
                            <strong class="arq-sub-val">$0.00</strong>
                            <span class="arq-sub-desc">vendidas × precio</span>
                        </div>
                        <div class="arq-sub-card arq-sub-warning">
                            <span class="arq-sub-label">Valor de la diferencia</span>
                            <strong class="arq-sub-val">$0.00</strong>
                            <span class="arq-sub-desc">pérdida estimada si es positiva</span>
                        </div>
                        <div class="arq-sub-card arq-sub-info">
                            <span class="arq-sub-label">Descuadre de caja</span>
                            <strong class="arq-sub-val">—</strong>
                            <span class="arq-sub-desc">dinero contado – esperado</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="arq-form-label">Dinero contado en caja ($)</label>
                            <input type="number" step="any" min="0" name="dinero_contado" class="form-control arq-input" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="arq-form-label">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control arq-input" placeholder="Ej. se regaló pan a un vecino">
                        </div>
                    </div>

                    <button type="submit" class="btn text-white px-4 py-2" style="background: #4A2F20; border-radius: 10px; font-weight: 600;">
                        Guardar arqueo
                    </button>
                </div>
            </div>

        </form>

        <div class="arq-tab-content" id="tab-historial">
            <div class="arq-card mb-4">
                <h3 class="arq-card-title">Historial reciente</h3>
                <div class="text-muted text-center py-5" style="font-size: 14px;">
                    Aún no hay arqueos guardados.
                </div>
            </div>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>

    </div>
</div>

<script src="/panaderia/Assets/js/arqueo.js"></script>