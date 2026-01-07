 <div class="container py-4 col-md-12">

     <!-- Encabezado -->
     <div class="d-flex justify-content-between align-items-center mb-3">
         <h4 class="mb-0">
             <i data-feather="layers" class="me-2"></i>Mercancía en Bodegas
         </h4>

         <!-- Solo informativo: sin botón de alta -->
         
  <div class="d-flex gap-2 align-items-center">
 

    <span class="badge bg-light text-dark border" id="mercanciaPisoBadgeTotal">
      Total: <span class="fw-semibold" id="mercanciaPisoTotal">0</span>
    </span>
    <span class="badge bg-info text-white" id="mercanciaPisoBadgeTJ">
      BODEGA TJ: <span class="fw-semibold" id="mercanciaPisoTotalTJ">0</span>
    </span>
    <span class="badge bg-primary text-white" id="mercanciaPisoBadgeSD">
      BODEGA SD: <span class="fw-semibold" id="mercanciaPisoTotalSD">0</span>
    </span>
  </div>
     </div>

     <!-- Filtros -->
     <div class="row g-3 align-items-end mb-4">

         <div class="col-12 col-md-4">
             <label for="mercanciaPisoBuscar" class="form-label">
                 Buscar (Cliente / Contenedor Marítimo)
             </label>
             <input type="text" id="mercanciaPisoBuscar" class="form-control" placeholder="Ej.  MSKU1234567">
         </div>

         <div class="col-12 col-md-3">
             <label for="mercanciaPisoFiltroBodega" class="form-label">Bodega</label>
             <select id="mercanciaPisoFiltroBodega" class="form-control">
                 <option value="" selected>Todas</option>
                 <option value="BODEGA TJ">BODEGA TJ</option>
                 <option value="BODEGA SD">BODEGA SD</option>
             </select>
              
         </div>

         <div class="col-12 col-md-5">
             <label class="form-label d-flex justify-content-between">
                 <span>Rango de fechas (opcional)</span>
             </label>
             <div class="d-flex gap-2 flex-wrap">
                 <input type="date" id="mercanciaPisoFechaDesde" class="form-control w-50" aria-label="Desde">
                 <input type="date" id="mercanciaPisoFechaHasta" class="form-control w-50" aria-label="Hasta">
             </div>
         </div>

         <div class="col-12 col-md-12">
             <div class="d-flex flex-wrap justify-content-between justify-content-md-end align-items-center gap-2">

                 <div class="btn-group" role="group" aria-label="Exportaciones">
                     <button class="btn btn-sm btn-outline-success" id="mercanciaPisoBtnExcel" type="button">
                         <i data-feather="file-text" class="me-1"></i> Excel
                     </button>
                     <button class="btn btn-sm btn-outline-warning" id="mercanciaPisoBtnPdf" type="button">
                         <i data-feather="file" class="me-1"></i> PDF
                     </button>
                       <button class="btn btn-sm btn-outline-primary" id="mercanciaPisoBtnActualizar" type="button">
                        <i data-feather="refresh-cw" class="me-1"></i> Actualizar
                    </button>
                 </div>

                 <div class="d-flex align-items-center ms-md-2">
                     <label for="mercanciaPisoPerPage" class="mb-0 small text-muted me-2">Mostrar</label>
                     <select id="mercanciaPisoPerPage" class="form-control" style="width: 90px;">
                         <option value="10" selected>10</option>
                         <option value="25">25</option>
                         <option value="50">50</option>
                         <option value="100">100</option>
                     </select>
                 </div>

             </div>
         </div>

     </div>

     <!-- Tabla -->
     <div class="table-responsive">
         <table class="table table-bordered align-middle" id="mercanciaPisoTabla">
             <thead class="table-light">
                 <tr>
                     <th>Bodega</th>
                     <th>Operacion</th>
                     <th>Cliente</th>
                     <th>Contenedor Marítimo</th>
                     <th>Bultos</th>
                     <th>Restantes</th> 
                 </tr>
             </thead>
             <tbody id="mercanciaPisoTbody">

                 <tr>
                     <td>
                         <span class="badge bg-info  text-white">BODEGA TJ</span>
                     </td>
                     <td>WALDOS</td>
                     <td>
                         <span class="fw-semibold">MSKU1234567</span>
                     </td>
                     <td>
                         <span class="badge bg-primary  text-white">120</span>
                     </td>
                     <td>
                         <span class="badge bg-warning text-dark">45</span>
                     </td>
                     <td class="text-center">
                         <button class="btn btn-sm btn-outline-secondary" disabled>
                             <i data-feather="eye"></i>
                         </button>
                     </td>
                 </tr>

                 <tr>
                     <td>
                         <span class="badge bg-primary  text-white">BODEGA SD</span>
                     </td>
                     <td>TOMMER</td>
                     <td>
                         <span class="fw-semibold">TGHU7654321</span>
                     </td>
                     <td>
                         <span class="badge bg-primary text-white">80</span>
                     </td>
                     <td>
                         <span class="badge bg-success text-white">80</span>
                     </td>
                     <td class="text-center">
                         <button class="btn btn-sm btn-outline-secondary" disabled>
                             <i data-feather="eye"></i>
                         </button>
                     </td>
                 </tr>

                 <tr>
                     <td>
                         <span class="badge bg-info  text-white">BODEGA TJ</span>
                     </td>
                     <td>HUSSNI</td>
                     <td>
                         <span class="fw-semibold">OOLU9988776</span>
                     </td>
                     <td>
                         <span class="badge bg-primary text-white">150</span>
                     </td>
                     <td>
                         <span class="badge bg-danger text-white">10</span>
                     </td>
                     <td class="text-center">
                         <button class="btn btn-sm btn-outline-secondary" disabled>
                             <i data-feather="eye"></i>
                         </button>
                     </td>
                 </tr>

             </tbody>

         </table>

         <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
             <div class="small text-muted">
                 <span id="mercanciaPisoMetaResumen">Mostrando 0-0 de 0</span>
             </div>
             <nav aria-label="Paginación Mercancía en Piso">
                 <ul id="mercanciaPisoPaginacion" class="pagination pagination-sm mb-0"></ul>
             </nav>
         </div>
     </div>

 

 </div>

 <script>
     feather.replace();
     const BASE_URL = "<?= BASE_URL ?>";
 </script>
 <script src="<?= BASE_URL ?>Assets/Js/ModulosAdmin/operaciones_maritimoferro/en_piso.js"></script>

 