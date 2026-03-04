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
$runs = [];
$jornadaData = [];
$lectivasData = [];
$noLectivasData = [];
$sobreLegal = 0;
$cumpleLegal = 0;
$bajoLegal = 0;
$igualLegal = 0;
$colacionData = [];
$employeeRecords = [];

foreach ($empleados as $e) {
    $nombre = trim((string)($e["nombres"] ?? "") . " " . (string)($e["apellido_paterno"] ?? "") . " " . (string)($e["apellido_materno"] ?? ""));
    if ($nombre === "") {
        $nombre = "Empleado sin nombre";
    }

    $run = trim((string)($e["run"] ?? ""));
    if ($run === "") {
        $run = "Sin RUN";
    }

    $jornadaMin = (int)($e["horas_semanales_cron"] ?? 0);
    $lectivasMin = (int)($e["horas_lectivas"] ?? 0);
    $noLectivasMin = (int)($e["horas_no_lectivas"] ?? 0);
    $colacionMin = (int)($e["min_colacion_diaria"] ?? 0);

    $labels[] = $nombre;
    $runs[] = $run;
    $jornadaData[] = $jornadaMin;
    $lectivasData[] = $lectivasMin;
    $noLectivasData[] = $noLectivasMin;
    $employeeRecords[] = [
        "nombre" => $nombre,
        "run" => $run,
        "jornada" => $jornadaMin,
        "lectivas" => $lectivasMin,
        "noLectivas" => $noLectivasMin,
        "colacion" => $colacionMin
    ];

    if ($jornadaMin > 2400) {
        $sobreLegal++;
        $cumpleLegal++;
    } elseif ($jornadaMin === 2400) {
        $igualLegal++;
        $cumpleLegal++;
    } else {
        $bajoLegal++;
    }

    $colacionData[] = $colacionMin;
}

$kpiTotal = count($empleados);
$visibleEmployees = 10;
$employeeRowHeight = 36;
$chartMinHeight = 220;
$chartViewportHeight = max($chartMinHeight, $visibleEmployees * $employeeRowHeight);
$chartTopHeight = max($chartViewportHeight, $kpiTotal * $employeeRowHeight);
$employeeColumnWidth = 120;
$chartComposicionViewportWidth = $visibleEmployees * $employeeColumnWidth;
$chartComposicionWidth = max($chartComposicionViewportWidth, $kpiTotal * $employeeColumnWidth);
$colacionColumnWidth = 110;
$chartColacionViewportWidth = $visibleEmployees * $colacionColumnWidth;
$chartColacionWidth = max($chartColacionViewportWidth, $kpiTotal * $colacionColumnWidth);
$rankingIndices = array_keys($jornadaData);
usort($rankingIndices, function ($a, $b) use ($jornadaData) {
    return $jornadaData[$b] <=> $jornadaData[$a];
});
$rankingLabels = [];
$rankingNames = [];
$rankingJornada = [];
$rankingColors = [];
foreach ($rankingIndices as $idx) {
    $rankingLabels[] = $runs[$idx];
    $rankingNames[] = $labels[$idx];
    $rankingJornada[] = $jornadaData[$idx];
    if ($jornadaData[$idx] > 2400) {
        $rankingColors[] = "#E8DCA3";
    } elseif ($jornadaData[$idx] === 2400) {
        $rankingColors[] = "#B9DEC4";
    } else {
        $rankingColors[] = "#E8B5B5";
    }
}
$topRankingLimit = 10;
$topRankingLabels = array_slice($rankingLabels, 0, $topRankingLimit);
$topRankingNames = array_slice($rankingNames, 0, $topRankingLimit);
$topRankingJornada = array_slice($rankingJornada, 0, $topRankingLimit);
$topRankingColors = array_slice($rankingColors, 0, $topRankingLimit);

