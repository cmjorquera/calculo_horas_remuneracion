<?php
$headerTitle = isset($headerTitle) ? (string)$headerTitle : "Calculadora de Horas Pedagógicas y Cronológicas";
$nombreColegioHeader = trim((string)($_SESSION["nom_colegio"] ?? $_SESSION["nco_colegio"] ?? "Sin colegio"));
$idColegioHeader = (int)($_SESSION["id_colegio"] ?? 0);
$logoHeader = "imagenes/logo_2.jpg";
foreach (["png", "jpg", "jpeg", "webp"] as $extLogoHeader) {
    $logoRelHeader = "imagenes/colegios/colegio_" . $idColegioHeader . "." . $extLogoHeader;
    $logoAbsHeader = __DIR__ . "/" . $logoRelHeader;
    if ($idColegioHeader > 0 && is_file($logoAbsHeader)) {
        $logoHeader = $logoRelHeader;
        break;
    }
}
?>
<header class="header">
    <div class="brand">
        <div class="logo">
            <img src="<?= htmlspecialchars($logoHeader) ?>" alt="Logo del colegio" onerror="this.src='imagenes/logo_2.jpg'">
        </div>
        <div class="titles">
            <h1><?= htmlspecialchars($headerTitle) ?></h1>
            <div class="user-info">
                <i class="bi bi-person-circle"></i>
                <span><?= htmlspecialchars($_SESSION["nombre_completo"]) ?></span>
                <span class="sep">•</span>
                <span><?= htmlspecialchars($nombreColegioHeader) ?></span>
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
