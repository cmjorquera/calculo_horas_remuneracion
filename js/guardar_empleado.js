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

function recolectarResumen() {
  const colacionSelect = document.getElementById("sumColacionSelect");
  const jornadaCroHHMM = (document.getElementById("sumJornadaCro")?.textContent || "00:00").trim();
  const colacionMinTxt = (document.getElementById("sumColacionMin")?.textContent || "0").trim();
  const lectivasCroHHMM = (document.getElementById("sumLectivasCro")?.value || "00:00").trim();
  const noLectivasCroHHMM = (document.getElementById("sumNoLectivasCro")?.value || "00:00").trim();

  return {
    colacionId: colacionSelect?.value || "",
    jornadaCroMinSemanal: hhmmToMinutes(jornadaCroHHMM),
    colacionMinDiaria: parseInt(colacionMinTxt, 10) || 0,
    horasLectivasMin: hhmmToMinutes(lectivasCroHHMM),
    horasNoLectivasMin: hhmmToMinutes(noLectivasCroHHMM)
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







/* =========================
   3) FUNCIÓN PRINCIPAL: GUARDAR
   ========================= */

function guardarEmpleado() {
  const { horario } = recolectarHorario();
  const colacionSelect = document.getElementById("sumColacionSelect");

  if (!colacionSelect || !colacionSelect.value) {
    Swal.fire({
      icon: "warning",
      title: "Falta colación",
      text: "Debes seleccionar una hora de colación antes de guardar.",
      customClass: {
        popup: "swal-seduc",
        confirmButton: "btn-seduc btn-seduc-primary"
      }
    });
    return;
  }

  // 1) Validación opcional de consistencia (inicio/fin por bloque)
  const errConsistencia = validarHorarioConsistencia(horario);
  if (errConsistencia) {
    Swal.fire({
      icon: "warning",
      title: "Horario incompleto",
      text: errConsistencia,
    });
    return;
  }

  // 2) Modal para datos del empleado
  Swal.fire({
    title: "Registrar empleado + horario",
html: `
  <div class="swal-form-modern">
    <div class="swal-grid-2">

      <div class="swal-field">
        <label>Nombres</label>
        <input id="sw_nombres" class="swal-input-modern" placeholder="Ej: Juan">
      </div>

      <div class="swal-field">
        <label>Apellido paterno</label>
        <input id="sw_ap_paterno" class="swal-input-modern" placeholder="Ej: Pérez">
      </div>

      <div class="swal-field">
        <label>Apellido materno</label>
        <input id="sw_ap_materno" class="swal-input-modern" placeholder="Ej: Soto">
      </div>

      <div class="swal-field">
        <label>RUN</label>
        <input id="sw_run" class="swal-input-modern" placeholder="12.345.678-9">
      </div>

      <div class="swal-field">
        <label>Email</label>
        <input id="sw_email" class="swal-input-modern" placeholder="juan@seduc.cl">
      </div>

      <div class="swal-field">
        <label>Teléfono</label>
        <input id="sw_telefono" class="swal-input-modern" placeholder="+56 9 1234 5678">
      </div>
    </div>
    <div class="swal-field swal-full">
      <label>Género</label>
      <select id="sw_genero" class="swal-input-modern">
        <option value="">Selecciona...</option>
        <option value="1">Profesor</option>
        <option value="2">Profesora</option>
      </select>
    </div>
    <div class="swal-field swal-full">
  <label>Observación contrato</label>
  <textarea id="sw_observacion"
            class="swal-input-modern"
            rows="3"
            placeholder="Ej: Contrato jornada completa, reemplazo, etc."></textarea>
</div>

  </div>
`,

    showCancelButton: true,
    confirmButtonText: "Guardar",
    cancelButtonText: "Cancelar",
    focusConfirm: false,
            customClass: {
            popup: 'swal-seduc',
            confirmButton: 'btn-seduc btn-seduc-primary',
            cancelButton: 'btn-seduc btn-seduc-ghost'
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
    observacion: document.getElementById("sw_observacion")?.value.trim() || ""
  };

  if (!data.nombres)    { Swal.showValidationMessage("Nombres es obligatorio."); return false; }
  if (!data.ap_paterno) { Swal.showValidationMessage("Apellido paterno es obligatorio."); return false; }
  if (!data.ap_materno) { Swal.showValidationMessage("Apellido materno es obligatorio."); return false; }
  if (!data.run)        { Swal.showValidationMessage("RUN es obligatorio."); return false; }
  if (!data.email)      { Swal.showValidationMessage("Email es obligatorio."); return false; }
  if (!data.telefono)   { Swal.showValidationMessage("Teléfono es obligatorio."); return false; }
  if (!data.genero)     { Swal.showValidationMessage("Selecciona Género."); return false; }

  const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email);
  if (!emailOk) { Swal.showValidationMessage("Email inválido."); return false; }

  return data;
}
,



  }).then((result) => {
    if (!result.isConfirmed) return;

    const empleado = result.value;

    // 3) Payload final: empleado + horario + resumen
    const resumen = recolectarResumen();

    const payload = {
      ...empleado,
      horario: JSON.stringify(horario),

      id_colacion: String(resumen.colacionId),
      horas_semanales_cron: String(resumen.jornadaCroMinSemanal),
      min_colacion_diaria: String(resumen.colacionMinDiaria),
      horas_lectivas: String(resumen.horasLectivasMin),
      horas_no_lectivas: String(resumen.horasNoLectivasMin)

    };


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
  Swal.fire({
    icon: "success",
    title: "OK",
    text: resp.msg,
    customClass: {
      popup: 'swal-seduc',
      confirmButton: 'btn-seduc btn-seduc-primary'
    }
  }).then(() => {
    location.reload(); // 🔥 recarga la página
  });
} else {
        Swal.fire("Error", resp.msg, "error");
      }
    })
    .catch(err => {
      Swal.fire("Error", "Error de servidor", "error");
    });

  });
}
