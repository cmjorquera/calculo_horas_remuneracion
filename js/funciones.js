/* js/funciones.js
   Auto-copiar hacia abajo (mismo campo) desde el día editado a los días siguientes.
   Requiere que tus <select> tengan name tipo:
   lun_man_ini_h, lun_man_ini_m, lun_tar_fin_h, etc.
*/

(function (global) {

  // Detecta names como: mie_man_ini_h / mie_man_ini_m / vie_tar_fin_h ...
  const NAME_RE = /^([a-z]{3})_(man|tar)_(ini|fin)_(h|m)$/;

  /**
   * Activa el auto-fill hacia abajo.
   * @param {Object} opts
   * @param {string} opts.tbodySelector  Ej: "#tbodyHorario"
   * @param {string[]} opts.dayPrefixes  Ej: ["lun","mar","mie","jue","vie"]
   * @param {boolean} [opts.onlyIfEmpty] true = solo copia si el destino está "00"
   * @param {Function} [opts.onAfterApply] callback opcional
   */
  function bindAutoFillHorario(opts) {
    const tbody = document.querySelector(opts.tbodySelector || "#tbodyHorario");
    const dayPrefixes = Array.isArray(opts.dayPrefixes) ? opts.dayPrefixes : [];
    const onlyIfEmpty = !!opts.onlyIfEmpty;
    const isEnabled = (typeof opts.isEnabled === "function") ? opts.isEnabled : () => true;

    if (!tbody || dayPrefixes.length === 0) return;

    tbody.addEventListener("change", function (ev) {
      if (!isEnabled()) return;

      const sel = ev.target.closest("select");
      if (!sel || !sel.name) return;

      const match = sel.name.match(NAME_RE);
      if (!match) return;

      const fromPrefix = match[1]; // lun/mar/mie/jue/vie
      const bloque     = match[2]; // man/tar
      const tipo       = match[3]; // ini/fin
      const hm         = match[4]; // h/m

      const fromIdx = dayPrefixes.indexOf(fromPrefix);
      if (fromIdx === -1) return;

      const newVal = sel.value;

      // Copia hacia abajo (días posteriores)
      for (let i = fromIdx + 1; i < dayPrefixes.length; i++) {
        const toName = `${dayPrefixes[i]}_${bloque}_${tipo}_${hm}`;
        const target = tbody.querySelector(`select[name="${toName}"]`);
        if (!target) continue;
        if (target.disabled) continue;

        if (onlyIfEmpty && String(target.value) !== "00") continue;

        target.value = newVal;

        // Si quieres disparar "change" para recalcular totales automáticamente:
        // target.dispatchEvent(new Event("change", { bubbles: true }));
      }

      if (typeof opts.onAfterApply === "function") {
        opts.onAfterApply({ fromPrefix, bloque, tipo, hm, value: newVal });
      }
    });
  }

  // Exponer al global (window)
  global.bindAutoFillHorario = bindAutoFillHorario;

})(window);


/* ==========================================================
   CÁLCULOS: Jornada ordinaria (Lun a Vie)
   - Jornada diaria  = (mañana_fin - mañana_ini) + (tarde_fin - tarde_ini)
   - Totales semanales: suma de Lun..Vie
   - Colación se define por selección fija (no por diferencia horaria)
   ========================================================== */

