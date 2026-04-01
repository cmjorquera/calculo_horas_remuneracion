/**
 * guardar_empleado.js
 * - Recolecta selects del horario (generados por JS en la tabla)
 * - Muestra SweetAlert2 para registrar empleado
 * - Envía (empleado + horario) por AJAX a modelos/guardar/empleado.php
 *
 * Requisitos:
 * - DIAS_LV definido desde PHP (con {label, prefix})
 * - SweetAlert2 cargado
 * - jQuery cargado (por $.ajax / $)
 */

/* =========================
   1) HORARIO: LECTURA SELECTS
   ========================= */

function getHora(prefix, bloque, tipo) {
  // name esperado: {prefix}_{man|tar}_{ini|fin}_{h|m}
  const hSel = document.querySelector(`select[name="${prefix}_${bloque}_${tipo}_h"]`);
  const mSel = document.querySelector(`select[name="${prefix}_${bloque}_${tipo}_m"]`);

  const h = hSel ? hSel.value : "00";
  const m = mSel ? mSel.value : "00";

  const hhmm = `${h}:${m}`;

  // Si quedó 00:00, lo tratamos como "no marcado"
  return hhmm === "00:00" ? "" : hhmm;
}

function recolectarHorario() {
  const horario = {};
  let tieneAlgo = false;

  if (!Array.isArray(DIAS_LV) || DIAS_LV.length === 0) {
    return { horario: {}, tieneAlgo: false };
  }

  DIAS_LV.forEach((d) => {
    const prefix = d.prefix;
    const label = d.label;

    const manIni = getHora(prefix, "man", "ini");
    const manFin = getHora(prefix, "man", "fin");
    const tarIni = getHora(prefix, "tar", "ini");
    const tarFin = getHora(prefix, "tar", "fin");

    if (manIni || manFin || tarIni || tarFin) {
      tieneAlgo = true;
    }

    horario[prefix] = {
      dia: label,
      manana: { inicio: manIni, termino: manFin },
      tarde: { inicio: tarIni, termino: tarFin },
    };
  });

  return { horario, tieneAlgo };
}

/* =========================
   2) (OPCIONAL) VALIDACIONES EXTRA DE CONSISTENCIA
   - Si tienes inicio, exige término (por bloque)
   - Si tienes término, exige inicio (por bloque)
   ========================= */

function validarBloque(nombreBloque, ini, fin, diaLabel) {
  // Ambos vacíos: OK
  if (!ini && !fin) return null;

  // Uno sí y otro no: error
  if (ini && !fin) return `En ${diaLabel} (${nombreBloque}) falta el término.`;
  if (!ini && fin) return `En ${diaLabel} (${nombreBloque}) falta el inicio.`;

  return null;
}

function validarHorarioConsistencia(horarioObj) {
  // Retorna string con error, o null si OK
  const dias = Object.keys(horarioObj);

  for (const p of dias) {
    const d = horarioObj[p];
    const e1 = validarBloque("mañana", d.manana.inicio, d.manana.termino, d.dia);
    if (e1) return e1;

    const e2 = validarBloque("tarde", d.tarde.inicio, d.tarde.termino, d.dia);
    if (e2) return e2;
  }

  return null;
}


function hhmmToMinutes(hhmm) {
  const s = String(hhmm || "").trim();
  if (!/^\d{1,2}:\d{2}$/.test(s)) return 0;
  const [h, m] = s.split(":").map(Number);
  return (h * 60) + m;
}

function sanitizePedNumberInput(value) {
  const raw = String(value || "");
  let out = "";
  let hasSep = false;

  for (const ch of raw) {
    if (/\d/.test(ch)) {
      out += ch;
      continue;
    }
    if ((ch === "." || ch === ",") && !hasSep) {
      out += ".";
      hasSep = true;
    }
  }

  return out;
}

function parsePedHours(value) {
  const s = sanitizePedNumberInput(value);
  if (!s) return 0;
  const n = Number(s);
  if (!Number.isFinite(n)) return 0;
  return Math.max(0, n);
}

function pedHoursToCronoMinutes(value) {
  return Math.round(parsePedHours(value) * 40);
}

