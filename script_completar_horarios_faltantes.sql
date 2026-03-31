-- Completa horarios semanales solo para empleados/contratos que hoy no tienen ningun horario activo.
-- Pensado para el dump de pruebas donde los contratos son de 40:00 semanales
-- y el horario base esperado es:
-- Lunes a Viernes
--   08:00 - 13:00
--   14:00 - 17:00
--
-- Antes de ejecutar, puedes correr solo el bloque PREVIEW para ver a quienes afectara.

START TRANSACTION;

-- PREVIEW: contratos vigentes sin ningun horario activo
SELECT
  e.id_empleado,
  c.id_contrato,
  e.id_colegio,
  e.run,
  CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) AS empleado,
  c.horas_semanales_cron,
  c.horas_lectivas,
  c.horas_no_lectivas
FROM contratos_empleado c
INNER JOIN empleados e
  ON e.id_empleado = c.id_empleado
WHERE c.fecha_fin IS NULL
  AND NOT EXISTS (
    SELECT 1
    FROM horarios_semanales hs
    WHERE hs.id_contrato = c.id_contrato
      AND hs.activo = 1
  )
ORDER BY e.id_colegio, e.id_empleado;

-- INSERT: crea 5 dias para cada contrato que no tenga ningun horario activo
INSERT INTO horarios_semanales
  (id_empleado, id_contrato, dia, man_ini, man_fin, tar_ini, tar_fin, activo, created_at)
SELECT
  c.id_empleado,
  c.id_contrato,
  d.dia,
  '08:00:00' AS man_ini,
  '13:00:00' AS man_fin,
  '14:00:00' AS tar_ini,
  '17:00:00' AS tar_fin,
  1 AS activo,
  NOW() AS created_at
FROM contratos_empleado c
INNER JOIN empleados e
  ON e.id_empleado = c.id_empleado
INNER JOIN (
  SELECT 'LUN' AS dia
  UNION ALL SELECT 'MAR'
  UNION ALL SELECT 'MIE'
  UNION ALL SELECT 'JUE'
  UNION ALL SELECT 'VIE'
) d
WHERE c.fecha_fin IS NULL
  AND NOT EXISTS (
    SELECT 1
    FROM horarios_semanales hs
    WHERE hs.id_contrato = c.id_contrato
      AND hs.activo = 1
  );

-- VERIFICACION: cantidad de filas de horario por contrato vigente
SELECT
  c.id_contrato,
  c.id_empleado,
  COUNT(hs.id_horario) AS horarios_activos
FROM contratos_empleado c
LEFT JOIN horarios_semanales hs
  ON hs.id_contrato = c.id_contrato
 AND hs.activo = 1
WHERE c.fecha_fin IS NULL
GROUP BY c.id_contrato, c.id_empleado
ORDER BY c.id_empleado;

COMMIT;

