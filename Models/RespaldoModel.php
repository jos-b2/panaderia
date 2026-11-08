<?php
/**
 * Modelo de Respaldos de la Base de Datos - Panadería Don Leo
 * Ubicación: /panaderia/Models/RespaldoModel.php
 */

class RespaldoModel {
    private $pdo;
    private $dir;

    public function __construct($pdo, $dir) {
        $this->pdo = $pdo;
        $this->dir = $dir;

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }

    /** Genera un .sql con estructura y datos de todas las tablas. Devuelve el nombre del archivo. */
    public function generar() {
        $tablas = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sqlDump = "-- Respaldo Panadería Don Leo\n-- Fecha: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tablas as $tabla) {
            $createStmt = $this->pdo->query("SHOW CREATE TABLE `{$tabla}`")->fetch(PDO::FETCH_ASSOC);
            $sqlDump .= "DROP TABLE IF EXISTS `{$tabla}`;\n";
            $sqlDump .= $createStmt['Create Table'] . ";\n\n";

            $filas = $this->pdo->query("SELECT * FROM `{$tabla}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($filas)) {
                foreach ($filas as $fila) {
                    $cols = array_map(fn($c) => "`{$c}`", array_keys($fila));
                    $vals = array_map(function ($v) {
                        return $v === null ? 'NULL' : $this->pdo->quote((string)$v);
                    }, array_values($fila));
                    $sqlDump .= "INSERT INTO `{$tabla}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
                }
                $sqlDump .= "\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $nombreArchivo = 'panaderia_' . date('Y-m-d_His') . '_manual.sql';
        file_put_contents($this->dir . '/' . $nombreArchivo, $sqlDump);

        return $nombreArchivo;
    }

    /** Respaldos existentes, del más reciente al más antiguo. */
    public function listar() {
        $archivos = glob($this->dir . '/*.sql') ?: [];
        usort($archivos, fn($a, $b) => filemtime($b) - filemtime($a));

        $lista = [];
        foreach ($archivos as $ruta) {
            $bytes = filesize($ruta);
            $tam = $bytes >= 1048576
                ? number_format($bytes / 1048576, 2) . ' MB'
                : number_format(max(1, $bytes / 1024), 0) . ' KB';

            $lista[] = [
                'archivo' => basename($ruta),
                'fecha' => date('Y-m-d H:i', filemtime($ruta)),
                'tam' => $tam
            ];
        }
        return $lista;
    }

    /** Ruta del archivo si el nombre es válido y existe; si no, null. */
    public function rutaDe($archivo) {
        $archivo = basename($archivo);
        $ruta = $this->dir . '/' . $archivo;

        if (preg_match('/^panaderia_[\w\-]+\.sql$/', $archivo) && file_exists($ruta)) {
            return $ruta;
        }
        return null;
    }
}
