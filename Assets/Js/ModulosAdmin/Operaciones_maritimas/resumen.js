// =========================
// Refs + estado
// =========================
const selectContenedorResumen  = document.getElementById("selectContenedorResumen");
const inpBuscarOpResumen       = document.getElementById("buscarOperacionResumen");
const boxSugsOpResumen         = document.getElementById("sugerenciasOperacionResumen");
const btnRef                   = document.getElementById("btnRefrescarResumen");

let operacionIdActivo   = null;
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
function limpiarDetalleUI() {
  document.getElementById('nombreContenedorResumen').textContent = '—';
   
  // Marítimo
  document.getElementById('puertoResumen').textContent = '—';
  document.getElementById('etaContenedor').textContent = '—';
  document.getElementById('etdContenedor').textContent = '—';
  document.getElementById('blContenedor').textContent = '—';
  document.getElementById('comentarioContenedor').textContent = '—';
  // Ferro
  document.getElementById('arriboPuerto').textContent = '—';
  document.getElementById('bultos').textContent = '—';
}
function setDetalleLoading() {
  document.getElementById('comentarioContenedor').textContent = 'Cargando…';
}

// =========================
// Sugerencias (autocomplete)
// =========================
function renderSugerencias(items) {
  if (!Array.isArray(items) || items.length === 0) { clearSugerencias(); return; }
  boxSugsOpResumen.innerHTML = '';
  items.forEach(row => {
    const a = document.createElement('a');
    a.className   = 'list-group-item list-group-item-action';
    a.href        = '#';
    a.textContent = row.label; // "EN-06 — Cliente X"
    a.addEventListener('click', (e) => {
      e.preventDefault();
      seleccionarSugerencia(row);
    });
    boxSugsOpResumen.appendChild(a);
  });
  boxSugsOpResumen.style.display = 'block';
}

function doSearchSugerencias(term) {
  if (lastXHRSugerencias) { try { lastXHRSugerencias.abort(); } catch(e){} }
  const http = new XMLHttpRequest();
  lastXHRSugerencias = http;

  http.open("GET", base_url + "operaciones_maritimas_resumen/sugerencias?term=" + encodeURIComponent(term), true);
  http.onreadystatechange = function() {
    if (this.readyState !== 4) return;
    if (this.status === 200) {
      let res; try { res = JSON.parse(this.responseText); } catch { res = null; }
      if (!res || res.status !== 'ok') { clearSugerencias(); return; }
      renderSugerencias(res.data);
    } else {
      clearSugerencias();
    }
  };
  http.send();
}

inpBuscarOpResumen.addEventListener('input', function() {
  const term = this.value.trim();
  clearTimeout(debounceTimer);
  if (term.length < 2) { clearSugerencias(); return; }
  debounceTimer = setTimeout(() => doSearchSugerencias(term), 250);
});

document.addEventListener('click', (e) => {
  if (!boxSugsOpResumen.contains(e.target) && e.target !== inpBuscarOpResumen) {
    clearSugerencias();
  }
});
inpBuscarOpResumen.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') clearSugerencias();
});

// Al elegir una sugerencia -> fijamos operación y cargamos contenedores
function seleccionarSugerencia(row) {
  inpBuscarOpResumen.value = row.label;
  operacionIdActivo = String(row.id);
  cargarContenedores(operacionIdActivo);
  clearSugerencias();
}

// =========================
// Contenedores por operación
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
      let res; try { res = JSON.parse(this.responseText); } catch { res = null; }
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
  const rows = Array.isArray(res.contenedores) ? res.contenores : (Array.isArray(res.data) ? res.data : res.contenedores);
  // Fallback correcto:
  const data = Array.isArray(res.contenedores) ? res.contenedores : (Array.isArray(res.data) ? res.data : []);

  if (!data || data.length === 0) { setContenedoresEmpty(); return; }

  data.forEach(c => {
    const option = document.createElement("option");
    option.value = c.id_contenedor;                                    // id genérico
    option.textContent = `${c.tipo_contenedor} · ${c.numero_contenedor}`;
    option.dataset.tipo   = (c.tipo_contenedor || '').toUpperCase();   // "MARITIMO" | "FERRO"
    option.dataset.numero = c.numero_contenedor || '';
    selectContenedorResumen.appendChild(option);
  });

  if (selectContenedorResumen.options.length > 0) {
    selectContenedorResumen.selectedIndex = 0;
    consultarDetallesContenedor();
  }
}

// =========================
// Detalle del contenedor
// =========================
selectContenedorResumen.addEventListener('change', consultarDetallesContenedor);

if (btnRef) btnRef.addEventListener('click', (e) => {
  e.preventDefault();
  consultarDetallesContenedor();
});

function consultarDetallesContenedor() {
  const opt = selectContenedorResumen.options[selectContenedorResumen.selectedIndex];
  if (!opt || !operacionIdActivo) return;

  const tipo   = (opt.dataset.tipo || '').toUpperCase();  // "MARITIMO" | "FERRO"
  const idCont = opt.value;
  const numero = opt.dataset.numero || opt.textContent || '—';

  // Cabecera
  document.getElementById('nombreContenedorResumen').textContent = numero;
   

  // Alterna paneles
  document.getElementById('bloqueMaritimo').classList.toggle('d-none', tipo !== 'MARITIMO');
  document.getElementById('bloqueFerro').classList.toggle('d-none', tipo === 'MARITIMO');

  limpiarDetalleUI();
  setDetalleLoading();

  const http = new XMLHttpRequest();
  const url = `${base_url}operaciones_maritimas_resumen/detalles_contenedor?operacion_id=${encodeURIComponent(operacionIdActivo)}&tipo=${encodeURIComponent(tipo)}&id_contenedor=${encodeURIComponent(idCont)}`;
  http.open('GET', url, true);
  http.onreadystatechange = function() {
    if (this.readyState !== 4) return;

    if (this.status === 200) {
      let res; try { res = JSON.parse(this.responseText); } catch { res = null; }
      if (!res || res.status !== 'ok' || !res.data) return;
      pintarDetalleContenedor(tipo, res.data);
    } else {
      // deja placeholders
    }
  };
  http.send();
}

function pintarDetalleContenedor(tipo, data) {
   
  if (tipo === 'MARITIMO') {
    document.getElementById('nombreContenedorResumen').textContent = data.numero_contenedor || '—';
    document.getElementById('puertoResumen').textContent   = data.puerto || '—';
    document.getElementById('etaContenedor').textContent   = data.eta || '—';
    document.getElementById('etdContenedor').textContent   = data.etd || '—';
    document.getElementById('blContenedor').textContent    = data.bl || '—';
    document.getElementById('comentarioContenedor').textContent = data.comentarios || '—';
    
     
  } else {
    document.getElementById('nombreContenedorResumen').textContent = data.numero_ferro || '—';
    document.getElementById('arriboPuerto').textContent    = data.arribo_puerto || 'Falta Registrar Arribo';
    document.getElementById('bultos').textContent          = (data.bultos != null ? data.bultos : '—');
    document.getElementById('comentarioContenedor').textContent = data.comentarios || '—';
  }
}
