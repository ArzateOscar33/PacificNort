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
    window.BASE_URL || (typeof base_url !== "undefined" ? base_url : "") || "";

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
  if (!elMarAgua || !elTerCamino || !elMarPuerto || !elEntregadas) return;

  // ===== Utils =====
  function toInt(v) {
    const n = parseInt(v, 10);
    return Number.isFinite(n) ? n : 0;
  }

  function setText(el, value) {
    if (!el) return;
    el.textContent = String(toInt(value));
  }

  // Animación simple de conteo
  function animateCount(el, toValue, durationMs = 450) {
    if (!el) return;

    const fromValue = toInt(el.textContent);
    const endValue = toInt(toValue);

    if (fromValue === endValue) {
      el.textContent = String(endValue);
      return;
    }

    const start = performance.now();

    function tick(now) {
      const t = Math.min(1, (now - start) / durationMs);
      const eased = 1 - Math.pow(1 - t, 3);
      const current = Math.round(fromValue + (endValue - fromValue) * eased);
      el.textContent = String(current);

      if (t < 1) {
        requestAnimationFrame(tick);
      } else {
        el.textContent = String(endValue);
      }
    }

    requestAnimationFrame(tick);
  }

  function setLoadingState() {
    setText(elMarAgua, 0);
    setText(elMarPuerto, 0);
    setText(elTerCamino, 0);
    setText(elEntregadas, 0);

    if (elBodegas) setText(elBodegas, 0);
    if (elYardas) setText(elYardas, 0);
  }

  function paintKpis(k) {
    // El backend sigue devolviendo:
    // { mar_agua, mar_puerto, fo_camino, bodegas, yardas, entregadas }
    const marAgua = toInt(k.mar_agua);
    const marPuerto = toInt(k.mar_puerto);
    const enCaminoDestino = toInt(k.fo_camino); // <-- misma key, nueva lógica
    const bodegas = toInt(k.bodegas);
    const yardas = toInt(k.yardas);
    const entregadas = toInt(k.entregadas);

    animateCount(elMarAgua, marAgua);
    animateCount(elMarPuerto, marPuerto);
    animateCount(elTerCamino, enCaminoDestino);
    animateCount(elEntregadas, entregadas);

    if (elBodegas) animateCount(elBodegas, bodegas);
    if (elYardas) animateCount(elYardas, yardas);

    // Subtextos actualizados a la nueva lógica
    if (elSubMarAgua) elSubMarAgua.textContent = "Operaciones en agua";
    if (elSubMarPuerto) elSubMarPuerto.textContent = "Operaciones en puerto";
    if (elSubTerCamino) elSubTerCamino.textContent = "En camino a destino";
    if (elSubBodegas) elSubBodegas.textContent = "Bodega MX / Bodega USA";
    if (elSubYardas) elSubYardas.textContent = "Yarda MX / Yarda USA";
    if (elSubEntregadas) elSubEntregadas.textContent = "Operaciones entregadas";
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
            return;
          }

          paintKpis(data.kpis);
        } catch (e) {
          console.error("Error al parsear KPIs del portal:", e);
        }
        return;
      }

      console.error("Error al cargar KPIs del portal. HTTP:", xhr.status);
    };

    xhr.onerror = function () {
      console.error("Error de red al cargar KPIs del portal.");
    };

    xhr.send(null);
  }

  // Refrescar manual
  if (btnRefrescar) {
    btnRefrescar.addEventListener("click", function () {
      loadKpis();
    });
  }

  // ===== Init =====
  loadKpis();
})();
