<?php
/**
 * Controlador de Reportes - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/ReportesController.php
 *
 * GET                    -> vista con gráficas (filtro ?desde=Y-m-d&hasta=Y-m-d)
 * GET  ?exportar=excel   -> descarga .xlsx (PhpSpreadsheet)
 * POST accion=pdf        -> descarga .pdf (dompdf) con las gráficas enviadas como imágenes
 *
 * Las exportaciones necesitan las librerías de Composer (carpeta vendor/).
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/ReporteModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use Dompdf\Dompdf;
use Dompdf\Options;

$reporteModel = new ReporteModel($pdo);

$mensaje = '';
$tipo_alerta = '';

/* ---------- Rango de fechas (por defecto, últimos 30 días) ---------- */
$entrada = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$valida = fn($f) => is_string($f) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) && strtotime($f) !== false;

$hasta = $valida($entrada['hasta'] ?? null) ? $entrada['hasta'] : date('Y-m-d');
$desde = $valida($entrada['desde'] ?? null) ? $entrada['desde'] : date('Y-m-d', strtotime($hasta . ' -29 days'));
if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}
// Máximo un año para mantener las gráficas legibles
if ((strtotime($hasta) - strtotime($desde)) / 86400 > 366) {
    $desde = date('Y-m-d', strtotime($hasta . ' -366 days'));
}

/* ---------- Datos ---------- */
$datos = [
    'produccion' => $reporteModel->produccionVsVentas($desde, $hasta),
    'ingresos'   => $reporteModel->ingresosPorDia($desde, $hasta),
    'productos'  => $reporteModel->porProducto($desde, $hasta),
    'pedidos'    => $reporteModel->pedidosPorEstado($desde, $hasta),
    'inventario' => $reporteModel->inventario(),
    'caja'       => $reporteModel->caja($desde, $hasta),
];

$sumar = fn($filas, $campo) => array_sum(array_map(fn($f) => (float)($f[$campo] ?? 0), $filas));

$totales = [
    'horneadas'  => (int)$sumar($datos['produccion'], 'horneadas'),
    'vendidas'   => (int)$sumar($datos['produccion'], 'vendidas'),
    'ventas'     => round($sumar($datos['ingresos'], 'ventas'), 2),
    'abonos'     => round($sumar($datos['ingresos'], 'abonos'), 2),
    'ingresos'   => round($sumar($datos['ingresos'], 'total'), 2),
    'pedidos'    => (int)$sumar($datos['pedidos'], 'cantidad'),
    'inventario' => round($sumar($datos['inventario'], 'valor'), 2),
    'descuadre'  => round($sumar($datos['caja'], 'diferencia'), 2),
];

$meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fechaCorta = fn($f) => date('j', strtotime($f)) . ' ' . $meses[(int)date('n', strtotime($f))] . ' ' . date('Y', strtotime($f));
$periodoTexto = $fechaCorta($desde) . ' – ' . $fechaCorta($hasta);
$nombreArchivo = "reporte_panaderia_{$desde}_{$hasta}";

/* ---------- Exportaciones ---------- */
$autoload = __DIR__ . '/../vendor/autoload.php';
$pideExcel = ($_GET['exportar'] ?? '') === 'excel';
$pidePdf   = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'pdf';

if (($pideExcel || $pidePdf) && !file_exists($autoload)) {
    $mensaje = 'Para exportar falta instalar las librerías del proyecto. Abre una terminal en la carpeta del proyecto y ejecuta: composer install';
    $tipo_alerta = 'warning';
    $pideExcel = $pidePdf = false;
}

if ($pideExcel) {
    require $autoload;
    exportarExcel($datos, $totales, $periodoTexto, $nombreArchivo, $nombreUsuario);
    exit;
}

if ($pidePdf) {
    require $autoload;

    // Imágenes de las gráficas enviadas por el navegador (solo PNG en base64)
    $imagenes = [];
    foreach (($_POST['imagenes'] ?? []) as $clave => $img) {
        if (is_string($img) && strlen($img) < 4000000 && preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $img)) {
            $imagenes[preg_replace('/[^a-z]/', '', $clave)] = $img;
        }
    }

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', false);

    ob_start();
    require __DIR__ . '/../src/Views/Admin/reporte_pdf.php';
    $html = ob_get_clean();

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    $dompdf->stream($nombreArchivo . '.pdf', ['Attachment' => true]);
    exit;
}

$exportarDisponible = file_exists($autoload);
$pagina_activa = 'reportes';

require __DIR__ . '/../src/Views/Admin/reportes.php';


/* ==========================================================================
   EXCEL (PhpSpreadsheet): una hoja por reporte, con formato y gráficas nativas
   ========================================================================== */