$chartPayload = [
    "employees" => $employeeRecords,
    "labels" => $labels,
    "jornada" => $jornadaData,
    "rankingLabels" => $rankingLabels,
    "rankingNames" => $rankingNames,
    "rankingJornada" => $rankingJornada,
    "rankingColors" => $rankingColors,
    "topRankingLabels" => $topRankingLabels,
    "topRankingNames" => $topRankingNames,
    "topRankingJornada" => $topRankingJornada,
    "topRankingColors" => $topRankingColors,
    "lectivas" => $lectivasData,
    "noLectivas" => $noLectivasData,
    "chartViewportHeight" => $chartViewportHeight,
    "chartTopHeight" => $chartTopHeight,
    "chartMinHeight" => $chartMinHeight,
    "chartComposicionViewportWidth" => $chartComposicionViewportWidth,
    "chartComposicionWidth" => $chartComposicionWidth,
    "chartColacionViewportWidth" => $chartColacionViewportWidth,
    "chartColacionWidth" => $chartColacionWidth,
    "colacionLabels" => $labels,
    "colacionValues" => $colacionData,
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
    <link rel="stylesheet" type="text/css" href="css/modales.css">
    <link rel="stylesheet" type="text/css" href="css/graficos.css">
    <link rel="icon" type="image/png" href="imagenes/logo_1.jpg" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><!-- SweetAlert2 -->
    <script src="js/button.js"></script>

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
        <section class="chart-toolbar">
            <label class="chart-search" for="chartSearchInput">
                <i class="bi bi-search"></i>
                <input
                    id="chartSearchInput"
                    type="search"
                    placeholder="Buscar por nombre o RUN"
                    autocomplete="off"
                >
            </label>
            <button class="chart-search-clear" id="chartSearchClear" type="button">Limpiar</button>
        </section>

        <section class="kpi-grid">
            <article class="kpi-card kpi-empleados">
                <div class="kpi-top">
                    <span class="kpi-label">Empleados</span>
                    <span class="kpi-icon"><i class="bi bi-people"></i></span>
                </div>
                <span class="kpi-value" id="kpiTotal"><?= (int)$kpiTotal ?></span>
            </article>
            <article class="kpi-card kpi-promedio">
                <div class="kpi-top">
                    <span class="kpi-label">Sobre 40h</span>
                    <span class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></span>
                </div>
                <span class="kpi-value" id="kpiSobreLegal"><?= (int)$sobreLegal ?></span>
            </article>
            <article class="kpi-card kpi-cumplen">
                <div class="kpi-top">
                    <span class="kpi-label">40h exactas</span>
                    <span class="kpi-icon"><i class="bi bi-check-circle"></i></span>
                </div>
                <span class="kpi-value" id="kpiIgualLegal"><?= (int)$igualLegal ?></span>
            </article>
            <article class="kpi-card kpi-bajo">
                <div class="kpi-top">
                    <span class="kpi-label">Bajo 40h</span>
                    <span class="kpi-icon"><i class="bi bi-exclamation-circle"></i></span>
                </div>
                <span class="kpi-value" id="kpiBajoLegal"><?= (int)$bajoLegal ?></span>
            </article>
        </section>

        <section class="charts-grid">
            <article class="chart-card chart-g1">
                <h3>Jornada cronológica por empleado</h3>
                <div class="chart-wrap chart-wrap-scroll" style="height: <?= (int)$chartViewportHeight ?>px; overflow: hidden;">
                    <div class="chart-scroll-body" style="height: <?= (int)$chartViewportHeight ?>px; max-height: <?= (int)$chartViewportHeight ?>px; overflow-y: scroll; overflow-x: hidden; scrollbar-gutter: stable;">
                        <div class="chart-scroll-inner" id="chartTopJornadaInner" style="height: <?= (int)$chartTopHeight ?>px; min-height: <?= (int)$chartTopHeight ?>px;">
                            <canvas id="chartTopJornada"></canvas>
                        </div>
                    </div>
                </div>
            </article>
            <article class="chart-card chart-g2">
                <h3>Horas lectivas vs no lectivas</h3>
                <div class="chart-wrap chart-wrap-scroll-x" style="height: 360px; overflow: hidden;">
                    <div class="chart-scroll-body-x" style="width: 100%; height: 360px; overflow-x: scroll; overflow-y: hidden; scrollbar-gutter: stable;">
                        <div class="chart-scroll-inner-x" style="width: <?= (int)$chartComposicionWidth ?>px; min-width: <?= (int)$chartComposicionWidth ?>px; height: 326px;">
                            <canvas id="chartComposicion" width="<?= (int)$chartComposicionWidth ?>" style="width: <?= (int)$chartComposicionWidth ?>px !important; min-width: <?= (int)$chartComposicionWidth ?>px; height: 326px !important;"></canvas>
                        </div>
                    </div>
                </div>
            </article>
            <article class="chart-card chart-g3">
                <h3>Colación diaria por empleado</h3>
                <div class="chart-wrap chart-wrap-scroll-x chart-wrap-colacion" style="height: 360px; overflow: hidden;">
                    <div class="chart-scroll-body-x" style="width: 100%; height: 360px; overflow-x: scroll; overflow-y: hidden; scrollbar-gutter: stable;">
                        <div class="chart-scroll-inner-x" style="width: <?= (int)$chartColacionWidth ?>px; min-width: <?= (int)$chartColacionWidth ?>px; height: 326px;">
                            <canvas id="chartColacion" width="<?= (int)$chartColacionWidth ?>" style="width: <?= (int)$chartColacionWidth ?>px !important; min-width: <?= (int)$chartColacionWidth ?>px; height: 326px !important;"></canvas>
                        </div>
                    </div>
                </div>
            </article>
            <article class="chart-card chart-g4">
                <h3>Top 10 jornadas más altas</h3>
                <div class="chart-wrap"><canvas id="chartCumplimiento"></canvas></div>
            </article>
        </section>
    </main>
</div>

<script>
const CHART_DATA = <?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE); ?>;
const EMPLOYEES = Array.isArray(CHART_DATA.employees) ? CHART_DATA.employees : [];
const DEFAULT_VISIBLE_EMPLOYEES = 10;
const EMPLOYEE_ROW_HEIGHT = 36;
const TOP_CHART_MIN_HEIGHT = Number(CHART_DATA.chartMinHeight) || 220;
const EMPLOYEE_COLUMN_WIDTH = 120;
const COLACION_COLUMN_WIDTH = 110;

