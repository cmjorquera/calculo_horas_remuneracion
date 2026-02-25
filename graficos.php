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
$empleados = $funciones->obtenerEmpleadosConResumen($_SESSION["id_colegio"]);

function minToHHMM($min)
{
    $min = max(0, (int)$min);
    $h = floor($min / 60);
    $m = $min % 60;
    return str_pad((string)$h, 2, "0", STR_PAD_LEFT) . ":" . str_pad((string)$m, 2, "0", STR_PAD_LEFT);
}

$labels = [];
$jornadaData = [];
$lectivasData = [];
$noLectivasData = [];
$colacionMap = [];
$cumpleLegal = 0;
$bajoLegal = 0;
$totalJornadaMin = 0;

foreach ($empleados as $e) {
    $nombre = trim((string)($e["nombres"] ?? "") . " " . (string)($e["apellido_paterno"] ?? "") . " " . (string)($e["apellido_materno"] ?? ""));
    if ($nombre === "") {
        $nombre = "Empleado sin nombre";
    }

    $jornadaMin = (int)($e["horas_semanales_cron"] ?? 0);
    $lectivasMin = (int)($e["horas_lectivas"] ?? 0);
    $noLectivasMin = (int)($e["horas_no_lectivas"] ?? 0);
    $colacionMin = (int)($e["min_colacion_diaria"] ?? 0);

    $labels[] = $nombre;
    $jornadaData[] = $jornadaMin;
    $lectivasData[] = $lectivasMin;
    $noLectivasData[] = $noLectivasMin;
    $totalJornadaMin += $jornadaMin;

    if ($jornadaMin >= 2400) {
        $cumpleLegal++;
    } else {
        $bajoLegal++;
    }

    if (!isset($colacionMap[$colacionMin])) {
        $colacionMap[$colacionMin] = 0;
    }
    $colacionMap[$colacionMin]++;
}

ksort($colacionMap, SORT_NUMERIC);

$kpiTotal = count($empleados);
$kpiPromJornada = $kpiTotal > 0 ? (int)round($totalJornadaMin / $kpiTotal) : 0;

// Top 10 por jornada para no saturar gráfico horizontal
$topIndices = array_keys($jornadaData);
usort($topIndices, function ($a, $b) use ($jornadaData) {
    return $jornadaData[$b] <=> $jornadaData[$a];
});
$topIndices = array_slice($topIndices, 0, 10);
$topLabels = [];
$topJornada = [];
foreach ($topIndices as $idx) {
    $topLabels[] = $labels[$idx];
    $topJornada[] = $jornadaData[$idx];
}

