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

  function normalizePedNumber(value) {
    const n = parsePedHours(value);
    if (n === 0) return "0";
    return String(Math.round(n * 100) / 100);
  }

  function pedHoursToCronoHHMM(pedHours) {
    const minCrono = Math.round(Math.max(0, Number(pedHours) || 0) * 40);
    return formatHHMM(minCrono);
  }

  function recalcularLectivas() {
    const pedEl = document.getElementById("sumLectivasPed");
    const croEl = document.getElementById("sumLectivasCro");
    if (!pedEl || !croEl) return;

    const pedHours = parsePedHours(pedEl.value);
    croEl.value = pedHoursToCronoHHMM(pedHours);
    recalcularNoLectivas();
  }

  function recalcularNoLectivas() {
    const jornadaCroEl = document.getElementById("sumJornadaCro");
    const lectivasCroEl = document.getElementById("sumLectivasCro");
    const noLectivasCroEl = document.getElementById("sumNoLectivasCro");

    if (!jornadaCroEl || !lectivasCroEl || !noLectivasCroEl) return;

    const jornadaMin = parseHHMM(jornadaCroEl.textContent);
    const lectivasMin = parseHHMM(lectivasCroEl.value);

    // Regla pedida:
    // - Si lectivas cronológicas está vacío o en 00:00, repetir jornada ordinaria cronológica.
    // - Si tiene valor, calcular No lectivas = Jornada - Lectivas.
    const noLectivasMin = lectivasMin <= 0 ? jornadaMin : Math.max(0, jornadaMin - lectivasMin);

    noLectivasCroEl.value = formatHHMM(noLectivasMin);
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

  function attachPedNumberValidation(inputEl) {
    if (!inputEl) return;

    inputEl.addEventListener("input", function () {
      const next = sanitizePedNumberInput(inputEl.value);
      if (inputEl.value !== next) {
        inputEl.value = next;
      }
    });

    inputEl.addEventListener("blur", function () {
      inputEl.value = normalizePedNumber(inputEl.value);
    });
  }

  function bindAutoCalc() {
    const lectivasPedEl = document.getElementById("sumLectivasPed");
    const lectivasCroEl = document.getElementById("sumLectivasCro");
    const jornadaCroEl = document.getElementById("sumJornadaCro");

    if (!lectivasPedEl) return;

    if (lectivasCroEl) {
      lectivasCroEl.setAttribute("readonly", "readonly");
      attachTimeValidation(lectivasCroEl);
    }

    attachPedNumberValidation(lectivasPedEl);
    const noLectivasCroEl = document.getElementById("sumNoLectivasCro");
    if (noLectivasCroEl) {
      noLectivasCroEl.setAttribute("readonly", "readonly");
      attachTimeValidation(noLectivasCroEl);
    }

    const noLectivasPedEl = document.getElementById("sumNoLectivasPed");
    if (noLectivasPedEl) {
      attachPedNumberValidation(noLectivasPedEl);
    }

    lectivasPedEl.addEventListener("input", function () {
      recalcularLectivas();
    });

    lectivasPedEl.addEventListener("blur", function () {
      recalcularLectivas();
    });

    if (jornadaCroEl && typeof MutationObserver !== "undefined") {
      const observer = new MutationObserver(recalcularNoLectivas);
      observer.observe(jornadaCroEl, { childList: true, characterData: true, subtree: true });
    }

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
