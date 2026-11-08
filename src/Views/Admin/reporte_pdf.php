<?php
/**
 * Vista: Reporte en PDF - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/reporte_pdf.php
 * La usa ReportesController.php (dompdf). Solo HTML + CSS sencillo que dompdf entiende.
 * Variables: $datos, $totales, $periodoTexto, $imagenes, $nombreUsuario
 */
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/ReportesController.php");
    exit;
}

$e = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
$dinero = fn($n) => $n === null ? '—' : '$' . number_format((float)$n, 2);
$fecha = fn($f) => date('d/m/Y', strtotime($f));

/**
 * Imprime una gráfica si el navegador la envió, ajustada a la caja disponible
 * sin deformarla (respeta la proporción original de la imagen).
 */
$grafica = function ($clave, $altoMax = 85) use ($imagenes) {
    if (empty($imagenes[$clave])) return;
    $info = @getimagesizefromstring(base64_decode(substr($imagenes[$clave], strpos($imagenes[$clave], ',') + 1)));
    if (!$info || $info[1] == 0) return;
    $proporcion = $info[0] / $info[1];
    $ancho = min(178, $altoMax * $proporcion);   // mm; 178 = ancho útil de la página carta
    $alto  = $ancho / $proporcion;
    printf('<div class="grafica"><img src="%s" style="width:%.1fmm;height:%.1fmm"></div>', $imagenes[$clave], $ancho, $alto);
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte - Panadería Don Leo</title>
<style>
    @page { margin: 28mm 16mm 20mm 16mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #3B2417; }

    /* Encabezado y pie fijos en cada página */
    .encabezado { position: fixed; top: -20mm; left: 0; right: 0; height: 14mm; border-bottom: 2px solid #C08A3E; }
    .encabezado .marca { font-size: 15pt; font-weight: bold; color: #3B2417; }
    .encabezado .sub { font-size: 8pt; color: #8C7868; letter-spacing: 1px; text-transform: uppercase; }
    .encabezado .derecha { position: absolute; right: 0; top: 2mm; text-align: right; font-size: 8pt; color: #8C7868; }
    .pie { position: fixed; bottom: -13mm; left: 0; right: 0; height: 8mm; border-top: 1px solid #E6DBC9; font-size: 7.5pt; color: #8C7868; padding-top: 2mm; }
    .pie .num:after { content: counter(page); }

    h1 { font-size: 19pt; margin: 0 0 2mm; color: #3B2417; }
    .periodo { color: #8C7868; margin-bottom: 6mm; }
    h2 { font-size: 12.5pt; margin: 7mm 0 1mm; color: #3B2417; border-left: 3px solid #C08A3E; padding-left: 3mm; }
    .nota { font-size: 8pt; color: #8C7868; margin-bottom: 3mm; }

    /* Resumen */
    table.resumen { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    table.resumen td { width: 20%; padding: 3mm 2mm; text-align: center; background: #FAF5EC; border: 1px solid #E6DBC9; }
    table.resumen .valor { font-size: 13pt; font-weight: bold; color: #3B2417; }
    table.resumen .etiqueta { font-size: 7pt; color: #8C7868; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Tablas */
    table.datos { width: 100%; border-collapse: collapse; margin-top: 2mm; }
    table.datos th { background: #3B2417; color: #FEFAE0; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.5px; padding: 2mm; text-align: left; }
    table.datos td { padding: 1.6mm 2mm; border-bottom: 1px solid #EFE7DA; }
    table.datos tr:nth-child(even) td { background: #FAF5EC; }
    table.datos .num { text-align: right; }
    table.datos tfoot td { font-weight: bold; border-top: 2px solid #C08A3E; background: #FFFFFF; }
    .neg { color: #A33A2C; }
    .pos { color: #3F7046; }

    .grafica { text-align: center; margin: 2mm 0; }
    .seccion { page-break-inside: avoid; }
</style>
</head>
<body>

<div class="encabezado">
    <div class="marca">Panadería Don Leo</div>
    <div class="sub">Reporte de gestión</div>
    <div class="derecha">Periodo: <?= $e($periodoTexto) ?><br>Generado el <?= date('d/m/Y H:i') ?> por <?= $e($nombreUsuario) ?></div>
</div>
<div class="pie">Panadería Don Leo · Sistema de gestión <span style="float:right">Página <span class="num"></span></span></div>

<h1>Reporte del periodo</h1>
<div class="periodo"><?= $e($periodoTexto) ?></div>

<table class="resumen">
    <tr>
        <td><div class="valor"><?= number_format($totales['horneadas']) ?></div><div class="etiqueta">Piezas horneadas</div></td>
        <td><div class="valor"><?= number_format($totales['vendidas']) ?></div><div class="etiqueta">Piezas vendidas</div></td>
        <td><div class="valor"><?= $dinero($totales['ingresos']) ?></div><div class="etiqueta">Ingresos</div></td>
        <td><div class="valor"><?= number_format($totales['pedidos']) ?></div><div class="etiqueta">Pedidos</div></td>
        <td><div class="valor"><?= $dinero($totales['inventario']) ?></div><div class="etiqueta">Inventario hoy</div></td>
    </tr>
</table>

<!-- 1. Producción vs ventas -->
<div class="seccion">
<h2>Producción vs. ventas</h2>
<div class="nota">Piezas horneadas (tandas no anuladas) y piezas vendidas según el arqueo diario.</div>
<?php $grafica('produccion', 72); ?>
</div>

<!-- 2. Ingresos -->
<div class="seccion">
<h2>Ingresos por día</h2>
<div class="nota">Ventas del arqueo (vendidas × precio) más abonos de pedidos.</div>
<?php $grafica('ingresos', 72); ?>
</div>

<!-- 3. Productos -->
<div class="seccion">
<h2>Resultados por producto</h2>
<?php $grafica('productos', 62); ?>
<table class="datos">
    <thead><tr><th>Producto</th><th class="num">Precio</th><th class="num">Horneadas</th><th class="num">Vendidas</th><th class="num">Ingreso</th></tr></thead>
    <tbody>
        <?php foreach ($datos['productos'] as $p): ?>
            <tr>
                <td><?= $e($p['producto']) ?></td>
                <td class="num"><?= $dinero($p['precio']) ?></td>
                <td class="num"><?= number_format($p['horneadas']) ?></td>
                <td class="num"><?= number_format($p['vendidas']) ?></td>
                <td class="num"><?= $dinero($p['ingreso']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</div>

<!-- 4. Pedidos -->
<div class="seccion">
<h2>Pedidos por estado</h2>
<?php $grafica('pedidos', 62); ?>
<table class="datos">
    <thead><tr><th>Estado</th><th class="num">Cantidad</th><th class="num">Monto</th></tr></thead>
    <tbody>
        <?php foreach ($datos['pedidos'] as $p): ?>
            <tr><td><?= $e($p['estado']) ?></td><td class="num"><?= number_format($p['cantidad']) ?></td><td class="num"><?= $dinero($p['monto']) ?></td></tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td>Total</td><td class="num"><?= number_format($totales['pedidos']) ?></td><td class="num"><?= $dinero(array_sum(array_column($datos['pedidos'], 'monto'))) ?></td></tr></tfoot>
</table>

</div>

<!-- 5. Inventario -->
<div class="seccion">
<h2>Inventario actual de materia prima</h2>
<?php $grafica('inventario', 75); ?>
<table class="datos">
    <thead><tr><th>Insumo</th><th class="num">Existencia</th><th class="num">Mínimo</th><th class="num">Costo</th><th class="num">Valor</th></tr></thead>
    <tbody>
        <?php foreach ($datos['inventario'] as $i): ?>
            <tr>
                <td><?= $e($i['insumo']) ?></td>
                <td class="num <?= $i['stock'] <= $i['minimo'] ? 'neg' : '' ?>"><?= number_format($i['stock'], 2) ?> <?= $e($i['unidad']) ?></td>
                <td class="num"><?= number_format($i['minimo'], 2) ?></td>
                <td class="num"><?= $dinero($i['costo']) ?></td>
                <td class="num"><?= $dinero($i['valor']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="4">Valor total del inventario</td><td class="num"><?= $dinero($totales['inventario']) ?></td></tr></tfoot>
</table>

</div>

<!-- 6. Caja -->
<div class="seccion">
<h2>Arqueo de caja</h2>
<?php if (empty($datos['caja'])): ?>
    <div class="nota">No hay arqueos guardados en el periodo.</div>
<?php else: ?>
    <?php $grafica('caja', 65); ?>
    <table class="datos">
        <thead><tr><th>Fecha</th><th class="num">Esperado</th><th class="num">Contado</th><th class="num">Diferencia</th></tr></thead>
        <tbody>
            <?php foreach ($datos['caja'] as $c): ?>
                <tr>
                    <td><?= $fecha($c['fecha']) ?></td>
                    <td class="num"><?= $dinero($c['esperado']) ?></td>
                    <td class="num"><?= $dinero($c['contado']) ?></td>
                    <td class="num <?= ($c['diferencia'] ?? 0) < 0 ? 'neg' : 'pos' ?>"><?= $dinero($c['diferencia']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="3">Descuadre acumulado</td><td class="num"><?= $dinero($totales['descuadre']) ?></td></tr></tfoot>
    </table>
<?php endif; ?>
</div>

</body>
</html>
