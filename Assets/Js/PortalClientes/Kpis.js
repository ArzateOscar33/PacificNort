/* Assets/Js/PortalClientes/KpisPortal.js
   Carga KPIs del Portal Cliente y pinta:
   - #kpiMarEnAgua
   - #kpiTerEnCamino
   - #kpiMarEnPuerto
   - #kpiBodegas
   - #kpiYardas
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
  const elBodegas = document.getElementById("kpiBodegas");
  const elYardas = document.getElementById("kpiYardas");
  const elEntregadas = document.getElementById("kpiEntregadas");

  // Subtext (opcionales)
  const elSubMarAgua = document.getElementById("kpiMarEnAguaSub");
  const elSubTerCamino = document.getElementById("kpiTerEnCaminoSub");
  const elSubMarPuerto = document.getElementById("kpiMarEnPuertoSub");
  const elSubBodegas = document.getElementById("kpiBodegasSub");
  const elSubYardas = document.getElementById("kpiYardasSub");
  const elSubEntregadas = document.getElementById("kpiEntregadasSub");

  // Si no existe lo mínimo, no hacemos nada
  // (Los nuevos KPIs son opcionales por si aún no están en alguna vista)
  if (!elMarAgua || !elTerCamino || !elMarPuerto || !elEntregadas) return;

  // ===== Utils =====
  function toInt(v) {
    const n = parseInt(v, 10);
    return Number.isFinite(n) ? n : 0;
  }

  // Animación simple de conteo (sutil)
  function animateCount(el, toValue, durationMs = 450) {
    if (!el) return;

    const fromValue = toInt(el.textContent);
    const start = performance.now();

    function tick(now) {
      const t = Math.min(1, (now - start) / durationMs);
      const eased = 1 - Math.pow(1 - t, 3);
      const current = Math.round(fromValue + (toValue - fromValue) * eased);
      el.textContent = String(current);
      if (t < 1) requestAnimationFrame(tick);
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
    // Endpoint: { ok:true, kpis: { mar_agua, mar_puerto, fo_camino, bodegas, yardas, entregadas } }
    const marAgua = toInt(k.mar_agua);
    const marPuerto = toInt(k.mar_puerto);
    const foCamino = toInt(k.fo_camino);
    const bodegas = toInt(k.bodegas);
    const yardas = toInt(k.yardas);
    const entregadas = toInt(k.entregadas);

    animateCount(elMarAgua, marAgua);
    animateCount(elMarPuerto, marPuerto);
    animateCount(elTerCamino, foCamino);
    animateCount(elEntregadas, entregadas);

    // Nuevos (si existen en la vista)
    animateCount(elBodegas, bodegas);
    animateCount(elYardas, yardas);

    // Subtext opcional (puedes dejarlo fijo)
    if (elSubMarAgua) elSubMarAgua.textContent = "En tránsito";
    if (elSubMarPuerto) elSubMarPuerto.textContent = "Contenedores en puerto";
    if (elSubTerCamino) elSubTerCamino.textContent = "Ruta activa";

    if (elSubBodegas) elSubBodegas.textContent = "Contenedores En Bodega";
    if (elSubYardas) elSubYardas.textContent = "Contenedores en Yarda";

    if (elSubEntregadas)
      elSubEntregadas.textContent = "Operaciones Totales Entregadas";
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

          if (!data || data.ok !== true || !data.kpis) return;

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
  loadKpis();
})();
