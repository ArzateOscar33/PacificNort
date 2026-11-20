<?php
/**
 * PacificNort Suite - Vista parcial: Traslados de Mercancía (MF).
 *
 * Incluir UNA sola vez desde:
 * Views/Admin/operaciones_maritimo_ferro/tabs/operaciones_mar.php
 *
 * Integración posterior con:
 * Assets/Js/ModulosAdmin/operaciones_maritimoferro/traslados_mercancia.js
 *
 * Esta vista NO consulta la BD, no inicializa endpoints ni genera
 * formularios de operaciones. El JS carga los datos por operacion_id.
 *
 * Convenciones del controlador:
 *  GET  Operaciones_maritimo_ferro_traslados/inicializar?operacion_id=...
 *  GET  Operaciones_maritimo_ferro_traslados/listar?operacion_id=...
 *  GET  Operaciones_maritimo_ferro_traslados/obtenerUno?operacion_id=...&id_traslado=...
 *  POST Operaciones_maritimo_ferro_traslados/guardar
 *  POST Operaciones_maritimo_ferro_traslados/guardarLote
 *  POST Operaciones_maritimo_ferro_traslados/cancelar
 */
?>
<style>
  /* Estilos limitados al modal MF para no afectar otras tablas/modales. */
  #modalTrasladosMf .mf-tr-resumen {
    border: 1px solid #d7e8de;
    border-radius: .5rem;
    background: #f4faf6;
  }

  #modalTrasladosMf .mf-tr-cabecera-label {
    font-size: .81rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .025em;
    color: #64748b;
  }

  #modalTrasladosMf .mf-tr-codigo-operacion {
    font-size: 1.25rem;
    font-weight: 700;
    color: #155a40;
    overflow-wrap: anywhere;
  }

  #modalTrasladosMf .mf-tr-contenedor-etiqueta {
    display: inline-block;
    padding: .2rem .5rem;
    margin: .1rem .2rem .1rem 0;
    border: 1px solid #d4ded8;
    border-radius: .35rem;
    background: #fff;
    font-size: .87rem;
    font-weight: 600;
  }

  /* Area de renglones: reserva espacio para ver las opciones desplegables.
     En escritorio no recorta el autocompletado dentro de la tabla. */
  #modalTrasladosMf .mf-tr-tabla-contenedor {
    min-height: clamp(340px, 45vh, 450px);
    max-height: none;
    overflow: visible;
    position: relative;
    border: 1px solid #dee2e6;
    border-radius: .4rem;
  }

  #modalTrasladosMf .mf-tr-tabla {
    min-width: 1110px;
    font-size: .9rem;
    margin-bottom: 0;
  }

  #modalTrasladosMf .mf-tr-tabla thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #eef5f0;
    color: #295a42;
    font-size: .82rem;
    white-space: nowrap;
    vertical-align: middle;
  }

  #modalTrasladosMf .mf-tr-tabla tbody td {
    vertical-align: middle;
  }

  #modalTrasladosMf .mf-tr-numero {
    font-weight: 700;
    color: #476577;
  }

  #modalTrasladosMf .mf-tr-codigo-input {
    max-width: 124px;
    min-width: 96px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
  }

  #modalTrasladosMf .mf-tr-autocomplete {
    position: relative;
    min-width: 188px;
  }

  #modalTrasladosMf .mf-tr-sugerencias {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1065;
    max-height: 230px;
    overflow-y: auto;
    box-shadow: 0 .35rem .85rem rgba(0, 0, 0, .13);
  }

  /* Mantiene las sugerencias por encima de otras filas de la tabla. */
  #modalTrasladosMf .mf-tr-tabla tbody tr:focus-within {
    position: relative;
    z-index: 10;
  }

  #modalTrasladosMf .mf-tr-resuelto {
    display: block;
    font-size: .79rem;
    max-width: 245px;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
  }

  #modalTrasladosMf .mf-tr-monto {
    min-width: 112px;
    max-width: 150px;
    font-variant-numeric: tabular-nums;
  }

  #modalTrasladosMf .mf-tr-cancelado {
    opacity: .68;
    background-color: #f8f9fa;
  }

  #modalTrasladosMf .mf-tr-total {
    font-weight: 800;
    font-size: 1.3rem;
    color: #116148;
    font-variant-numeric: tabular-nums;
  }

  #modalTrasladosMf .mf-tr-acciones {
    min-width: 158px;
  }

  /* ===== Ajustes visuales Traslados MF: no alteran otros modales ===== */
  #modalTrasladosMf .modal-dialog {
    width: calc(100vw - 48px);
    max-width: min(1480px, calc(100vw - 48px));
  }

  #modalTrasladosMf .modal-content {
    font-size: .95rem;
    border-radius: .7rem;
  }

  #modalTrasladosMf .modal-header {
    padding: 1rem 1.5rem;
    border-radius: .7rem .7rem 0 0;
  }

  #modalTrasladosMf .modal-body {
    padding: 1.45rem 1.65rem;
  }

  #modalTrasladosMf .modal-footer {
    padding: 1rem 1.65rem;
    border-radius: 0 0 .7rem .7rem;
  }

  #modalTrasladosMf .mf-tr-resumen {
    padding: 1.15rem 1.35rem !important;
    margin-bottom: 1.35rem !important;
  }

  #modalTrasladosMf .mf-tr-tabla thead th {
    padding: .72rem .8rem;
    letter-spacing: .01em;
  }

  #modalTrasladosMf .mf-tr-tabla tbody td {
    padding: .78rem .75rem;
  }

  #modalTrasladosMf .mf-tr-codigo-input {
    padding-top: .35rem;
    padding-bottom: .35rem;
  }

  #modalTrasladosMf .mf-tr-tabla .btn {
    white-space: nowrap;
  }

  #modalTrasladosMf .mf-tr-resuelto {
    margin-top: .24rem;
    line-height: 1.25;
  }

  /* El grupo de acciones de la tabla principal queda en una fila. */
  #maritimo_ferro_tablaBody td:last-child {
    min-width: 310px;
  }

  #maritimo_ferro_tablaBody .mf-tr-acciones-listado {
    flex-wrap: nowrap !important;
    align-items: center;
    gap: .35rem !important;
    white-space: nowrap;
  }

  /* Morado para diferenciar Traslados del azul Editar y el verde Caja/Ferro. */
  #maritimo_ferro_tablaBody .btn-traslados-mf {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: auto !important;
    min-width: 0;
    padding: .28rem .54rem;
    font-size: .78rem;
    line-height: 1.2;
    white-space: nowrap;
    color: #fff;
    background-color: #6f42c1;
    border-color: #6f42c1;
    border-radius: .35rem;
    box-shadow: none;
  }

  #maritimo_ferro_tablaBody .btn-traslados-mf:hover,
  #maritimo_ferro_tablaBody .btn-traslados-mf:focus-visible {
    color: #fff;
    background-color: #59359a;
    border-color: #59359a;
  }

  #maritimo_ferro_tablaBody .btn-traslados-mf svg {
    width: 14px;
    height: 14px;
  }

  /* En pantallas angostas se conserva el scroll horizontal de la tabla. */
  @media (max-width: 1199.98px) {
    #modalTrasladosMf .mf-tr-tabla-contenedor {
      min-height: 340px;
      max-height: 60vh;
      overflow: auto;
    }
  }

  @media (max-width: 767.98px) {
    #modalTrasladosMf .modal-dialog {
      width: calc(100vw - 16px);
      max-width: calc(100vw - 16px);
      margin: .5rem auto;
    }
    #modalTrasladosMf .modal-body,
    #modalTrasladosMf .modal-footer {
      padding: 1rem;
    }
    #modalTrasladosMf .mf-tr-tabla {
      min-width: 1010px;
    }
  }