function formatearRun(input) {
  let value = input.value;

  // Eliminar todo excepto números y K/k
  value = value.replace(/[^0-9kK]/g, "").toUpperCase();

  // Limitar largo máximo real (8 cuerpo + 1 dv)
  if (value.length > 9) {
    value = value.slice(0, 9);
  }

  if (value.length > 1) {
    let cuerpo = value.slice(0, -1);
    const dv = value.slice(-1);

    // Agregar puntos
    cuerpo = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

    value = cuerpo + "-" + dv;
  }

  input.value = value;
}

function recolectarResumen() {
  const colacionSelect = document.getElementById("sumColacionSelect");
  const jornadaCroHHMM = (document.getElementById("sumJornadaCro")?.textContent || "00:00").trim();
  const colacionMinTxt = (document.getElementById("sumColacionMin")?.textContent || "0").trim();
  const colacionOpt = colacionSelect?.selectedOptions?.[0] || null;

  if (typeof window.recalcularLectivas === "function") {
    window.recalcularLectivas();
  }
  if (typeof window.recalcularNoLectivas === "function") {
    window.recalcularNoLectivas();
  }
  if (typeof window.updateHorasLectivasUI === "function") {
    window.updateHorasLectivasUI();
  }

  const lectivasPedTxt = (document.getElementById("sumLectivasPed")?.value || "0").trim();
  const noLectivasPedTxt = (document.getElementById("sumNoLectivasPed")?.value || "0").trim();
  const lectivasCroHHMM = (document.getElementById("sumLectivasCro")?.value || "00:00").trim();
  const noLectivasCroHHMM = (document.getElementById("sumNoLectivasCro")?.value || "00:00").trim();

  let colacionMin = parseInt(colacionOpt?.dataset?.minutos || "", 10);
  if (!Number.isFinite(colacionMin)) {
    colacionMin = parseInt(colacionMinTxt, 10);
  }
  if (!Number.isFinite(colacionMin)) {
    colacionMin = 0;
  }

  return {
    colacionId: colacionSelect?.value || "",
    jornadaCroMinSemanal: hhmmToMinutes(jornadaCroHHMM),
    colacionMinDiaria: colacionMin,
    horasLectivasMin: pedHoursToCronoMinutes(lectivasPedTxt) || hhmmToMinutes(lectivasCroHHMM),
    horasNoLectivasMin: pedHoursToCronoMinutes(noLectivasPedTxt) || hhmmToMinutes(noLectivasCroHHMM)
  };
}

function contarDiasConHorario(horario) {
  let count = 0;
  for (const k in horario) {
    const d = horario[k];
    const man = d?.manana || {};
    const tar = d?.tarde || {};
    const tiene = !!(man.inicio || man.termino || tar.inicio || tar.termino);
    if (tiene) count++;
  }
  return count;
}

function validarResumenLectivo() {
  if (typeof window.getHorasLectivasState !== "function") {
    return { isValid: true, errors: [] };
  }
  const state = window.getHorasLectivasState();
  if (typeof window.updateHorasLectivasUI === "function") {
    window.updateHorasLectivasUI();
  }
  return state;
}







/* =========================
   3) FUNCIÓN PRINCIPAL: GUARDAR
   ========================= */

