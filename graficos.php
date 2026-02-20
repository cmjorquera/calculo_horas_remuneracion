<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/class/conexion.php";
require_once __DIR__ . "/class/funciones.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$funciones = new Funciones($db);
$idColegio = $_SESSION["id_colegio"];

// Obtener distribución de empleados por horas semanales
$query = "
    SELECT 
        ce.horas_semanales_cron,
        COUNT(e.id_empleado) as cantidad
    FROM empleados e
    INNER JOIN contratos_empleado ce ON e.id_empleado = ce.id_empleado
    WHERE e.id_colegio = $idColegio
    AND ce.fecha_fin IS NULL 
    GROUP BY ce.horas_semanales_cron
    ORDER BY ce.horas_semanales_cron ASC
";

$resultado = $db->consulta($query);

$horas = [];
$cantidades = [];

while ($row = $db->fetch_assoc($resultado)) {
    $horas[] = $row['horas_semanales_cron'];
    $cantidades[] = $row['cantidad'];
}

$dataJson = json_encode([
    'horas' => $horas,
    'cantidades' => $cantidades
]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gráficos - Distribución de Horas</title>
    <link rel="stylesheet" type="text/css" href="css/principal.css">
    <link rel="stylesheet" type="text/css" href="css/menu_lateral.css">
    <link rel="stylesheet" type="text/css" href="css/modales.css">
    <link rel="stylesheet" type="text/css" href="css/modales.css">
        <link rel="stylesheet" type="text/css" href="css/graficos.css">


    <link rel="icon" type="image/png" href="imagenes/logo_1.jpg" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><!-- SweetAlert2 -->
    <style>

    </style>
</head>
<body>
    <?php include 'menu_lateral.php'; ?>

    <div class="graficos-container">
        <h1 style="color: #111827; font-size: 24px; margin-bottom: 30px;">
             Distribución de Empleados por Horas Semanales
        </h1>

        <div class="grafico-box">
            <div class="grafico-titulo">Empleados por Horas Semanales</div>
            <div class="canvas-wrapper">
                <canvas id="horasChart"></canvas>
            </div>
            
            <div class="estadisticas" id="estadisticas">
                <!-- Se llena con JavaScript -->
            </div>
        </div>
    </div>

    <script>
        const data = <?= $dataJson; ?>;
        
        // Colores según horas
        const getColor = (horas) => {
            if (horas == 40) return '#30DB55'; // Verde - legal
            if (horas == 45) return '#FFB100'; // Amarillo - sobre horas
            if (horas > 40) return '#FF6B6B'; // Rojo - mucho extra
            return '#2563EB'; // Azul - menos valor
        };

        const colors = data.horas.map(h => getColor(h));

        // Gráfico de barras
        const ctx = document.getElementById('horasChart').getContext('2d');
        const horasChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.horas.map(h => h + ' horas'),
                datasets: [{
                    label: 'Cantidad de Empleados',
                    data: data.cantidades,
                    backgroundColor: colors,
                    borderColor: colors,
                    borderRadius: 8,
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Empleados: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: Math.max(...data.cantidades) + 2,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Generar estadísticas
        const estadisticasDiv = document.getElementById('estadisticas');
        data.horas.forEach((horas, idx) => {
            const cantidad = data.cantidades[idx];
            let etiqueta = horas + ' horas';
            
            if (horas == 40) etiqueta += ' (Legal)';
            else if (horas > 40) etiqueta += ' (Extra)';
            else etiqueta += ' (Reducida)';

            const div = document.createElement('div');
            div.className = 'estadistica-item';
            div.innerHTML = `
                <div class="estadistica-valor">${cantidad}</div>
                <div class="estadistica-label">${etiqueta}</div>
            `;
            estadisticasDiv.appendChild(div);
        });
    </script>
</body>
</html>
