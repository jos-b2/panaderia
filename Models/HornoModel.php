<?php
/**
 * Modelo de Hornos y Horneado - Panadería Don Leo
 * Ubicación: /panaderia/Models/HornoModel.php
 *
 * - Hornos: nombre y capacidad (piezas a la vez).
 * - Cola: tandas "amasando" u "horneando" que aún tienen piezas sin meter al horno.
 * - Cargas: piezas de una tanda dentro de un horno, con su hora de entrada.
 *   Una tanda puede repartirse en varias cargas y varios hornos.
 * - Cuando una tanda ya no tiene piezas pendientes ni cargas dentro del horno, pasa a "lista".
 */

class HornoModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* ======================================================================
       Hornos
       ====================================================================== */

    /** Hornos con su ocupación actual (piezas dentro). */
    public function hornos($incluirInactivos = false) {
        return $this->pdo->query("
            SELECT h.id, h.nombre, h.capacidad, h.activo,
                   COALESCE((SELECT SUM(c.piezas) FROM horno_cargas c WHERE c.horno_id = h.id AND c.fin IS NULL), 0) AS ocupadas
            FROM hornos h
            " . ($incluirInactivos ? "" : "WHERE h.activo = 1") . "
            ORDER BY h.activo DESC, h.nombre ASC
        ")->fetchAll();
    }

    public function crearHorno($nombre, $capacidad) {
        $stmt = $this->pdo->prepare("INSERT INTO hornos (nombre, capacidad) VALUES (?, ?)");
        $stmt->execute([$nombre, $capacidad]);
    }

    public function actualizarHorno($id, $nombre, $capacidad) {
        $stmt = $this->pdo->prepare("UPDATE hornos SET nombre = ?, capacidad = ? WHERE id = ?");
        $stmt->execute([$nombre, $capacidad, $id]);
    }

    /** Activa o desactiva un horno. No se puede desactivar si tiene pan adentro. */
    public function alternarHorno($id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM horno_cargas WHERE horno_id = ? AND fin IS NULL");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new Exception("No se puede desactivar un horno que tiene pan adentro. Saca primero las cargas.");
        }
        $this->pdo->prepare("UPDATE hornos SET activo = NOT activo WHERE id = ?")->execute([$id]);
    }

    /* ======================================================================
       Cola y cargas
       ====================================================================== */

    /** Tandas con masa lista y piezas que aún no entran al horno. */
    public function cola() {
        return $this->pdo->query("
            SELECT pr.id, pr.fecha, pr.piezas, pr.estado, pr.nota, pr.inicio_amasado,
                   p.nombre AS producto, p.minutos_horneado, p.minutos_volteo,
                   pr.piezas - COALESCE((SELECT SUM(c.piezas) FROM horno_cargas c WHERE c.produccion_id = pr.id), 0) AS pendientes
            FROM producciones pr
            JOIN productos p ON p.id = pr.producto_id
            WHERE pr.estado IN ('amasando', 'horneando') AND pr.anulada = 0
            HAVING pendientes > 0
            ORDER BY pr.inicio_amasado ASC, pr.id ASC
        ")->fetchAll();
    }

    /** Cargas que están dentro de algún horno, con sus tiempos (inicio en segundos Unix). */
    public function cargasActivas() {
        return $this->pdo->query("
            SELECT c.id, c.horno_id, c.produccion_id, c.piezas, c.volteada,
                   UNIX_TIMESTAMP(c.inicio) AS inicio_ts,
                   p.nombre AS producto, p.minutos_horneado, p.minutos_volteo
            FROM horno_cargas c
            JOIN producciones pr ON pr.id = c.produccion_id
            JOIN productos p ON p.id = pr.producto_id
            WHERE c.fin IS NULL
            ORDER BY c.inicio ASC
        ")->fetchAll();
    }

    /** Piezas que salieron del horno hoy (tandas listas o cargas terminadas). */
    public function piezasListasHoy() {
        return (int)$this->pdo->query("SELECT COALESCE(SUM(piezas), 0) FROM horno_cargas WHERE fin IS NOT NULL AND DATE(fin) = CURDATE()")->fetchColumn();
    }

    /**
     * Mete piezas de una tanda a un horno. Valida que la tanda tenga esas piezas pendientes
     * y que el horno tenga espacio.
     */
    public function meterAlHorno($produccion_id, $horno_id, $piezas, $usuario_id) {
        try {
            $this->pdo->beginTransaction();

            // Bloquea la tanda y el horno para que dos personas no metan lo mismo a la vez
            $stmt = $this->pdo->prepare("SELECT id, piezas, estado FROM producciones WHERE id = ? AND anulada = 0 FOR UPDATE");
            $stmt->execute([$produccion_id]);
            $tanda = $stmt->fetch();
            if (!$tanda || !in_array($tanda['estado'], ['amasando', 'horneando'], true)) {
                throw new Exception("Esa tanda no está lista para hornear (primero hay que empezar a amasarla).");
            }

            $stmt = $this->pdo->prepare("SELECT id, nombre, capacidad, activo FROM hornos WHERE id = ? FOR UPDATE");
            $stmt->execute([$horno_id]);
            $horno = $stmt->fetch();
            if (!$horno || !$horno['activo']) {
                throw new Exception("El horno no existe o está desactivado.");
            }

            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(piezas), 0) FROM horno_cargas WHERE produccion_id = ?");
            $stmt->execute([$produccion_id]);
            $pendientes = (int)$tanda['piezas'] - (int)$stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(piezas), 0) FROM horno_cargas WHERE horno_id = ? AND fin IS NULL");
            $stmt->execute([$horno_id]);
            $libres = (int)$horno['capacidad'] - (int)$stmt->fetchColumn();

            if ($piezas <= 0) {
                throw new Exception("Indica cuántas piezas vas a meter al horno.");
            }
            if ($piezas > $pendientes) {
                throw new Exception("A esa tanda solo le quedan {$pendientes} piezas por hornear.");
            }
            if ($piezas > $libres) {
                throw new Exception("En {$horno['nombre']} solo caben {$libres} piezas más.");
            }

            $this->pdo->prepare("INSERT INTO horno_cargas (produccion_id, horno_id, piezas, usuario_id) VALUES (?, ?, ?, ?)")
                      ->execute([$produccion_id, $horno_id, $piezas, $usuario_id]);
            $this->pdo->prepare("UPDATE producciones SET estado = 'horneando' WHERE id = ?")->execute([$produccion_id]);

            $this->pdo->commit();
            return $horno['nombre'];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Marca una carga como volteada. */
    public function voltear($carga_id) {
        $stmt = $this->pdo->prepare("UPDATE horno_cargas SET volteada = 1 WHERE id = ? AND fin IS NULL");
        $stmt->execute([$carga_id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Esa carga ya no está en el horno.");
        }
    }

    /**
     * Saca una carga del horno. Si la tanda ya no tiene piezas pendientes ni cargas
     * dentro de algún horno, pasa a "lista". Devuelve true si la tanda quedó lista.
     */
    public function sacar($carga_id) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("SELECT id, produccion_id FROM horno_cargas WHERE id = ? AND fin IS NULL FOR UPDATE");
            $stmt->execute([$carga_id]);
            $carga = $stmt->fetch();
            if (!$carga) {
                throw new Exception("Esa carga ya salió del horno.");
            }

            $this->pdo->prepare("UPDATE horno_cargas SET fin = NOW() WHERE id = ?")->execute([$carga_id]);

            // ¿Quedó lista la tanda?
            $stmt = $this->pdo->prepare("
                SELECT pr.piezas,
                       COALESCE(SUM(c.piezas), 0) AS cargadas,
                       COALESCE(SUM(c.fin IS NULL), 0) AS adentro
                FROM producciones pr
                LEFT JOIN horno_cargas c ON c.produccion_id = pr.id
                WHERE pr.id = ?
                GROUP BY pr.id, pr.piezas
            ");
            $stmt->execute([$carga['produccion_id']]);
            $t = $stmt->fetch();

            $quedoLista = $t && (int)$t['cargadas'] >= (int)$t['piezas'] && (int)$t['adentro'] === 0;
            if ($quedoLista) {
                $this->pdo->prepare("UPDATE producciones SET estado = 'lista', fin_horneado = NOW() WHERE id = ?")
                          ->execute([$carga['produccion_id']]);
            }

            $this->pdo->commit();
            return $quedoLista;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
