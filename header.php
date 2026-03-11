<?php
$headerTitle = isset($headerTitle) ? (string)$headerTitle : "Calculadora de Horas Cronológicas";
?>
<header class="header">
    <div class="brand">
        <div class="logo">
            <img src="imagenes/logo_2.jpg" alt="Logo" onerror="this.style.display='none'">
        </div>
        <div class="titles">
            <h1><?= htmlspecialchars($headerTitle) ?></h1>
            <div class="user-info">
                <i class="bi bi-person-circle"></i>
                <span><?= htmlspecialchars($_SESSION["nombre_completo"]) ?></span>
                <span class="sep">•</span>
                <span><?= htmlspecialchars($_SESSION["cabecera_contexto"] ?? ($_SESSION["nom_colegio"] ?? "Sin colegio")) ?></span>
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