(function (global) {

  // ===== Helpers =====
  function pad2(n) { return String(n).padStart(2, "0"); }

  function hhmmToMinutes(hh, mm) {
    const h = parseInt(hh, 10) || 0;
    const m = parseInt(mm, 10) || 0;
    return h * 60 + m;
  }

  function minutesToHHMM(totalMin) {
    totalMin = Math.max(0, Math.round(totalMin));
    const h = Math.floor(totalMin / 60);
    const m = totalMin % 60;
    return `${pad2(h)}:${pad2(m)}`;
  }

  function isZeroTime(hh, mm) {
    return (String(hh) === "00" && String(mm) === "00");
  }

  // Lee un tiempo desde los <select> por name:  lun_man_ini_h / lun_man_ini_m ...
  function getTimeFromSelects(tbody, prefix, bloque, tipo) {
    const selH = tbody.querySelector(`select[name="${prefix}_${bloque}_${tipo}_h"]`);
    const selM = tbody.querySelector(`select[name="${prefix}_${bloque}_${tipo}_m"]`);
    if (!selH || !selM) return null;

    const hh = selH.value;
    const mm = selM.value;

    // Si está en 00:00 lo consideramos "no marcado"
    if (isZeroTime(hh, mm)) return null;

    return { hh, mm, min: hhmmToMinutes(hh, mm) };
  }

  function diffMinutes(t1, t2) {
    // t2 - t1 (minutos), nunca negativo
    if (!t1 || !t2) return 0;
    return Math.max(0, t2.min - t1.min);
  }

  // ===== Cálculo por día =====
  function calcDayJornada(tbody, prefix) {
    const manIni = getTimeFromSelects(tbody, prefix, "man", "ini");
    const manFin = getTimeFromSelects(tbody, prefix, "man", "fin");
    const tarIni = getTimeFromSelects(tbody, prefix, "tar", "ini");
    const tarFin = getTimeFromSelects(tbody, prefix, "tar", "fin");

    const durMan = diffMinutes(manIni, manFin);
    const durTar = diffMinutes(tarIni, tarFin);

    // Jornada = suma de bloques trabajados (NO incluye colación)
    const jornada = durMan + durTar;
    return jornada;
  }

  // ===== Cálculo semana =====
  function calcWeekJornada(tbody, dayPrefixes) {
    let jornadaTotal = 0;

    for (const p of dayPrefixes) {
      jornadaTotal += calcDayJornada(tbody, p);
    }

    return jornadaTotal;
  }

  // ===== Pintar resumen =====
  function updateResumenJornadaUI(jornadaTotalMin) {
    const elJorCro = document.getElementById("sumJornadaCro");

    if (elJorCro) elJorCro.textContent = minutesToHHMM(jornadaTotalMin);
  }

  /**
   * Activa el recálculo automático al cambiar cualquier select del horario.
   * @param {Object} opts
   * @param {string} opts.tbodySelector
   * @param {string[]} opts.dayPrefixes  ["lun","mar","mie","jue","vie"]
   */
  function bindRecalculoHorario(opts) {
    const tbody = document.querySelector(opts.tbodySelector || "#tbodyHorario");
    const dayPrefixes = Array.isArray(opts.dayPrefixes) ? opts.dayPrefixes : [];
    if (!tbody || dayPrefixes.length === 0) return;

    // recalcula y pinta
    function recompute() {
      const jornadaTotal = calcWeekJornada(tbody, dayPrefixes);
      updateResumenJornadaUI(jornadaTotal);
    }

    // 1) Inicial
    recompute();

    // 2) Cada cambio
    tbody.addEventListener("change", function () {
      recompute();
    });
  }

  function bindColacionFija() {
    const select = document.getElementById("sumColacionSelect");
    const elColMin = document.getElementById("sumColacionMin");
    const elColHH = document.getElementById("sumColacionHHMM");
    if (!select || !elColMin) return;

    function updateFromSelect() {
      if (!select.value) {
        elColMin.textContent = "0";
        if (elColHH) {
          elColHH.textContent = minutesToHHMM(0);
        }
        return;
      }

      const selected = select.options[select.selectedIndex];
      const minutosAttr = selected ? selected.getAttribute("data-minutos") : null;
      const minutos = parseInt(minutosAttr, 10) || 0;
      elColMin.textContent = String(Math.max(0, minutos));
      if (elColHH) {
        elColHH.textContent = minutesToHHMM(minutos);
      }
    }

    updateFromSelect();
    select.addEventListener("change", updateFromSelect);
  }

  // Exponer
  global.bindRecalculoHorario = bindRecalculoHorario;
  global.bindColacionFija = bindColacionFija;

})(window);