function exportarExcel($datos, $totales, $periodoTexto, $nombreArchivo, $usuario) {
    $libro = new Spreadsheet();
    $libro->getProperties()
        ->setCreator('Panadería Don Leo')
        ->setTitle('Reporte ' . $periodoTexto);

    $moneda = '"$"#,##0.00';
    $entero = '#,##0';

    // Hoja 1: Resumen
    $resumen = $libro->getActiveSheet();
    $resumen->setTitle('Resumen');
    escribirEncabezado($resumen, 'Reporte de la Panadería Don Leo', $periodoTexto, $usuario, 2);
    $filasResumen = [
        ['Piezas horneadas', $totales['horneadas']],
        ['Piezas vendidas', $totales['vendidas']],
        ['Ventas del arqueo', $totales['ventas']],
        ['Abonos de pedidos', $totales['abonos']],
        ['Ingresos totales', $totales['ingresos']],
        ['Pedidos registrados', $totales['pedidos']],
        ['Valor del inventario (hoy)', $totales['inventario']],
        ['Descuadre de caja acumulado', $totales['descuadre']],
    ];
    escribirTabla($resumen, ['Indicador', 'Valor'], $filasResumen, [null, $entero]);
    foreach ([7, 8, 9, 11, 12] as $fila) {
        $resumen->getStyle("B{$fila}")->getNumberFormat()->setFormatCode($moneda);
    }
    $resumen->getStyle('A9:B9')->getFont()->setBold(true);

    // Hoja 2: Producción vs ventas
    $hoja = $libro->createSheet()->setTitle('Produccion');
    escribirEncabezado($hoja, 'Producción vs. ventas por día', $periodoTexto, $usuario, 3);
    $filas = array_map(fn($f) => [fechaExcel($f['fecha']), $f['horneadas'], $f['vendidas']], $datos['produccion']);
    $n = escribirTabla($hoja, ['Fecha', 'Piezas horneadas', 'Piezas vendidas'], $filas, ['dd/mm/yyyy', $entero, $entero]);
    agregarGrafica($hoja, 'Produccion', DataSeries::TYPE_BARCHART, 'A', ['B', 'C'], $n, 'Piezas horneadas vs. vendidas', 'E4', 'P24');

    // Hoja 3: Ingresos
    $hoja = $libro->createSheet()->setTitle('Ingresos');
    escribirEncabezado($hoja, 'Ingresos por día', $periodoTexto, $usuario, 4);
    $filas = array_map(fn($f) => [fechaExcel($f['fecha']), $f['ventas'], $f['abonos'], $f['total']], $datos['ingresos']);
    $n = escribirTabla($hoja, ['Fecha', 'Ventas (arqueo)', 'Abonos', 'Total'], $filas, ['dd/mm/yyyy', $moneda, $moneda, $moneda]);
    agregarGrafica($hoja, 'Ingresos', DataSeries::TYPE_BARCHART, 'A', ['B', 'C'], $n, 'Ingresos por día', 'F4', 'Q24', true);

    // Hoja 4: Por producto
    $hoja = $libro->createSheet()->setTitle('Productos');
    escribirEncabezado($hoja, 'Resultados por producto', $periodoTexto, $usuario, 5);
    $filas = array_map(fn($f) => [$f['producto'], $f['precio'], $f['horneadas'], $f['vendidas'], $f['ingreso']], $datos['productos']);
    $n = escribirTabla($hoja, ['Producto', 'Precio', 'Piezas horneadas', 'Piezas vendidas', 'Ingreso'], $filas, [null, $moneda, $entero, $entero, $moneda]);
    agregarGrafica($hoja, 'Productos', DataSeries::TYPE_PIECHART, 'A', ['C'], $n, 'Piezas horneadas por producto', 'G4', 'O22');

    // Hoja 5: Pedidos
    $hoja = $libro->createSheet()->setTitle('Pedidos');
    escribirEncabezado($hoja, 'Pedidos por estado', $periodoTexto, $usuario, 3);
    $filas = array_map(fn($f) => [$f['estado'], $f['cantidad'], $f['monto']], $datos['pedidos']);
    $n = escribirTabla($hoja, ['Estado', 'Cantidad', 'Monto'], $filas, [null, $entero, $moneda]);
    agregarGrafica($hoja, 'Pedidos', DataSeries::TYPE_PIECHART, 'A', ['B'], $n, 'Pedidos por estado', 'E4', 'M22');

    // Hoja 6: Inventario
    $hoja = $libro->createSheet()->setTitle('Inventario');
    escribirEncabezado($hoja, 'Inventario actual de materia prima', 'Existencias al ' . date('d/m/Y'), $usuario, 6);
    $filas = array_map(fn($f) => [$f['insumo'], $f['unidad'], $f['stock'], $f['minimo'], $f['costo'], $f['valor']], $datos['inventario']);
    $n = escribirTabla($hoja, ['Insumo', 'Unidad', 'Existencia', 'Mínimo', 'Costo unitario', 'Valor'], $filas, [null, null, '#,##0.00', '#,##0.00', $moneda, $moneda]);
    agregarGrafica($hoja, 'Inventario', DataSeries::TYPE_BARCHART, 'A', ['C', 'D'], $n, 'Existencia vs. mínimo', 'H4', 'Q24');

    // Hoja 7: Caja
    $hoja = $libro->createSheet()->setTitle('Caja');
    escribirEncabezado($hoja, 'Arqueo de caja', $periodoTexto, $usuario, 4);
    $filas = array_map(fn($f) => [fechaExcel($f['fecha']), $f['esperado'], $f['contado'], $f['diferencia']], $datos['caja']);
    $n = escribirTabla($hoja, ['Fecha', 'Esperado', 'Contado', 'Diferencia'], $filas, ['dd/mm/yyyy', $moneda, $moneda, $moneda]);
    agregarGrafica($hoja, 'Caja', DataSeries::TYPE_LINECHART, 'A', ['B', 'C'], $n, 'Esperado vs. contado', 'F4', 'Q24');

    $libro->setActiveSheetIndex(0);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($libro);
    $writer->setIncludeCharts(true);
    $writer->save('php://output');
}

