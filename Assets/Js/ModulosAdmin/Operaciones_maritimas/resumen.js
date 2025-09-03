// =========================
// Refs
// =========================
const selectContenedorResumen  = document.getElementById("selectContenedorResumen");
const inpBuscarOpResumen       = document.getElementById("buscarOperacionResumen");
const boxSugsOpResumen         = document.getElementById("sugerenciasOperacionResumen");

// Asegúrate de tener base_url en tu layout (ej: const base_url = "<?php echo BASE_URL; ?>";)
let lastXHRContenedores = null;
let lastXHRSugerencias  = null;
let debounceTimer       = null;

// =========================
// Helpers UI
// =========================
function setContenedoresLoading() {
  selectContenedorResumen.innerHTML = '<option value="">Cargando contenedores…</option>';
}
function setContenedoresEmpty(msg = 'Sin contenedores') {
  selectContenedorResumen.innerHTML = `<option value="">${msg}</option>`;
}
function clearSugerencias() {
  boxSugsOpResumen.style.display = 'none';
  boxSugsOpResumen.innerHTML = '';
}
function renderSugerencias(items) {
  if (!Array.isArray(items) || items.length === 0) { clearSugerencias(); return; }
  boxSugsOpResumen.innerHTML = '';
  items.forEach(row => {
    const a = document.createElement('a');
    a.className     = 'list-group-item list-group-item-action';
    a.href          = '#';
    a.dataset.id    = row.id;
    a.dataset.label = row.label;
    a.textContent   = row.label; // "EN-06 — Cliente X"
    a.addEventListener('click', (e) => {
      e.preventDefault();
      seleccionarSugerencia(row);
    });
    boxSugsOpResumen.appendChild(a);
  });
  boxSugsOpResumen.style.display = 'block';
}

function seleccionarSugerencia(row) {
  // 1) Poner el texto elegido en el input (opcional)
  inpBuscarOpResumen.value = row.label;

  // 2) Cargar contenedores de esa operación (ya no existe select de operaciones)
  const id = String(row.id);
  if (typeof cargarContenedores === 'function') {
    cargarContenedores(id);
  }

  // 3) Cerrar las sugerencias
  clearSugerencias();
}

// =========================
// Cargar contenedores por operación
// =========================
function cargarContenedores(operacionIdResumen) {
  if (lastXHRContenedores) { try { lastXHRContenedores.abort(); } catch(e){} }
  setContenedoresLoading();

  const http = new XMLHttpRequest();
  lastXHRContenedores = http;

  http.open(
    "GET",
    base_url + "operaciones_maritimas_resumen/listarContenedoresPorOperacion?id_operacion=" + encodeURIComponent(operacionIdResumen),
    true
  );
  http.onreadystatechange = function () {
    if (this.readyState !== 4) return;

    if (this.status === 200) {
      let res;
      try { res = JSON.parse(this.responseText); } catch { res = null; }
      if (!res || (res.status && res.status !== 'ok')) {
        setContenedoresEmpty('No se pudieron cargar contenedores');
        return;
      }
      renderContenedores(res);
    } else {
      setContenedoresEmpty('Error al cargar contenedores');
    }
  };
  http.send();
}

function renderContenedores(res) {
  selectContenedorResumen.innerHTML = "";

  // Compatibilidad: {status, contenedores:[...]} o {data:[...]}
  const rows = Array.isArray(res.contenedores) ? res.contenedores : (Array.isArray(res.data) ? res.data : []);

  if (!rows || rows.length === 0) {
    setContenedoresEmpty();
    return;
  }

  rows.forEach(c => {
    const option = document.createElement("option");
    // Esperado: { id_contenedor, tipo_contenedor, numero_contenedor }
    option.value = c.id_contenedor;
    option.textContent = `${c.tipo_contenedor} · ${c.numero_contenedor}`;
    selectContenedorResumen.appendChild(option);
  });
}

// =========================
// Autocomplete de operaciones (input)
// =========================
inpBuscarOpResumen.addEventListener('input', function() {
  const term = this.value.trim();
  clearTimeout(debounceTimer);
  if (term.length < 2) { clearSugerencias(); return; }
  debounceTimer = setTimeout(() => doSearchSugerencias(term), 250);
});

function doSearchSugerencias(term) {
  if (lastXHRSugerencias) { try { lastXHRSugerencias.abort(); } catch(e){} }

  const http = new XMLHttpRequest();
  lastXHRSugerencias = http;

  http.open("GET", base_url + "operaciones_maritimas_resumen/sugerencias?term=" + encodeURIComponent(term), true);
  http.onreadystatechange = function() {
    if (this.readyState !== 4) return;

    if (this.status === 200) {
      let res;
      try { res = JSON.parse(this.responseText); } catch { res = null; }
      // Log opcional para depurar:
      // console.log('sugerencias:', res);
      if (!res || res.status !== 'ok') { clearSugerencias(); return; }
      renderSugerencias(res.data);
    } else {
      clearSugerencias();
    }
  };
  http.send();
}

// Cerrar sugerencias al hacer click fuera
document.addEventListener('click', (e) => {
  if (!boxSugsOpResumen.contains(e.target) && e.target !== inpBuscarOpResumen) {
    clearSugerencias();
  }
});

// Cerrar con Esc
inpBuscarOpResumen.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') clearSugerencias();
});
