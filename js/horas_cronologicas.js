(function (global) {
  function pad2(n) {
    return String(Math.max(0, n)).padStart(2, "0");
  }

  function parseHHMM(str) {
    const raw = String(str || "").trim();
    if (!raw) return 0;

    const match = raw.match(/^(\d{1,3})(?::(\d{1,2}))?$/);
    if (!match) return 0;

    const h = parseInt(match[1], 10);
    const m = match[2] != null ? parseInt(match[2], 10) : 0;
    if (!Number.isFinite(h) || !Number.isFinite(m) || m < 0 || m > 59) return 0;

    return (h * 60) + m;
  }

  function formatHHMM(min) {
    const total = Math.max(0, Math.round(Number(min) || 0));
    const h = Math.floor(total / 60);
    const m = total % 60;
    return `${pad2(h)}:${pad2(m)}`;
  }

  function pedToCronoHHMM(pedHHMM) {
    const minPed = parseHHMM(pedHHMM);
    const minCrono = Math.round(minPed * (40 / 60));
    return formatHHMM(minCrono);
  }

  function cronoToPedHHMM(cronoHHMM) {
    const minCrono = parseHHMM(cronoHHMM);
    const minPed = Math.round(minCrono * (60 / 40));
    return formatHHMM(minPed);
  }

  function sanitizeInputValue(value) {
    const cleaned = String(value || "").replace(/[^\d:]/g, "");
    const parts = cleaned.split(":");

    let h = parts[0] || "";
    let m = parts[1] || "";

    h = h.slice(0, 3);
    m = m.slice(0, 2);

    if (parts.length > 1) return `${h}:${m}`;
    return h;
  }

  function normalizeToHHMM(value) {
    const min = parseHHMM(value);
    return formatHHMM(min);
  }

  function recalcularLectivas() {
    const pedEl = document.getElementById("sumLectivasPed");
    const croEl = document.getElementById("sumLectivasCro");
    if (!pedEl || !croEl) return;

    croEl.value = pedToCronoHHMM(pedEl.value);
  }

  function recalcularNoLectivas() {
    const noLectivasPedEl = document.getElementById("sumNoLectivasPed");
    const noLectivasCroEl = document.getElementById("sumNoLectivasCro");

    if (!noLectivasPedEl || !noLectivasCroEl) return;
    noLectivasPedEl.value = cronoToPedHHMM(noLectivasCroEl.value);
  }

  function attachTimeValidation(inputEl) {
    if (!inputEl) return;

    inputEl.addEventListener("input", function () {
      const next = sanitizeInputValue(inputEl.value);
      if (inputEl.value !== next) {
        inputEl.value = next;
      }
    });

    inputEl.addEventListener("blur", function () {
      inputEl.value = normalizeToHHMM(inputEl.value);
    });
  }

  function bindAutoCalc() {
    const lectivasPedEl = document.getElementById("sumLectivasPed");
    const lectivasCroEl = document.getElementById("sumLectivasCro");

    if (!lectivasPedEl) return;

    if (lectivasCroEl) {
      lectivasCroEl.setAttribute("readonly", "readonly");
      attachTimeValidation(lectivasCroEl);
    }

    attachTimeValidation(lectivasPedEl);
    const noLectivasCroEl = document.getElementById("sumNoLectivasCro");
    if (noLectivasCroEl) {
      attachTimeValidation(noLectivasCroEl);
      noLectivasCroEl.addEventListener("input", recalcularNoLectivas);
      noLectivasCroEl.addEventListener("blur", recalcularNoLectivas);
    }

    lectivasPedEl.addEventListener("input", function () {
      recalcularLectivas();
    });

    lectivasPedEl.addEventListener("blur", function () {
      recalcularLectivas();
    });

    recalcularLectivas();
    recalcularNoLectivas();
  }

  global.parseHHMM = parseHHMM;
  global.formatHHMM = formatHHMM;
  global.pedToCronoHHMM = pedToCronoHHMM;
  global.cronoToPedHHMM = cronoToPedHHMM;
  global.recalcularLectivas = recalcularLectivas;
  global.recalcularNoLectivas = recalcularNoLectivas;
  global.attachTimeValidation = attachTimeValidation;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bindAutoCalc);
  } else {
    bindAutoCalc();
  }
})(window);