let chartTopJornada;
let chartComposicion;
let chartColacion;
let chartCumplimiento;

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

function getRankingColors(jornada) {
    if (jornada > 2400) return "#E8DCA3";
    if (jornada === 2400) return "#B9DEC4";
    return "#E8B5B5";
}

function getFilteredEmployees(term) {
    const normalizedTerm = (term || "").trim().toLocaleLowerCase("es-CL");
    if (!normalizedTerm) {
        return EMPLOYEES.slice();
    }

    return EMPLOYEES.filter((employee) => {
        const nombre = String(employee.nombre || "").toLocaleLowerCase("es-CL");
        const run = String(employee.run || "").toLocaleLowerCase("es-CL");
        return nombre.includes(normalizedTerm) || run.includes(normalizedTerm);
    });
}

function buildChartState(employees) {
    const labels = employees.map((employee) => employee.nombre || "Empleado sin nombre");
    const runs = employees.map((employee) => employee.run || "Sin RUN");
    const jornada = employees.map((employee) => Number(employee.jornada) || 0);
    const lectivas = employees.map((employee) => Number(employee.lectivas) || 0);
    const noLectivas = employees.map((employee) => Number(employee.noLectivas) || 0);
    const colacion = employees.map((employee) => Number(employee.colacion) || 0);
    const sortedEmployees = employees
        .slice()
        .sort((a, b) => (Number(b.jornada) || 0) - (Number(a.jornada) || 0));

    const rankingLabels = sortedEmployees.map((employee) => employee.run || "Sin RUN");
    const rankingNames = sortedEmployees.map((employee) => employee.nombre || "Empleado sin nombre");
    const rankingJornada = sortedEmployees.map((employee) => Number(employee.jornada) || 0);
    const rankingColors = rankingJornada.map(getRankingColors);

    const total = employees.length;
    const visibleEmployees = Math.max(1, Math.min(DEFAULT_VISIBLE_EMPLOYEES, total || 1));

    return {
        labels,
        runs,
        jornada,
        lectivas,
        noLectivas,
        colacion,
        rankingLabels,
        rankingNames,
        rankingJornada,
        rankingColors,
        topRankingLabels: rankingLabels.slice(0, 10),
        topRankingNames: rankingNames.slice(0, 10),
        topRankingJornada: rankingJornada.slice(0, 10),
        topRankingColors: rankingColors.slice(0, 10),
        chartViewportHeight: Math.max(TOP_CHART_MIN_HEIGHT, visibleEmployees * EMPLOYEE_ROW_HEIGHT),
        chartTopHeight: Math.max(
            Math.max(TOP_CHART_MIN_HEIGHT, visibleEmployees * EMPLOYEE_ROW_HEIGHT),
            total * EMPLOYEE_ROW_HEIGHT
        ),
        chartComposicionWidth: Math.max(visibleEmployees * EMPLOYEE_COLUMN_WIDTH, total * EMPLOYEE_COLUMN_WIDTH),
        chartColacionWidth: Math.max(visibleEmployees * COLACION_COLUMN_WIDTH, total * COLACION_COLUMN_WIDTH)
    };
}

