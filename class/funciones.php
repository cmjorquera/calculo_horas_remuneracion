<?php
class Funciones
{
    private $db;

    public function __construct(MySQL $db)
    {
        $this->db = $db;
    }

    public function obtenerDiasSemana($soloLaborales = true)
    {
        $sql = "SELECT clave, nombre, prefijo
                FROM dias_semana
                WHERE activo = 1";

        if ($soloLaborales) {
            $sql .= " AND orden <= 5";
        }

        $sql .= " ORDER BY orden ASC";

        $res = $this->db->consulta($sql);

        $dias = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $dias[] = [
                "key"    => $row["clave"],
                "label"  => $row["nombre"],
                "prefix" => $row["prefijo"]
            ];
        }

        return $dias;
    }
 public function obtenerEmpleadosConContratoVigente($id_colegio)
    {
        $id_colegio = (int)$id_colegio;

$sql = "
  SELECT
    e.id_empleado, e.codigo, e.run, e.nombres, e.apellido_paterno, e.apellido_materno, e.genero, e.activo,
    c.id_contrato, c.horas_semanales_cron, c.min_colacion_diaria, c.observacion,

    COALESCE(SUM(
      (CASE
        WHEN hs.man_ini IS NOT NULL AND hs.man_fin IS NOT NULL
             AND hs.man_ini <> '00:00:00' AND hs.man_fin <> '00:00:00'
        THEN TIMESTAMPDIFF(MINUTE, hs.man_ini, hs.man_fin)
        ELSE 0
      END)
      +
      (CASE
        WHEN hs.tar_ini IS NOT NULL AND hs.tar_fin IS NOT NULL
             AND hs.tar_ini <> '00:00:00' AND hs.tar_fin <> '00:00:00'
        THEN TIMESTAMPDIFF(MINUTE, hs.tar_ini, hs.tar_fin)
        ELSE 0
      END)
    ), 0) AS trabajadas_min

  FROM empleados e
  LEFT JOIN contratos_empleado c
    ON c.id_empleado = e.id_empleado AND c.fecha_fin IS NULL
  LEFT JOIN horarios_semanales hs
    ON hs.id_contrato = c.id_contrato AND hs.activo = 1

  WHERE e.id_colegio = {$id_colegio}

  GROUP BY
    e.id_empleado, e.codigo, e.run, e.nombres, e.apellido_paterno, e.apellido_materno, e.genero, e.activo,
    c.id_contrato, c.horas_semanales_cron, c.min_colacion_diaria, c.observacion

  ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres
";



        $res = $this->db->consulta($sql);

        $empleados = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $empleados[] = $row;
        }
        return $empleados;
    }

    public function calcularHorasSemanales($id_contrato)
    {
        $id_contrato = (int)$id_contrato;
        if ($id_contrato <= 0) return 0;

        $sqlH = "
            SELECT dia, man_ini, man_fin, tar_ini, tar_fin
            FROM horarios_semanales
            WHERE id_contrato = {$id_contrato} AND activo = 1
        ";
        $rh = $this->db->consulta($sqlH);

        $totalMin = 0;

        while ($h = $this->db->fetch_assoc($rh)) {

            if (!empty($h['man_ini']) && !empty($h['man_fin'])) {
                $q = $this->db->consulta("SELECT TIMESTAMPDIFF(MINUTE,'{$h['man_ini']}','{$h['man_fin']}') AS m");
                $m = $this->db->fetch_assoc($q);
                $totalMin += max(0, (int)$m['m']);
            }

            if (!empty($h['tar_ini']) && !empty($h['tar_fin'])) {
                $q = $this->db->consulta("SELECT TIMESTAMPDIFF(MINUTE,'{$h['tar_ini']}','{$h['tar_fin']}') AS m");
                $m = $this->db->fetch_assoc($q);
                $totalMin += max(0, (int)$m['m']);
            }
        }

        return round($totalMin / 60, 2);
    }


    public function obtenerEmpleadosConResumen($id_colegio)
{
    $empleados = $this->obtenerEmpleadosConContratoVigente($id_colegio);

    foreach ($empleados as &$e) {
        $contrato = (int)($e['horas_semanales_cron'] ?? 0);
        $trab = $this->calcularHorasSemanales($e['id_contrato']);

        $pct = ($contrato > 0) ? min(100, round(($trab / $contrato) * 100)) : 0;
        $diff = round($trab - $contrato, 2);

        $e['contrato_horas'] = $contrato;
        $e['trab_horas'] = $trab;
        $e['pct'] = $pct;
        $e['diff'] = $diff;
    }
    unset($e);

    return $empleados;
}

}