</style>

<!-- Una única instancia del modal compartida por todas las operaciones. -->
<div class="modal fade"
     id="modalTrasladosMf"
     tabindex="-1"
     aria-labelledby="modalTrasladosMfTitulo"
     aria-hidden="true"
     data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-success text-white">
        <div>
          <h5 class="modal-title mb-1" id="modalTrasladosMfTitulo">
            <i data-feather="truck" class="me-2"></i>Traslados de Mercancía
          </h5>
          <div class="small opacity-75">
            Recorrido único por operación · Transporte en dólares
          </div>
        </div>
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="modal"
                aria-label="Cerrar"></button>
      </div>

      <form id="mfTrForm" autocomplete="off" novalidate>
        <div class="modal-body">
          <!-- El ID siempre llega del botón en Operaciones, nunca del contenedor. -->
          <input type="hidden" id="mfTrOperacionId" name="operacion_id" value="">

          <div class="mf-tr-resumen p-3 mb-3">
            <div class="row g-3 align-items-start">
              <div class="col-12 col-md-3">
                <div class="mf-tr-cabecera-label">Operación</div>
                <div class="mf-tr-codigo-operacion" id="mfTrNumeroOperacion">—</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="mf-tr-cabecera-label">Contenedores marítimos asociados</div>
                <div id="mfTrContenedores" aria-live="polite">
                  <span class="text-muted small">Selecciona una operación.</span>
                </div>
                <div class="form-text mt-1">
                  Los contenedores comparten un solo recorrido para esta operación.
                </div>
              </div>
              <div class="col-12 col-md-3 text-md-end">
                <div class="mf-tr-cabecera-label">Concepto de costo</div>
                <div class="fw-semibold" id="mfTrConcepto">Transporte / USD</div>
                <span class="badge bg-secondary" id="mfTrEstado">Sin cargar</span>
              </div>
            </div>
          </div>

          <!-- Este aviso puede mostrar ausencia de concepto o continuidad rota. -->
          <div class="alert alert-warning d-none mb-3" role="alert" id="mfTrAdvertencia"></div>
          <div class="alert alert-danger d-none mb-3" role="alert" id="mfTrError"></div>

          <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
            <div>
              <h6 class="mb-1">Recorrido y costos</h6>
              <div class="text-muted small">
                Cada renglón representa un tramo y un costo vinculado a la operación.
              </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <div class="form-check form-switch mb-0">
                <input type="checkbox"
                       class="form-check-input"
                       id="mfTrMostrarCancelados"
                       role="switch">
                <label class="form-check-label small" for="mfTrMostrarCancelados">
                  Ver cancelados
                </label>
              </div>
              <button type="button"
                      class="btn btn-outline-secondary btn-sm"
                      id="mfTrBtnRecargar"
                      disabled>
                <i data-feather="refresh-cw" class="me-1"></i>Actualizar
              </button>
              <button type="button"
                      class="btn btn-outline-success btn-sm"
                      id="mfTrBtnAgregarFila"
                      disabled>
                <i data-feather="plus-circle" class="me-1"></i>Agregar tramo
              </button>
            </div>
          </div>

          <div class="mf-tr-tabla-contenedor mb-2" id="mfTrTablaContenedor">
            <table class="table table-bordered table-hover table-sm align-middle mf-tr-tabla"
                   id="mfTrTabla">
              <thead>
                <tr>
                  <th class="text-center" style="width:6%">N.º</th>
                  <th style="width:19%">Transportista</th>
                  <th style="width:20%">Origen</th>
                  <th style="width:20%">Destino</th>
                  <th class="text-end" style="width:13%">Monto USD</th>
                  <th class="text-center" style="width:9%">Estado</th>
                  <th class="text-center" style="width:13%">Acciones</th>
                </tr>
              </thead>
              <!-- El JS inserta filas registradas y filas nuevas aquí. -->
              <tbody id="mfTrTablaBody">
                <tr id="mfTrFilaVacia">
                  <td colspan="7" class="text-center text-muted py-4">
                    Abre Traslados desde una operación para consultar su recorrido.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
            <div class="small text-muted" id="mfTrAyudaContinuidad">
              El origen del siguiente tramo debe coincidir con el destino del anterior.
              La baja lógica conserva el orden histórico.
            </div>
            <div class="small text-muted">
              <span id="mfTrConteo">0 tramos activos</span>
              <span class="mx-1">·</span>
              <span id="mfTrPendientes">0 pendientes</span>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 border rounded p-3 mt-3">
            <div>
              <div class="small text-muted">Total de traslados activos de esta operación</div>
              <span class="fw-semibold small">No incluye registros cancelados</span>
            </div>
            <div class="mf-tr-total" id="mfTrTotalUsd">US$0.00</div>
          </div>
          <div class="form-text mt-2">
            Se aceptan importes de US$0.00. Los costos pagados no se pueden editar ni cancelar automáticamente.
          </div>
        </div>

        <div class="modal-footer justify-content-between gap-2">
          <span class="small text-muted" id="mfTrMensaje" aria-live="polite"></span>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cerrar
            </button>
            <button type="button" class="btn btn-success" id="mfTrBtnGuardarPendientes" disabled>
              <i data-feather="save" class="me-1"></i>Guardar nuevos tramos
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!--
  Plantilla única de renglón nuevo. El JS la clona, resuelve IDs de catálogo
  y envía el lote al endpoint guardarLote.
  Las filas existentes se dibujan por JS con sus botones Editar/Cancelar.