function updateKpis(employees) {
    let sobreLegal = 0;
    let igualLegal = 0;
    let bajoLegal = 0;

    employees.forEach((employee) => {
        const jornada = Number(employee.jornada) || 0;
        if (jornada > 2400) {
            sobreLegal++;
        } else if (jornada === 2400) {
            igualLegal++;
        } else {
            bajoLegal++;
        }
    });

    document.getElementById("kpiTotal").textContent = String(employees.length);
    document.getElementById("kpiSobreLegal").textContent = String(sobreLegal);
    document.getElementById("kpiIgualLegal").textContent = String(igualLegal);
    document.getElementById("kpiBajoLegal").textContent = String(bajoLegal);
}

function updateChartContainers(state) {
    const topWrap = document.querySelector(".chart-g1 .chart-wrap-scroll");
    const topBody = document.querySelector(".chart-g1 .chart-scroll-body");
    const topInner = document.getElementById("chartTopJornadaInner");
    const composicionInner = document.querySelector(".chart-g2 .chart-scroll-inner-x");
    const composicionCanvas = document.getElementById("chartComposicion");
    const colacionInner = document.querySelector(".chart-g3 .chart-scroll-inner-x");
    const colacionCanvas = document.getElementById("chartColacion");

    topWrap.style.height = `${state.chartViewportHeight}px`;
    topBody.style.height = `${state.chartViewportHeight}px`;
    topBody.style.maxHeight = `${state.chartViewportHeight}px`;
    topInner.style.height = `${state.chartTopHeight}px`;
    topInner.style.minHeight = `${state.chartTopHeight}px`;

    composicionInner.style.width = `${state.chartComposicionWidth}px`;
    composicionInner.style.minWidth = `${state.chartComposicionWidth}px`;
    composicionCanvas.width = state.chartComposicionWidth;
    composicionCanvas.style.width = `${state.chartComposicionWidth}px`;
    composicionCanvas.style.minWidth = `${state.chartComposicionWidth}px`;

    colacionInner.style.width = `${state.chartColacionWidth}px`;
    colacionInner.style.minWidth = `${state.chartColacionWidth}px`;
    colacionCanvas.width = state.chartColacionWidth;
    colacionCanvas.style.width = `${state.chartColacionWidth}px`;
    colacionCanvas.style.minWidth = `${state.chartColacionWidth}px`;
}

