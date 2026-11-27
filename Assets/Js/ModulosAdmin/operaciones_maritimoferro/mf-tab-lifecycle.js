/* MF.02.2: inicializacion de paneles al recibir shown.bs.tab (Bootstrap 5). */
(function (global) {
  "use strict";
  if (global.PNMF02Tabs) return;

  function isActive(id) {
    const panel = document.getElementById(id);
    return !!panel && panel.classList.contains("active");
  }

  function ready(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn, { once: true });
    } else {
      fn();
    }
  }

  function register(id, first, again) {
    const nav = document.getElementById("operacionTabs");
    const tab = nav && Array.from(nav.querySelectorAll("[data-bs-toggle=tab]"))
      .find(el => (el.getAttribute("data-bs-target") || el.getAttribute("href")) === "#" + id);
    // Fuera de la vista MF, conservar el comportamiento original.
    if (!tab) { ready(first); return; }
    let started = false;
    let pendingReady = false;
    function activate() {
      if (!isActive(id)) return;
      if (document.readyState === "loading") {
        if (!pendingReady) {
          pendingReady = true;
          ready(() => { pendingReady = false; if (!started) activate(); });
        }
        return;
      }
      if (!started) {
        started = true;
        try { first(); } catch (e) { started = false; throw e; }
      } else if (typeof again === "function") {
        again();
      }
    }
    tab.addEventListener("shown.bs.tab", activate);
    ready(() => { if (!started) activate(); });
  }

  global.PNMF02Tabs = Object.freeze({ register, isActive });
})(window);