function guardarEmpleado() {
  const { horario, tieneAlgo } = recolectarHorario();
  const colacionSelect = document.getElementById("sumColacionSelect");
  const prefill = window.empleadoSeleccionadoPrefill || null;
  const normalizeRun = (v) => String(v || "").toUpperCase().replace(/[^0-9K]/g, "");
  const prefillRunNorm = normalizeRun(prefill?.run || "");
  const normalizeGeneroValue = (value) => {
    const raw = String(value || "").trim().toLowerCase();
    if (raw === "1" || raw === "hombre") return "1";
    if (raw === "2" || raw === "mujer") return "2";
    return "";
  };
  const runLookupState = {
    checking: false,
    exists: false,
    runNorm: "",
    fullName: "",
    ignorePrefilledExisting: !!prefillRunNorm
  };
  const emailLookupState = {
    checking: false,
    exists: false,
    emailNorm: "",
    fullName: ""
  };
  const colegiosEmpleadoRaw = typeof COLEGIOS_EMPLEADO !== "undefined" ? COLEGIOS_EMPLEADO : window.COLEGIOS_EMPLEADO;
  const colegiosEmpleado = Array.isArray(colegiosEmpleadoRaw) ? colegiosEmpleadoRaw : [];
  const colegiosLogoEmpleado = (typeof COLEGIOS_LOGO_EMPLEADO !== "undefined" ? COLEGIOS_LOGO_EMPLEADO : window.COLEGIOS_LOGO_EMPLEADO) || {};
  const esSuperAdminEmpleado = !!(typeof ES_SUPER_ADMIN_EMPLEADO !== "undefined" ? ES_SUPER_ADMIN_EMPLEADO : window.ES_SUPER_ADMIN_EMPLEADO);
  const colegioOptionsHtml = colegiosEmpleado.map((colegio) => {
    const idColegio = Number(colegio.id_colegio || 0);
    const nombreColegio = String(colegio.nco_colegio || colegio.nom_colegio || `Colegio ${idColegio}`).trim();
    return `<option value="${idColegio}">${nombreColegio.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;")}</option>`;
  }).join("");

  if (!tieneAlgo) {
    Swal.fire({
      icon: "warning",
      title: "Horario vacío",
      text: "Debes ingresar al menos un bloque horario antes de agregar.",
      customClass: {
        popup: "swal-seduc",
        confirmButton: "btn-seduc btn-seduc-primary"
      }
    });
    return;
  }

  if (!colacionSelect?.value) {
    Swal.fire({
      icon: "warning",
      title: "Colación requerida",
      text: "Debes seleccionar una colación antes de agregar.",
      customClass: {
        popup: "swal-seduc",
        confirmButton: "btn-seduc btn-seduc-primary"
      }
    });
    colacionSelect?.focus();
    return;
  }

  // 1) Validación opcional de consistencia (inicio/fin por bloque)
  const errConsistencia = validarHorarioConsistencia(horario);
  if (errConsistencia) {
    Swal.fire({
      icon: "warning",
      title: "Horario incompleto",
      text: errConsistencia,
      buttonsStyling: false,
      customClass: {
        popup: "swal-seduc",
        confirmButton: "btn-seduc btn-seduc-primary"
      }
    });
    return;
  }

  const resumenLectivo = validarResumenLectivo();
  if (!resumenLectivo.isValid) {
    Swal.fire({
      icon: "warning",
      title: "Horas lectivas inválidas",
      html: resumenLectivo.errors.join("<br>"),
      customClass: {
        popup: "swal-seduc",
        confirmButton: "btn-seduc btn-seduc-primary"
      }
    });
    return;
  }

  // 2) Modal para datos del empleado
  Swal.fire({
    title: "Registrar empleado",
    showCloseButton: true,
html: `
  <form class="user-create-form employee-create-form" id="employeeCreateForm">
    <div class="user-create-topbar">
      <div id="sw_colegio_preview" class="colegio-logo-chip is-hidden" aria-live="polite">
        <img id="sw_colegio_preview_img" class="colegio-logo-chip-img" src="" alt="">
        <div class="colegio-logo-chip-copy">
          <strong id="sw_colegio_preview_nombre">Colegio</strong>
          <span id="sw_colegio_preview_meta">ID colegio</span>
        </div>
      </div>
    </div>
    <div class="user-create-grid employee-create-grid">
      <label class="user-field">
        <span>Email</span>
        <input id="sw_email" type="email" maxlength="150" placeholder="correo@colegio.cl">
      </label>
      <label class="user-field">
        <span>Género</span>
        <select id="sw_genero">
          <option value="">Selecciona</option>
          <option value="1">Hombre</option>
          <option value="2">Mujer</option>
        </select>
      </label>
      <label class="user-field">
        <span>Nombre</span>
        <input id="sw_nombres" type="text" maxlength="80" placeholder="Nombre">
      </label>
      <label class="user-field">
        <span>Apellido paterno</span>
        <input id="sw_ap_paterno" type="text" maxlength="80" placeholder="Apellido paterno">
      </label>
      <label class="user-field">
        <span>Apellido materno</span>
        <input id="sw_ap_materno" type="text" maxlength="80" placeholder="Apellido materno">
      </label>
      <label id="sw_id_colegio_field" class="user-field${esSuperAdminEmpleado ? "" : " is-hidden"}">
        <span>Colegio</span>
        <select id="sw_id_colegio">
          <option value="">Selecciona</option>
          ${colegioOptionsHtml}
        </select>
      </label>
      <label class="user-field">
        <span>RUN</span>
        <input id="sw_run" type="text" maxlength="20" placeholder="12.345.678-9">
        <small id="sw_run_exists_msg" class="user-field-note is-hidden"></small>
      </label>
      <label class="user-field">
        <span>Teléfono</span>
        <input id="sw_telefono" type="text" maxlength="25" placeholder="Opcional">
      </label>
      <label class="user-field employee-observacion-field">
        <span>Observación contrato</span>
        <textarea id="sw_observacion" rows="5" placeholder="Ej: Contrato jornada completa, reemplazo, etc."></textarea>
      </label>
    </div>
  </form>
`,

    showCancelButton: true,
    confirmButtonText: "Guardar",
    cancelButtonText: "Cancelar",
    width: 860,
    focusConfirm: false,
            customClass: {
            popup: 'swal-seduc',
            confirmButton: 'btn-seduc btn-seduc-primary',
            cancelButton: 'btn-seduc btn-seduc-ghost'
        },
didOpen: () => {
  const nombresInput = document.getElementById("sw_nombres");
  const apPatInput = document.getElementById("sw_ap_paterno");
  const apMatInput = document.getElementById("sw_ap_materno");
  const runInput = document.getElementById("sw_run");
  const emailInput = document.getElementById("sw_email");
  const generoInput = document.getElementById("sw_genero");
  const observacionInput = document.getElementById("sw_observacion");
  const runMsg = document.getElementById("sw_run_exists_msg");
  const colegioInput = document.getElementById("sw_id_colegio");
  const colegioField = document.getElementById("sw_id_colegio_field");
  const colegioPreview = document.getElementById("sw_colegio_preview");
  const colegioPreviewImg = document.getElementById("sw_colegio_preview_img");
  const colegioPreviewNombre = document.getElementById("sw_colegio_preview_nombre");
  const colegioPreviewMeta = document.getElementById("sw_colegio_preview_meta");
  let emailMsg = document.getElementById("sw_email_exists_msg");
  if (!runInput || !runMsg) return;

  if (emailInput && !emailMsg) {
    emailMsg = document.createElement("small");
    emailMsg.id = "sw_email_exists_msg";
    emailMsg.className = "user-field-note is-hidden";
    emailInput.insertAdjacentElement("afterend", emailMsg);
  }

  function ensureColegioFieldState() {
    if (!colegioInput || !colegioField) return;

    const tieneOpcionesReales = colegioInput.options.length > 1;
    const debeMostrarColegio = !!esSuperAdminEmpleado && tieneOpcionesReales;
    colegioField.classList.toggle("is-hidden", !debeMostrarColegio);

    if (!debeMostrarColegio && colegioPreview) {
      colegioPreview.classList.add("is-hidden");
    }
  }

  function syncColegioPreview() {
    if (!colegioInput || !colegioPreview || !colegioPreviewImg || !colegioPreviewNombre || !colegioPreviewMeta) {
      return;
    }

    ensureColegioFieldState();
    if (colegioField?.classList.contains("is-hidden")) {
      return;
    }

    const idColegio = String(colegioInput.value || "");
    const selectedOption = colegioInput.options[colegioInput.selectedIndex];
    const nombreColegio = selectedOption ? selectedOption.textContent.trim() : "Colegio";
    const logoPath = colegiosLogoEmpleado[idColegio] || "";

    if (!idColegio) {
      colegioPreview.classList.add("is-hidden");
      colegioPreviewImg.removeAttribute("src");
      colegioPreviewImg.alt = "";
      return;
    }

    colegioPreviewNombre.textContent = nombreColegio;
    colegioPreviewMeta.textContent = `ID colegio: ${idColegio}`;

    if (logoPath) {
      colegioPreviewImg.src = logoPath;
      colegioPreviewImg.alt = `Logo de ${nombreColegio}`;
      colegioPreviewImg.style.display = "";
    } else {
      colegioPreviewImg.removeAttribute("src");
      colegioPreviewImg.alt = "";
      colegioPreviewImg.style.display = "none";
    }

    colegioPreview.classList.remove("is-hidden");
  }

  if (prefill) {
    if (nombresInput) nombresInput.value = String(prefill.nombres || "");
    if (apPatInput) apPatInput.value = String(prefill.ap_paterno || "");
    if (apMatInput) apMatInput.value = String(prefill.ap_materno || "");
    if (observacionInput) observacionInput.value = String(prefill.observacion || "");
    if (generoInput) generoInput.value = normalizeGeneroValue(prefill.genero);
    runInput.value = String(prefill.run || "");
    formatearRun(runInput);
    if (colegioInput) {
      colegioInput.value = String(prefill.id_colegio || "");
      colegioInput.disabled = Number(prefill.id_empleado || 0) > 0;
    }
  } else if (colegioInput) {
    colegioInput.value = "";
    colegioInput.disabled = false;
  }

  ensureColegioFieldState();
  syncColegioPreview();

  if (colegioInput) {
    colegioInput.addEventListener("change", syncColegioPreview);
  }

  let timer = null;

  function setMsg(text, color) {
    runMsg.textContent = text || "";
    runMsg.classList.remove("is-hidden", "is-error", "is-ok");

    if (!text) {
      runMsg.classList.add("is-hidden");
      runMsg.style.color = "";
      return;
    }

    if (color === "#166534") {
      runMsg.classList.add("is-ok");
      runMsg.style.color = "";
      return;
    }

    if (color === "#b91c1c" || color === "#92400e") {
      runMsg.classList.add("is-error");
      runMsg.style.color = "";
      return;
    }

    runMsg.style.color = color || "#6b7280";
  }

  function setEmailMsg(text, color) {
    if (!emailMsg) return;

    emailMsg.textContent = text || "";
    emailMsg.classList.remove("is-hidden", "is-error", "is-ok");
    if (emailInput) emailInput.classList.remove("is-invalid");

    if (!text) {
      emailMsg.classList.add("is-hidden");
      emailMsg.style.color = "";
      return;
    }

    if (color === "#166534") {
      emailMsg.classList.add("is-ok");
      emailMsg.style.color = "";
      return;
    }

    if (color === "#b91c1c" || color === "#92400e") {
      emailMsg.classList.add("is-error");
      emailMsg.style.color = "";
      if (emailInput) emailInput.classList.add("is-invalid");
      return;
    }

    emailMsg.style.color = color || "#6b7280";
  }

  async function buscarRun() {
    const runRaw = runInput.value.trim();
    const runNorm = normalizeRun(runRaw);

    if (!runNorm) {
      runLookupState.checking = false;
      runLookupState.exists = false;
      runLookupState.runNorm = "";
      runLookupState.fullName = "";
      setMsg("");
      return;
    }

    runLookupState.checking = true;
    runLookupState.exists = false;
    runLookupState.runNorm = runNorm;
    runLookupState.fullName = "";
    setMsg("Buscando RUN...", "#6b7280");

    try {
      const body = new URLSearchParams({ run: runRaw });
      const res = await fetch("modelos/rescatar/usuario.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body
      });
      const resp = await res.json();

      if (normalizeRun(runInput.value.trim()) !== runNorm) return;

      runLookupState.checking = false;
      if (resp && resp.ok && resp.exists) {
        runLookupState.exists = true;
        runLookupState.fullName = String(resp.nombre_completo || "").trim();
        const runTxt = String(resp.run || runRaw).trim();
        setMsg(`El RUN ${runTxt} existe y está asociado a ${runLookupState.fullName}.`, "#b91c1c");
        return;
      }

      runLookupState.exists = false;
      runLookupState.fullName = "";
      setMsg("RUN disponible.", "#166534");
    } catch (e) {
      runLookupState.checking = false;
      runLookupState.exists = false;
      runLookupState.fullName = "";
      setMsg("No se pudo validar el RUN en este momento.", "#92400e");
    }
  }

  let emailTimer = null;

  async function buscarEmail() {
    if (!emailInput) return;

    const emailRaw = emailInput.value.trim();
    const emailNorm = emailRaw.toLowerCase();

    if (!emailNorm) {
      emailLookupState.checking = false;
      emailLookupState.exists = false;
      emailLookupState.emailNorm = "";
      emailLookupState.fullName = "";
      setEmailMsg("");
      return;
    }

    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailNorm);
    if (!emailOk) {
      emailLookupState.checking = false;
      emailLookupState.exists = false;
      emailLookupState.emailNorm = emailNorm;
      emailLookupState.fullName = "";
      setEmailMsg("Email inválido.", "#92400e");
      return;
    }

    emailLookupState.checking = true;
    emailLookupState.exists = false;
    emailLookupState.emailNorm = emailNorm;
    emailLookupState.fullName = "";
    setEmailMsg("Buscando email...", "#6b7280");

    try {
      const res = await fetch(`modelos/rescatar/empleado_email.php?email=${encodeURIComponent(emailRaw)}`);
      const resp = await res.json();

      if ((emailInput.value || "").trim().toLowerCase() !== emailNorm) return;

      emailLookupState.checking = false;
      if (resp && resp.ok && resp.exists) {
        emailLookupState.exists = true;
        emailLookupState.fullName = String(resp.nombre_completo || "").trim();
        const emailTxt = String(resp.email || emailRaw).trim();
        setEmailMsg(`El email ${emailTxt} existe y está asociado a ${emailLookupState.fullName}.`, "#b91c1c");
        return;
      }

      emailLookupState.exists = false;
      emailLookupState.fullName = "";
      setEmailMsg("Email disponible.", "#166534");
    } catch (e) {
      emailLookupState.checking = false;
      emailLookupState.exists = false;
      emailLookupState.fullName = "";
      setEmailMsg("No se pudo validar el email en este momento.", "#92400e");
    }
  }

  runInput.addEventListener("input", () => {
    formatearRun(runInput);
    const currentNorm = normalizeRun(runInput.value.trim());
    runLookupState.ignorePrefilledExisting = !!prefillRunNorm && currentNorm === prefillRunNorm;
    if (runLookupState.ignorePrefilledExisting) {
      runLookupState.checking = false;
      runLookupState.exists = false;
      runLookupState.runNorm = currentNorm;
      runLookupState.fullName = "";
      setMsg("");
      if (timer) clearTimeout(timer);
      return;
    }
    if (timer) clearTimeout(timer);
    timer = setTimeout(buscarRun, 350);
  });

  runInput.addEventListener("blur", () => {
    formatearRun(runInput);
    const currentNorm = normalizeRun(runInput.value.trim());
    runLookupState.ignorePrefilledExisting = !!prefillRunNorm && currentNorm === prefillRunNorm;
    if (runLookupState.ignorePrefilledExisting) {
      runLookupState.checking = false;
      runLookupState.exists = false;
      runLookupState.runNorm = currentNorm;
      runLookupState.fullName = "";
      setMsg("");
      return;
    }
    buscarRun();
  });

  if (runInput.value.trim() && !runLookupState.ignorePrefilledExisting) {
    buscarRun();
  }

  if (emailInput) {
    emailInput.addEventListener("input", () => {
      if (emailTimer) clearTimeout(emailTimer);
      emailTimer = setTimeout(buscarEmail, 350);
    });

    emailInput.addEventListener("blur", () => {
      buscarEmail();
    });
  }
},

preConfirm: () => {
  const data = {
    nombres: document.getElementById("sw_nombres")?.value.trim() || "",
    ap_paterno: document.getElementById("sw_ap_paterno")?.value.trim() || "",
    ap_materno: document.getElementById("sw_ap_materno")?.value.trim() || "",
    run: document.getElementById("sw_run")?.value.trim() || "",
    email: document.getElementById("sw_email")?.value.trim() || "",
    telefono: document.getElementById("sw_telefono")?.value.trim() || "",
    genero: document.getElementById("sw_genero")?.value || "",
    observacion: document.getElementById("sw_observacion")?.value.trim() || "",
    id_colegio: document.getElementById("sw_id_colegio")?.value || ""
  };

  if (!data.nombres)    { Swal.showValidationMessage("Nombres es obligatorio."); return false; }
  if (!data.ap_paterno) { Swal.showValidationMessage("Apellido paterno es obligatorio."); return false; }
  if (!data.ap_materno) { Swal.showValidationMessage("Apellido materno es obligatorio."); return false; }
  if (!data.run)        { Swal.showValidationMessage("RUN es obligatorio."); return false; }
  if (!data.genero)     { Swal.showValidationMessage("Género es obligatorio."); return false; }
  if (esSuperAdminEmpleado && Number(prefill?.id_empleado || 0) <= 0 && !data.id_colegio) {
    Swal.showValidationMessage("Debes seleccionar un colegio.");
    return false;
  }

  if (runLookupState.checking) {
    Swal.showValidationMessage("Espera la validación del RUN.");
    return false;
  }

  if (emailLookupState.checking) {
    Swal.showValidationMessage("Espera la validación del email.");
    return false;
  }

  if (!runLookupState.ignorePrefilledExisting &&
      runLookupState.exists &&
      normalizeRun(data.run) === runLookupState.runNorm) {
    const nombre = runLookupState.fullName || "otro usuario";
    Swal.showValidationMessage(`El RUN ${data.run} ya existe y está asociado a ${nombre}.`);
    return false;
  }

  if (data.email) {
    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email);
    if (!emailOk) { Swal.showValidationMessage("Email inválido."); return false; }
    if (emailLookupState.exists && data.email.toLowerCase() === emailLookupState.emailNorm) {
      const nombre = emailLookupState.fullName || "otro funcionario";
      Swal.showValidationMessage(`El email ${data.email} ya existe y está asociado a ${nombre}.`);
      return false;
    }
  }

  return data;
}
,



  }).then((result) => {
    if (!result.isConfirmed) return;

    const empleado = result.value;

    // 3) Payload final: empleado + horario + resumen
    const resumenLectivo = validarResumenLectivo();
    if (!resumenLectivo.isValid) {
      Swal.fire({
        icon: "warning",
        title: "Horas lectivas inválidas",
        text: resumenLectivo.errors.join("\n"),
        showCloseButton: true,
        customClass: {
          popup: "swal-seduc",
          confirmButton: "btn-seduc btn-seduc-primary"
        }
      });
      return;
    }

    const resumen = recolectarResumen();
    if (!resumen.colacionId) {
      Swal.fire({
        icon: "warning",
        title: "Falta colación",
        text: "Selecciona una colación antes de guardar.",
        showCloseButton: true,
        customClass: {
          popup: "swal-seduc",
          confirmButton: "btn-seduc btn-seduc-primary"
        }
      });
      return;
    }

    const payload = {
      ...empleado,
      horario: JSON.stringify(horario),

      id_colacion: String(resumen.colacionId),
      horas_semanales_cron: String(resumen.jornadaCroMinSemanal),
      min_colacion_diaria: String(resumen.colacionMinDiaria),
      horas_lectivas: String(resumen.horasLectivasMin),
      horas_no_lectivas: String(resumen.horasNoLectivasMin)

    };

    const prefillEmpleadoId = Number(prefill?.id_empleado || 0);
    if (prefillEmpleadoId > 0) {
      payload.id_empleado = String(prefillEmpleadoId);
    }
    const prefillContratoId = Number(prefill?.id_contrato || 0);
    if (prefillContratoId > 0) {
      payload.id_contrato = String(prefillContratoId);
    }


    // 4) Enviar por AJAX
    fetch("modelos/guardar/empleado.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: new URLSearchParams(payload)
    })
    .then(res => res.json())
    .then(resp => {
     if (resp.ok) {
  const empleadoIdGuardado = Number(resp.id_empleado || payload.id_empleado || 0);
  const contratoIdGuardado = Number(resp.id_contrato || payload.id_contrato || 0);
  if (empleadoIdGuardado > 0) {
    sessionStorage.setItem("empleadoSeleccionadoId", String(empleadoIdGuardado));
  }
  if (contratoIdGuardado > 0) {
    sessionStorage.setItem("empleadoSeleccionadoContratoId", String(contratoIdGuardado));
  }
  Swal.fire({
    icon: "success",
    title: "OK",
    text: resp.msg,
    timer: 3000,
    showConfirmButton: false,
    showCloseButton: true,
    customClass: {
      popup: 'swal-seduc'
    }
  }).then(() => {
    location.reload(); // recarga la página
  });
} else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: resp.msg,
          showCloseButton: true,
          customClass: {
            popup: "swal-seduc",
            confirmButton: "btn-seduc btn-seduc-primary"
          }
        });
      }
    })
    .catch(err => {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error de servidor",
        showCloseButton: true,
        customClass: {
          popup: "swal-seduc",
          confirmButton: "btn-seduc btn-seduc-primary"
        }
      });
    });

  });
}
