<?php
/**
 * Vista de Arqueo Diario - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/arqueo.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cargar datos del usuario global y sesión
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';

// 2. Definir pestaña activa para iluminar "Arqueo diario" en el sidebar
$pagina_activa = 'arqueo';

// 3. Incluir el Header global
include_once __DIR__ . '/../Layouts/header.php';
?>

<!-- Hojas de estilos: Sidebar oficial y el CSS específico de Arqueo -->
<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/arqueo.css">

<div class="d-flex">
    
    <!-- Sidebar Modular -->
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <!-- Contenedor Principal (Idéntico a las vistas anteriores) -->
    <div class="main flex-grow-1 p-4">
        
        <!-- Cabecera de la sección -->
        <div class="arq-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #6B4226;">Arqueo diario</h2>
                </div>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <!-- Tarjeta Principal: Cuadre Diario -->
        <div class="arq-card mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <h3 class="arq-card-title mb-0">Cuadre del 4 nov 2026</h3>
                <div class="arq-date-picker-group">
                    <input type="text" class="form-control arq-input-date" value="04/11/2026">
                    <span class="arq-calendar-icon">📅</span>
                </div>
            </div>

            <!-- Tabla de Cuadre por Producto -->
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
                        <!-- Producto 1: Pan dulce -->
                        <tr>
                            <td>
                                <strong style="color: #4A2F20; display: block;">Pan dulce</strong>
                                <span class="text-muted" style="font-size: 12px;">$0.25 c/u</span>
                            </td>
                            <td class="text-center" style="color: #4A2F20;">0</td>
                            <td class="text-center" style="color: #4A2F20;">0</td>
                            <td class="text-center">
                                <input type="number" min="0" class="form-control arq-table-input mx-auto" value="0">
                            </td>
                            <td class="text-center">
                                <input type="number" min="0" class="form-control arq-table-input mx-auto" value="0">
                            </td>
                            <td class="text-center" style="color: #4A2F20;">0</td>
                            <td class="text-end" style="color: #4A2F20; font-weight: 600;">$0.00</td>
                        </tr>

                        <!-- Producto 2: Pan francés -->
                        <tr>
                            <td>
                                <strong style="color: #4A2F20; display: block;">Pan francés</strong>
                                <span class="text-muted" style="font-size: 12px;">$0.12 c/u</span>
                            </td>
                            <td class="text-center" style="color: #4A2F20;">0</td>
                            <td class="text-center" style="color: #4A2F20;">0</td>
                            <td class="text-center">
                                <input type="number" min="0" class="form-control arq-table-input mx-auto" value="0">
                            </td>
                            <td class="text-center">
                                <input type="number" min="0" class="form-control arq-table-input mx-auto" value="0">
                            </td>
                            <td class="text-center" style="color: #4A2F20;">0</td>
                            <td class="text-end" style="color: #4A2F20; font-weight: 600;">$0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Nota aclaratoria de fórmula -->
            <div class="text-muted mb-4" style="font-size: 12px; line-height: 1.5;">
                Diferencia = sobrante de ayer + horneadas – vendidas – sobrante contado. Positiva: pan que no aparece (merma o faltante). Negativa: excedente (revise si faltó anotar una tanda).
            </div>

            <!-- 3 Tarjetas de Resumen Inferiores -->
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

            <!-- Formulario inferior de dinero y observaciones -->
            <form method="post" action="arqueo.php">
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
            </form>
        </div>

        <!-- Tarjeta Secundaria: Historial reciente -->
        <div class="arq-card mb-4">
            <h3 class="arq-card-title">Historial reciente</h3>
            <div class="text-muted text-center py-4" style="font-size: 14px;">
                Aún no hay arqueos guardados.
            </div>
        </div>

        <!-- Footer oficial integrado unificado -->
        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>

    </div>
</div>

<script src="/panaderia/Assets/js/arqueo.js"></script>