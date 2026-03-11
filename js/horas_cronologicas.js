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

  function formatPedHoursValue(value) {
    const n = Math.max(0, Math.round((Number(value) || 0) * 100) / 100);
    if (n === 0) return "0";
    return String(n);
  }

  function normalizePedNumber(value) {
    const n = parsePedHours(value);
    return formatPedHoursValue(n);
  }

  function pedHoursToCronoHHMM(pedHours) {
    const minCrono = Math.round(Math.max(0, Number(pedHours) || 0) * 40);
    return formatHHMM(minCrono);
  }

  function getJornadaCronoMinutes() {
    const jornadaCroEl = document.getElementById("sumJornadaCro");
    if (!jornadaCroEl) return 0;
    return parseHHMM(jornadaCroEl.textContent || jornadaCroEl.value || "00:00");
  }

  function buzzInvalidInput(inputEl, shouldBuzz) {
    if (!inputEl) return;

    const wasInvalid = inputEl.dataset.wasInvalid === "1";
    if (!shouldBuzz) {
      inputEl.dataset.wasInvalid = "0";
      inputEl.classList.remove("is-buzzing");
      return;
    }

    if (wasInvalid) return;

    inputEl.dataset.wasInvalid = "1";
    inputEl.classList.remove("is-buzzing");
    void inputEl.offsetWidth;
    inputEl.classList.add("is-buzzing");
  }

  function enforcePedLimit(currentEl, otherEl) {
    if (!currentEl) return 0;

    const limitePed = 54;
    const otherValue = parsePedHours(otherEl ? otherEl.value : "0");
    const rawValue = parsePedHours(currentEl.value);
    const maxForCurrent = Math.max(0, Math.min(limitePed, limitePed - otherValue));
    const clampedValue = Math.min(rawValue, maxForCurrent);
    const exceeded = rawValue > maxForCurrent;

    if (exceeded) {
      currentEl.value = formatPedHoursValue(clampedValue);
      currentEl.classList.add("is-invalid");
      buzzInvalidInput(currentEl, true);
    } else {
      buzzInvalidInput(currentEl, false);
    }

    return clampedValue;
  }

  function getHorasLectivasState() {
    const lectivasPedEl = document.getElementById("sumLectivasPed");
    const noLectivasPedEl = document.getElementById("sumNoLectivasPed");
    const lectivasPed = parsePedHours(lectivasPedEl ? lectivasPedEl.value : "0");
    const noLectivasPed = parsePedHours(noLectivasPedEl ? noLectivasPedEl.value : "0");
    const totalPed = lectivasPed + noLectivasPed;
    const limitePed = 54;
    const errors = [];

    if (lectivasPed > limitePed) {
      errors.push("Las horas lectivas no pueden ser mayores a 54.");
    }
    if (noLectivasPed > limitePed) {
      errors.push("Las horas no lectivas no pueden ser mayores a 54.");
    }
    if (totalPed > limitePed) {
      errors.push("La suma de horas lectivas y no lectivas no puede ser mayor a 54.");
    }

    return {
      lectivasPed,
      noLectivasPed,
      totalPed,
      limitePed,
      errors,
      isValid: errors.length === 0
    };
  }

  function updateHorasLectivasUI() {
    const msgEl = document.getElementById("horasLectivasMsg");
    const lectivasPedEl = document.getElementById("sumLectivasPed");
    const noLectivasPedEl = document.getElementById("sumNoLectivasPed");
    const state = getHorasLectivasState();
    const lectivasInvalid = state.lectivasPed > state.limitePed || state.totalPed > state.limitePed;
    const noLectivasInvalid = state.noLectivasPed > state.limitePed || state.totalPed > state.limitePed;

    if (lectivasPedEl) {
      lectivasPedEl.classList.toggle("is-invalid", lectivasInvalid);
      buzzInvalidInput(lectivasPedEl, lectivasInvalid);
    }
    if (noLectivasPedEl) {
      noLectivasPedEl.classList.toggle("is-invalid", noLectivasInvalid);
      buzzInvalidInput(noLectivasPedEl, noLectivasInvalid);
    }

    if (!msgEl) return state;

    msgEl.classList.remove("is-hidden", "is-ok");
    if (state.errors.length > 0) {
      msgEl.innerHTML = state.errors.join("<br>");
      return state;
    }

    if (state.totalPed > 0) {
      msgEl.classList.add("is-ok");
      msgEl.textContent = `Horas lectivas + no lectivas: ${Math.round(state.totalPed * 100) / 100} de 54 pedagógicas.`;
      return state;
    }

    msgEl.classList.add("is-hidden");
    msgEl.textContent = "";
    return state;
  }

  function recalcularLectivas() {
    const pedEl = document.getElementById("sumLectivasPed");
    const croEl = document.getElementById("sumLectivasCro");
    const noLectivasPedEl = document.getElementById("sumNoLectivasPed");
    if (!pedEl || !croEl) return;

    const pedHours = enforcePedLimit(pedEl, noLectivasPedEl);
    croEl.value = pedHoursToCronoHHMM(pedHours);
    recalcularNoLectivas();
    updateHorasLectivasUI();
  }

  function recalcularNoLectivas() {
    const pedEl = document.getElementById("sumNoLectivasPed");
    const noLectivasCroEl = document.getElementById("sumNoLectivasCro");
    const lectivasPedEl = document.getElementById("sumLectivasPed");
    const lectivasCroEl = document.getElementById("sumLectivasCro");
    if (!pedEl || !noLectivasCroEl) return;

    enforcePedLimit(pedEl, lectivasPedEl);
    const jornadaCronoMin = getJornadaCronoMinutes();
    const lectivasCronoMin = parseHHMM(lectivasCroEl ? lectivasCroEl.value : "00:00");
    const noLectivasCronoMin = Math.max(0, jornadaCronoMin - lectivasCronoMin);
    noLectivasCroEl.value = formatHHMM(noLectivasCronoMin);
    updateHorasLectivasUI();
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
    const noLectivasPedEl = document.getElementById("sumNoLectivasPed");

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

    if (noLectivasPedEl) {
      attachPedNumberValidation(noLectivasPedEl);
      noLectivasPedEl.addEventListener("input", function () {
        recalcularNoLectivas();
      });
      noLectivasPedEl.addEventListener("blur", function () {
        recalcularNoLectivas();
      });
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
  global.getHorasLectivasState = getHorasLectivasState;
  global.updateHorasLectivasUI = updateHorasLectivasUI;
  global.attachTimeValidation = attachTimeValidation;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bindAutoCalc);
  } else {
    bindAutoCalc();
  }
})(window);