$chartPayload = [
    "labels" => $labels,
    "jornada" => $jornadaData,
    "lectivas" => $lectivasData,
    "noLectivas" => $noLectivasData,
    "topLabels" => $topLabels,
    "topJornada" => $topJornada,
    "colacionLabels" => array_map(function ($k) {
        return $k . " min";
    }, array_keys($colacionMap)),
    "colacionValues" => array_values($colacionMap),
    "cumplimiento" => [$cumpleLegal, $bajoLegal]
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gráficos | Calculadora de Horas</title>
    <link rel="stylesheet" type="text/css" href="css/principal.css">
    <link rel="stylesheet" type="text/css" href="css/menu_lateral.css">
    <link rel="stylesheet" type="text/css" href="css/graficos.css">
    <link rel="icon" type="image/png" href="imagenes/logo_1.jpg" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<div class="page">
    <?php include __DIR__ . "/menu_lateral.php"; ?>

    <header class="header">
        <div class="brand">
            <div class="logo">
                <img src="imagenes/logo_2.jpg" alt="Logo" onerror="this.style.display='none'">
            </div>
            <div class="titles">
                <h1>Panel de Gráficos</h1>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?= htmlspecialchars($_SESSION["nombre_completo"]) ?></span>
                    <span class="sep">•</span>
                    <span><?= htmlspecialchars($_SESSION["nom_colegio"]) ?></span>
                </div>
            </div>
        </div>
        <div class="meta">
            <div class="chip">
                <span class="label">Fecha</span>
                <span class="value" id="uiFecha">--</span>
            </div>
            <div class="chip">
                <span class="label">Hora</span>
                <span class="value" id="uiHora">--</span>
            </div>
        </div>
    </header>

    <main class="content graficos-main">
        <section class="kpi-grid">
            <article class="kpi-card">
                <span class="kpi-label">Empleados</span>
                <span class="kpi-value"><?= (int)$kpiTotal ?></span>
            </article>
            <article class="kpi-card">
                <span class="kpi-label">Promedio jornada</span>
                <span class="kpi-value"><?= htmlspecialchars(minToHHMM($kpiPromJornada)) ?></span>
            </article>
            <article class="kpi-card">
                <span class="kpi-label">Cumplen 40h+</span>
                <span class="kpi-value"><?= (int)$cumpleLegal ?></span>
            </article>
            <article class="kpi-card">
                <span class="kpi-label">Bajo 40h</span>
                <span class="kpi-value"><?= (int)$bajoLegal ?></span>
            </article>
        </section>

        <section class="charts-grid">
            <article class="chart-card chart-g1">
                <h3>Top 10 jornada cronológica</h3>
                <div class="chart-wrap"><canvas id="chartTopJornada"></canvas></div>
            </article>
            <article class="chart-card chart-g2">
                <h3>Horas lectivas vs no lectivas</h3>
                <div class="chart-wrap"><canvas id="chartComposicion"></canvas></div>
            </article>
            <article class="chart-card chart-g3">
                <h3>Distribución colación diaria</h3>
                <div class="chart-wrap"><canvas id="chartColacion"></canvas></div>
            </article>
            <article class="chart-card chart-g4">
                <h3>Cumplimiento jornada legal</h3>
                <div class="chart-wrap"><canvas id="chartCumplimiento"></canvas></div>
            </article>
        </section>
    </main>
</div>

<script>
const CHART_DATA = <?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE); ?>;

function updateHeaderDateTime() {
    const now = new Date();
    const fecha = new Intl.DateTimeFormat("es-CL", {
        weekday: "long",
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    }).format(now);
    const hora = new Intl.DateTimeFormat("es-CL", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
    }).format(now);
    document.getElementById("uiFecha").textContent = fecha;
    document.getElementById("uiHora").textContent = hora;
}

function minutesToHHMM(v) {
    const total = Math.max(0, Number(v) || 0);
    const h = Math.floor(total / 60);
    const m = total % 60;
    return String(h).padStart(2, "0") + ":" + String(m).padStart(2, "0");
}

function createCharts() {
    new Chart(document.getElementById("chartTopJornada"), {
        type: "bar",
        data: {
            labels: CHART_DATA.topLabels,
            datasets: [{
                label: "Jornada",
                data: CHART_DATA.topJornada,
                backgroundColor: "#0E7490"
            }]
        },
        options: {
            indexAxis: "y",
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => " " + minutesToHHMM(ctx.parsed.x)
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        callback: (value) => minutesToHHMM(value)
                    }
                }
            }
        }
    });

    new Chart(document.getElementById("chartComposicion"), {
        type: "bar",
        data: {
            labels: CHART_DATA.labels,
            datasets: [
                { label: "Lectivas", data: CHART_DATA.lectivas, backgroundColor: "#2563EB" },
                { label: "No lectivas", data: CHART_DATA.noLectivas, backgroundColor: "#F59E0B" }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { display: false },
                y: {
                    ticks: { callback: (value) => minutesToHHMM(value) }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.dataset.label}: ${minutesToHHMM(ctx.parsed.y)}`
                    }
                }
            }
        }
    });

    new Chart(document.getElementById("chartColacion"), {
        type: "bar",
        data: {
            labels: CHART_DATA.colacionLabels,
            datasets: [{
                label: "Cantidad de empleados",
                data: CHART_DATA.colacionValues,
                backgroundColor: "#8B5CF6"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    new Chart(document.getElementById("chartCumplimiento"), {
        type: "doughnut",
        data: {
            labels: ["Cumple 40h+", "Bajo 40h"],
            datasets: [{
                data: CHART_DATA.cumplimiento,
                backgroundColor: ["#22C55E", "#EF4444"]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

updateHeaderDateTime();
setInterval(updateHeaderDateTime, 1000);
createCharts();
</script>
</body>
</html>