/** Título, periodo y autor en las filas 1–2; deja la tabla desde la fila 4. */
function escribirEncabezado($hoja, $titulo, $subtitulo, $usuario, $columnas) {
    $ultima = chr(ord('A') + max(1, $columnas) - 1);
    $hoja->setCellValue('A1', $titulo);
    $hoja->setCellValue('A2', $subtitulo . '  ·  Generado el ' . date('d/m/Y H:i') . ' por ' . $usuario);
    $hoja->mergeCells("A1:{$ultima}1");
    $hoja->mergeCells("A2:{$ultima}2");
    $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('3B2417');
    $hoja->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('8C7868');
    $hoja->getRowDimension(1)->setRowHeight(24);
}

/**
 * Escribe encabezados (fila 4) y filas (desde la 5) con estilo.
 * $formatos: formato numérico por columna (null = sin formato). Devuelve el número de filas.
 */
function escribirTabla($hoja, $encabezados, $filas, $formatos) {
    $ultima = chr(ord('A') + count($encabezados) - 1);
    $hoja->fromArray($encabezados, null, 'A4');
    $hoja->getStyle("A4:{$ultima}4")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FEFAE0']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B2417']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);
    $hoja->getRowDimension(4)->setRowHeight(20);

    $n = count($filas);
    if ($n > 0) {
        $hoja->fromArray($filas, null, 'A5', true);
        $fin = 4 + $n;
        $hoja->getStyle("A5:{$ultima}{$fin}")->getBorders()->getAllBorders()
             ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E6DBC9');
        // Filas alternas en crema
        for ($f = 6; $f <= $fin; $f += 2) {
            $hoja->getStyle("A{$f}:{$ultima}{$f}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FAF5EC');
        }
        foreach ($formatos as $i => $formato) {
            if ($formato) {
                $col = chr(ord('A') + $i);
                $hoja->getStyle("{$col}5:{$col}{$fin}")->getNumberFormat()->setFormatCode($formato);
            }
        }
    }

    foreach (range('A', $ultima) as $col) {
        $hoja->getColumnDimension($col)->setAutoSize(true);
    }
    $hoja->freezePane('A5');
    return $n;
}

/** Gráfica nativa de Excel a partir de la tabla (categorías en $colCat, series en $colsValores). */
function agregarGrafica($hoja, $nombreHoja, $tipo, $colCat, $colsValores, $n, $titulo, $desde, $hasta, $apilada = false) {
    if ($n === 0) return;
    $fin = 4 + $n;

    $etiquetas = [];
    $valores = [];
    foreach ($colsValores as $col) {
        $etiquetas[] = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$nombreHoja}'!\${$col}\$4", null, 1);
        $valores[]   = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$nombreHoja}'!\${$col}\$5:\${$col}\${$fin}", null, $n);
    }
    $categorias = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$nombreHoja}'!\${$colCat}\$5:\${$colCat}\${$fin}", null, $n)];

    if ($tipo === DataSeries::TYPE_PIECHART) {
        $agrupacion = null;
    } elseif ($tipo === DataSeries::TYPE_LINECHART) {
        $agrupacion = DataSeries::GROUPING_STANDARD;
    } else {
        $agrupacion = $apilada ? DataSeries::GROUPING_STACKED : DataSeries::GROUPING_CLUSTERED;
    }

    $serie = new DataSeries($tipo, $agrupacion, range(0, count($valores) - 1), $etiquetas, $categorias, $valores);
    if ($tipo === DataSeries::TYPE_BARCHART) {
        $serie->setPlotDirection(DataSeries::DIRECTION_COL);
    }

    $grafica = new Chart(
        'grafica_' . strtolower($nombreHoja),
        new Title($titulo),
        new Legend(Legend::POSITION_BOTTOM, null, false),
        new PlotArea(null, [$serie]),
        true,
        'gap'
    );
    $grafica->setTopLeftPosition($desde);
    $grafica->setBottomRightPosition($hasta);
    $hoja->addChart($grafica);
}

/** Convierte Y-m-d a número de fecha de Excel para que se pueda ordenar y filtrar. */
function fechaExcel($fecha) {
    return \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($fecha));
}
