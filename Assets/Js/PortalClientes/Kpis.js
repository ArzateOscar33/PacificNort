/* Assets/Js/PortalClientes/KpisPortal.js
   Carga KPIs del Portal Cliente y pinta:
   - #kpiMarEnAgua
   - #kpiTerEnCamino
   - #kpiMarEnPuerto
   - #kpiEntregadas
*/
(function () {
  "use strict";
  const btnRefrescar = document.getElementById("btnRefrescarTodo");
  // ===== Helpers base url =====
  const BASE_URL =
    window.BASE_URL || (typeof base_url !== "undefined" ? base_url : "");

  const ENDPOINT = BASE_URL + "PortalClientes/kpis";

  // ===== Refs UI =====
  const elMarAgua = document.getElementById("kpiMarEnAgua");
  const elTerCamino = document.getElementById("kpiTerEnCamino");
  const elMarPuerto = document.getElementById("kpiMarEnPuerto");
  const elEntregadas = document.getElementById("kpiEntregadas");

  // Subtext (opcionales)
  const elSubMarAgua = document.getElementById("kpiMarEnAguaSub");
  const elSubTerCamino = document.getElementById("kpiTerEnCaminoSub");
  const elSubMarPuerto = document.getElementById("kpiMarEnPuertoSub");
  const elSubEntregadas = document.getElementById("kpiEntregadasSub");

  // Si no existen los elementos, no hacemos nada
  if (!elMarAgua || !elTerCamino || !elMarPuerto || !elEntregadas) return;

  // ===== Utils =====
  function toInt(v) {
    const n = parseInt(v, 10);
    return Number.isFinite(n) ? n : 0;
  }

  function setText(el, val) {
    if (!el) return;
    el.textContent = String(val);
  }

  // Animación simple de conteo (sutil)
  function animateCount(el, toValue, durationMs = 450) {
    if (!el) return;

    const fromValue = toInt(el.textContent);
    const start = performance.now();
    const end = start + durationMs;

    function tick(now) {
      const t = Math.min(1, (now - start) / durationMs);
      // easing suave
      const eased = 1 - Math.pow(1 - t, 3);
      const current = Math.round(fromValue + (toValue - fromValue) * eased);
      el.textContent = String(current);
      if (now < end) requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);
  }
  // Refrescar
  if (btnRefrescar) {
    btnRefrescar.addEventListener("click", function () {
      loadKpis();
    });
  }
  function paintKpis(k) {
    // Tu endpoint devuelve: { ok:true, kpis: { mar_agua, mar_puerto, fo_camino, entregadas } }
    const marAgua = toInt(k.mar_agua);
    const marPuerto = toInt(k.mar_puerto);
    const foCamino = toInt(k.fo_camino);
    const entregadas = toInt(k.entregadas);

    animateCount(elMarAgua, marAgua);
    animateCount(elMarPuerto, marPuerto);
    animateCount(elTerCamino, foCamino);
    animateCount(elEntregadas, entregadas);

    // Subtext opcional dinámico (puedes dejar tus textos fijos si prefieres)
    if (elSubMarAgua)
      elSubMarAgua.textContent = marAgua === 1 ? "En tránsito" : "En tránsito";
    if (elSubMarPuerto)
      elSubMarPuerto.textContent =
        marPuerto === 1 ? "Arribada / en proceso" : "Arribadas / en proceso";
    if (elSubTerCamino)
      elSubTerCamino.textContent =
        foCamino === 1 ? "Ruta activa" : "Ruta activa";
    if (elSubEntregadas) elSubEntregadas.textContent = "MAR + LBMF + FO";
  }

  function loadKpis() {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", ENDPOINT, true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const data = JSON.parse(xhr.responseText);

          if (!data || data.ok !== true || !data.kpis) {
            // fallback silencioso
            return;
          }

          paintKpis(data.kpis);
        } catch (e) {
          // silencioso
        }
      }
    };

    xhr.onerror = function () {
      // silencioso
    };

    xhr.send(null);
  }

  // ===== Init =====
  // Carga al inicio
  loadKpis();

  // Opcional: refrescar cada 60s (si lo quieres)
  // setInterval(loadKpis, 60000);
})();