-->
<template id="mfTrPlantillaFila">
  <tr class="mf-tr-fila mf-tr-fila-nueva" data-estado="nuevo">
    <td class="text-center mf-tr-numero">Nuevo</td>
    <td>
      <div class="mf-tr-autocomplete">
        <input type="hidden" class="mf-tr-transportista-id" value="">
        <input type="text"
               class="form-control form-control-sm mf-tr-codigo-input mf-tr-transportista-codigo"
               maxlength="4"
               autocomplete="off"
               spellcheck="false"
               placeholder="TSAN"
               aria-label="Código del transportista"
               required>
        <span class="text-muted mf-tr-resuelto mf-tr-transportista-nombre">Selecciona transportista</span>
        <div class="list-group mf-tr-sugerencias mf-tr-sugerencias-transportista d-none"
             role="listbox"></div>
      </div>
    </td>
    <td>
      <div class="mf-tr-autocomplete">
        <input type="hidden" class="mf-tr-origen-id" value="">
        <input type="text"
               class="form-control form-control-sm mf-tr-codigo-input mf-tr-origen-codigo"
               maxlength="4"
               autocomplete="off"
               spellcheck="false"
               placeholder="BDHC"
               aria-label="Código de origen"
               required>
        <span class="text-muted mf-tr-resuelto mf-tr-origen-nombre">Selecciona origen</span>
        <div class="list-group mf-tr-sugerencias mf-tr-sugerencias-origen d-none"
             role="listbox"></div>
      </div>
    </td>
    <td>
      <div class="mf-tr-autocomplete">
        <input type="hidden" class="mf-tr-destino-id" value="">
        <input type="text"
               class="form-control form-control-sm mf-tr-codigo-input mf-tr-destino-codigo"
               maxlength="4"
               autocomplete="off"
               spellcheck="false"
               placeholder="YDTE"
               aria-label="Código de destino"
               required>
        <span class="text-muted mf-tr-resuelto mf-tr-destino-nombre">Selecciona destino</span>
        <div class="list-group mf-tr-sugerencias mf-tr-sugerencias-destino d-none"
             role="listbox"></div>
      </div>
    </td>
    <td>
      <div class="input-group input-group-sm">
        <span class="input-group-text">$</span>
        <input type="number"
               class="form-control text-end mf-tr-monto"
               min="0"
               max="9999999999.99"
               step="0.01"
               inputmode="decimal"
               placeholder="0.00"
               aria-label="Costo en dólares"
               required>
      </div>
    </td>
    <td class="text-center">
      <span class="badge bg-warning text-dark mf-tr-etiqueta-estado">Pendiente</span>
    </td>
    <td class="text-center mf-tr-acciones">
      <button type="button"
              class="btn btn-sm btn-outline-danger mf-tr-btn-quitar"
              title="Quitar fila sin guardar"
              aria-label="Quitar fila sin guardar">
        <i data-feather="x" class="me-1"></i>Quitar
      </button>
    </td>
  </tr>
</template>