function createCharts() {
    const initialState = buildChartState(EMPLOYEES);

    chartTopJornada = new Chart(document.getElementById("chartTopJornada"), {
        type: "bar",
        data: {
            labels: initialState.rankingNames,
            datasets: [{
                label: "Jornada",
                data: initialState.rankingJornada,
                backgroundColor: initialState.rankingColors,
                borderColor: initialState.rankingColors,
                borderWidth: 1
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
                        title: (items) => chartTopJornada.data.labels[items[0].dataIndex] || "",
                        label: (ctx) => ` RUN: ${chartTopJornada.data.datasets[0].runs?.[ctx.dataIndex] || "Sin RUN"} | Jornada: ${minutesToHHMM(ctx.parsed.x)}`
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
    chartTopJornada.data.datasets[0].runs = initialState.rankingLabels;

    chartComposicion = new Chart(document.getElementById("chartComposicion"), {
        type: "bar",
        data: {
            labels: initialState.labels,
            datasets: [
                { label: "Lectivas", data: initialState.lectivas, backgroundColor: "#2563EB" },
                { label: "No lectivas", data: initialState.noLectivas, backgroundColor: "#F59E0B" }
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

    chartColacion = new Chart(document.getElementById("chartColacion"), {
        type: "bar",
        data: {
            labels: initialState.labels,
            datasets: [{
                label: "Colación diaria",
                data: initialState.colacion,
                backgroundColor: "#335C67",
                borderColor: "#26474F",
                borderWidth: 1,
                borderRadius: 8,
                maxBarThickness: 44
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { display: false },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => minutesToHHMM(value)
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.dataset.label}: ${minutesToHHMM(ctx.parsed.y)}`
                    }
                }
            }
        }
    });

    chartCumplimiento = new Chart(document.getElementById("chartCumplimiento"), {
        type: "bar",
        data: {
            labels: initialState.topRankingLabels,
            datasets: [{
                label: "Jornada",
                data: initialState.topRankingJornada,
                backgroundColor: initialState.topRankingColors,
                borderColor: initialState.topRankingColors,
                borderWidth: 1,
                borderRadius: 8,
                maxBarThickness: 26
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: "y",
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => chartCumplimiento.data.datasets[0].names?.[items[0].dataIndex] || "",
                        label: (ctx) => ` RUN: ${ctx.label} | Jornada: ${minutesToHHMM(ctx.parsed.x)}`
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
    chartCumplimiento.data.datasets[0].names = initialState.topRankingNames;

    updateChartContainers(initialState);
    updateKpis(EMPLOYEES);
}

function applyChartFilter(term) {
    const filteredEmployees = getFilteredEmployees(term);
    const state = buildChartState(filteredEmployees);

    updateChartContainers(state);
    updateKpis(filteredEmployees);

    chartTopJornada.data.labels = state.rankingNames;
    chartTopJornada.data.datasets[0].data = state.rankingJornada;
    chartTopJornada.data.datasets[0].backgroundColor = state.rankingColors;
    chartTopJornada.data.datasets[0].borderColor = state.rankingColors;
    chartTopJornada.data.datasets[0].runs = state.rankingLabels;
    chartTopJornada.update();

    chartComposicion.data.labels = state.labels;
    chartComposicion.data.datasets[0].data = state.lectivas;
    chartComposicion.data.datasets[1].data = state.noLectivas;
    chartComposicion.update();

    chartColacion.data.labels = state.labels;
    chartColacion.data.datasets[0].data = state.colacion;
    chartColacion.update();

    chartCumplimiento.data.labels = state.topRankingLabels;
    chartCumplimiento.data.datasets[0].data = state.topRankingJornada;
    chartCumplimiento.data.datasets[0].backgroundColor = state.topRankingColors;
    chartCumplimiento.data.datasets[0].borderColor = state.topRankingColors;
    chartCumplimiento.data.datasets[0].names = state.topRankingNames;
    chartCumplimiento.update();
}

function bindChartSearch() {
    const input = document.getElementById("chartSearchInput");
    const clearButton = document.getElementById("chartSearchClear");

    if (!input || !clearButton) {
        return;
    }

    input.addEventListener("input", () => {
        applyChartFilter(input.value);
    });

    clearButton.addEventListener("click", () => {
        input.value = "";
        applyChartFilter("");
        input.focus();
    });
}

updateHeaderDateTime();
setInterval(updateHeaderDateTime, 1000);
createCharts();
bindChartSearch();
</script>
</body>
</html>
