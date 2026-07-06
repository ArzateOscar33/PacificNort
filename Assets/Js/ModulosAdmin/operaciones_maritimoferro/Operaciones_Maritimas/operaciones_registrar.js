// ====== Operaciones Marítimo-Ferroviarias: Llenado y Registro ======

// Refs del modal MF
const formOpMF = document.getElementById("formOperacionMaritimoFerro");
const btnSaveMF = document.getElementById("btnGuardarOperacion_mf");
const modalElMF = document.getElementById("modalMaritimoFerro");
function getModalMF() {
  if (!modalElMF || !window.bootstrap) return null;
  return bootstrap.Modal.getOrCreateInstance(modalElMF);
}

// Campos base (MF)
const inpBL_MF = document.getElementById("numeroBL_mf");
const selSubtipoMF = document.getElementById("subtipoOperacion_mf");
const inpNumeroOp_MF = document.getElementById("numeroOperacion_mf");
const selEstatusMF = document.getElementById("estatusId_mf");
const inpETD_MF = document.getElementById("etd_mf");
const inpETA_MF = document.getElementById("eta_mf");
const selPuertoMF = document.getElementById("puertoArribo_mf");
const selNavieraMF = document.getElementById("navieraId_mf");
const selForwarderMF = document.getElementById("forwarderId_mf");
const selShipperMF = document.getElementById("shipperId_mf");
const hidClienteMF = document.getElementById("clienteId_mf");
const inpClienteMF = document.getElementById("clienteNombre_mf");
const helpFolioMF = document.getElementById("folioHelp_mf");
const chkISF_MF = document.getElementById("chkIsf"); // checkbox o select
const inpCita_MF = document.getElementById("cita_puerto"); // input date/datetime
const inpDescripcionMercancia_MF = document.getElementById(
  "descripcion_mercancia_mf",
); // textarea

// Repeater MF
const repeaterMF = document.getElementById("contenedoresRepeater_mf");
const tplContMF = document.getElementById("contenedorTemplate_mf");
const inpUbicacionActual_MF = document.getElementById("ubicacionActual_mf");
// ===== Helpers =====
function valStr(v) {
  return (v ?? "").toString().trim();
}
function isEmpty(v) {
  return valStr(v) === "";
}
function setHelpFolio(text, ok = false) {
  if (!helpFolioMF) return;
  helpFolioMF.classList.toggle("text-success", !!ok);
  helpFolioMF.classList.toggle("text-muted", !ok);
  helpFolioMF.textContent = text;
}
function resetRepeaterMF() {
  if (!repeaterMF || !tplContMF) return;
  repeaterMF.innerHTML = "";
  const node = tplContMF.content.cloneNode(true);
  const newItem = node.querySelector(".contenedor-item");
  repeaterMF.appendChild(newItem);
  if (window.feather?.replace) feather.replace();
}

// Recolecta contenedores del repeater (id/numero/bultos) – versión MF
function collectContenedoresMF() {
  const out = [];
  if (!repeaterMF) return out;
  repeaterMF.querySelectorAll(".contenedor-item").forEach((item) => {
    const id = valStr(item.querySelector(".contenedor-id_mf")?.value || "");
    const num = valStr(item.querySelector(".contenedor-input_mf")?.value || "");
    const bulV = valStr(
      item.querySelector(".contenedor-bultos_mf")?.value || "",
    );
    const tipo = valStr(item.querySelector(".contenedor-tipo_mf")?.value || "");
    const peso = valStr(item.querySelector(".pesoOperacion_mf")?.value || "");
    if (id !== "" || num !== "") {
      out.push({
        id: id || 0,
        numero: num,
        bultos: bulV === "" ? null : Number(bulV),
        tipo: tipo || null,
        peso: peso === "" ? null : Number(peso),
      });
    }
  });
  return out;
}

// ===== Validaciones (BL + Cliente + Requisitos por Subtipo) =====
const REGEX_ASCII_BL = /[^A-Za-z0-9]/g;

function limpiarBL_MF() {
  if (!inpBL_MF) return;
  const before = inpBL_MF.value || "";
  const clean = before.replace(REGEX_ASCII_BL, "").toUpperCase();
  if (clean !== before) inpBL_MF.value = clean;
}

function validarBL_MF() {
  if (!inpBL_MF) return true;
  const v = (inpBL_MF.value || "").trim();
  const ok = v.length > 0 && !REGEX_ASCII_BL.test(v);
  inpBL_MF.classList.toggle("is-invalid", !ok);
  inpBL_MF.setCustomValidity(ok ? "" : "Solo letras y números");
  return ok;
}

function validarClienteSeleccionadoMF() {
  if (!hidClienteMF || !inpClienteMF) return true;

  const id = valStr(hidClienteMF.value);
  const nom = valStr(inpClienteMF.value);

  // Cliente totalmente vacío
  if (id === "") {
    Swal?.fire(
      "Cliente requerido",
      "Selecciona un cliente de la lista antes de guardar la operación.",
      "warning",
    );

    inpClienteMF.classList.add("is-invalid");
    inpClienteMF.focus();
    return false;
  }

  // Nombre escrito pero sin ID válido
  if (nom !== "" && id === "") {
    Swal?.fire(
      "Cliente no válido",
      "Selecciona un cliente de la lista de sugerencias.",
      "warning",
    );

    inpClienteMF.classList.add("is-invalid");
    inpClienteMF.focus();
    return false;
  }

  inpClienteMF.classList.remove("is-invalid");
  return true;
}

inpClienteMF?.addEventListener("input", () => {
  inpClienteMF.classList.remove("is-invalid");
});

// Importante: usamos la validación ya montada en tu archivo MF anterior:
// validarCamposObligatorios() mira los requisitos del subtipo (naviera/forwarder) obtenidos con subtipo_info.
// Aquí solo la invocamos si existe; si no, hacemos un mínimo.
function validarMinimoMF() {
  if (typeof validarCamposObligatorios === "function")
    return validarCamposObligatorios();
  // fallback mínimo
  return !!selSubtipoMF?.value;
}

// ===== Prefill folio preliminar por subtipo (usa preview_folio del controlador MF) =====
function prefillNumeroPorSubtipoMF() {
  // ⛔ No tocar folio en edición
  const isEdit =
    document.getElementById("formOperacionMaritimoFerro")?.dataset?.mode ===
      "edit" ||
    (document.getElementById("id_operacion_mf")?.value || "").trim() !== "";
  if (isEdit) return;

  const subtipoId = (selSubtipoMF?.value || "").trim();
  if (!subtipoId) {
    if (inpNumeroOp_MF) {
      inpNumeroOp_MF.value = "";
      inpNumeroOp_MF.setAttribute("placeholder", "Se generará automáticamente");
      inpNumeroOp_MF.setAttribute("readonly", "readonly");
    }
    setHelpFolio("Selecciona un subtipo para generar el folio");
    return;
  }

  const x = new XMLHttpRequest();
  x.open(
    "GET",
    base_url +
      "Operaciones_maritimo_ferro/preview_folio?subtipo_id=" +
      encodeURIComponent(subtipoId),
    true,
  );
  x.onreadystatechange = function () {
    if (x.readyState !== 4) return;
    if (x.status !== 200) return;
    let d = {};
    try {
      d = JSON.parse(x.responseText || "{}");
    } catch (e) {}
    const ok = d && d.status === "success";
    const payload = ok ? (d.data ?? d) : null;
    if (ok && payload?.codigo && inpNumeroOp_MF) {
      const code = String(payload.codigo);
      inpNumeroOp_MF.value = code;
      inpNumeroOp_MF.setAttribute("readonly", "readonly");
      setHelpFolio(
        `Folio preliminar: ${code}. El definitivo se asigna al guardar.`,
        false,
      );
    }
  };
  x.send();
}

// ===== Guardar (POST MF) =====
function guardarOperacionMF() {
  return new Promise((resolve) => {
    if (!formOpMF || !btnSaveMF) {
      resolve(false);
      return;
    }
    window.guardarOperacionMF = guardarOperacionMF;
    // ---- helpers locales para UI ----
    const clearContenedorInvalid = () => {
      document
        .querySelectorAll("#contenedoresRepeater_mf .contenedor-input_mf")
        .forEach((el) => el.classList.remove("is-invalid"));
    };

    const markContenedorInvalid = (numero) => {
      const needle = String(numero || "")
        .trim()
        .toUpperCase();

      if (!needle) return false;

      let first = null;
      document
        .querySelectorAll("#contenedoresRepeater_mf .contenedor-item")
        .forEach((row) => {
          const inp = row.querySelector(".contenedor-input_mf");
          if (!inp) return;

          const val = String(inp.value || "")
            .trim()
            .toUpperCase();

          if (val && val === needle) {
            inp.classList.add("is-invalid");
            if (!first) first = inp;
          }
        });

      if (first) {
        try {
          first.scrollIntoView({ behavior: "smooth", block: "center" });
        } catch (e) {}
        first.focus?.();
        first.select?.();
        return true;
      }
      return false;
    };

    // Limpia marcados previos
    clearContenedorInvalid();

    // ---- validaciones existentes ----
    if (!validarBL_MF()) {
      Swal?.fire(
        "BL inválido",
        "El BL solo debe contener letras y números.",
        "warning",
      );
      inpBL_MF?.focus();
      resolve(false);
      return;
    }
    if (!validarMinimoMF()) {
      resolve(false);
      return;
    }
    if (!validarClienteSeleccionadoMF()) {
      resolve(false);
      return;
    }
    const ubicacionActual = (inpUbicacionActual_MF?.value || "").trim();

    if (ubicacionActual.length > 250) {
      Swal?.fire(
        "Ubicación actual muy larga",
        "La ubicación actual no puede superar los 250 caracteres.",
        "warning",
      );
      inpUbicacionActual_MF?.focus();
      resolve(false);
      return;
    }

    // Construir payload alineado a tu controlador MF::guardar()
    const fd = new FormData();

    // ✅ NAMES REALES (vista/controlador)
    fd.append("subtipo_operacion_id_mf", selSubtipoMF?.value || "");
    fd.append("numero_operacion_mf", inpNumeroOp_MF?.value.trim() || "");
    fd.append("estatus_id_mf", selEstatusMF?.value || "");
    fd.append("etd_mf", inpETD_MF?.value || "");
    fd.append("eta_mf", inpETA_MF?.value || "");
    fd.append(
      "ubicacion_actual_mf",
      (inpUbicacionActual_MF?.value || "").trim().toUpperCase(),
    );
    fd.append("numero_bl_mf", inpBL_MF?.value || "");

    fd.append("cliente_id_mf", hidClienteMF?.value || "");
    fd.append("naviera_id_mf", selNavieraMF?.value || "");
    fd.append("forwarder_id_mf", selForwarderMF?.value || "");
    fd.append("shipper_id_mf", selShipperMF?.value || "");
    fd.append(
      "descripcion_mercancia_mf",
      inpDescripcionMercancia_MF?.value.trim() || "",
    );

    // extras
    fd.append(
      "broker_id_mf",
      document.getElementById("brokerId_mf")?.value || "",
    );
    fd.append(
      "transportista_id_mf",
      document.getElementById("transportistaId_mf")?.value || "",
    );
    fd.append(
      "notas_mf",
      (document.getElementById("notas_mf")?.value || "").trim(),
    );

    // ✅ isf: el controlador usa isset($_POST['isf'])
    if (chkISF_MF?.checked) fd.append("isf", "1");

    // ✅ cita_puerto: name real
    fd.append("cita_puerto", inpCita_MF?.value || "");

    // ✅ Contenedores: nombres que el controlador espera
    const conts = collectContenedoresMF();
    conts.forEach((c) => {
      fd.append("contenedores_id[]", String(c.id || ""));
      fd.append("contenedores_codigo[]", String(c.numero || ""));
      fd.append(
        "contenedores_bultos[]",
        c.bultos === null ? "" : String(c.bultos),
      );
      fd.append("contenedores_tipo[]", String(c.tipo || ""));
      fd.append(
        "contenedores_peso[]",
        c.peso === null || c.peso === undefined ? "" : String(c.peso),
      );
    });

    // ⚠️ OJO: aquí tu código tenía un set a un name que NO usa tu controlador.
    // Tu controlador lee "numero_operacion_mf". Si quieres que el backend genere folio,
    // manda numero_operacion_mf vacío.
    if (inpNumeroOp_MF?.hasAttribute("readonly")) {
      fd.set("numero_operacion_mf", "");
    }

    const x = new XMLHttpRequest();
    x.open("POST", base_url + "Operaciones_maritimo_ferro/guardar", true);
    x.timeout = 20000;

    x.onerror =
      x.onabort =
      x.ontimeout =
        () => {
          Swal?.fire(
            "Error de red",
            "No se pudo registrar la operación.",
            "error",
          );
          resolve(false);
        };

    x.onreadystatechange = function () {
      if (x.readyState !== 4) return;

      let res = null;
      try {
        res = JSON.parse(x.responseText);
      } catch (e) {}

      if (x.status !== 200 || !res) {
        Swal?.fire("Error", "No se pudo registrar la operación.", "error");
        resolve(false);
        return;
      }

      if (res.status === "success") {
        const folioFinal =
          res.data?.numero_operacion || res.numero_operacion || "";

        if (inpNumeroOp_MF) {
          inpNumeroOp_MF.value = folioFinal;
          inpNumeroOp_MF.setAttribute("readonly", "readonly");
        }

        setHelpFolio(
          folioFinal
            ? `Folio definitivo asignado: ${folioFinal}`
            : "Folio asignado",
          true,
        );

        Swal?.fire(
          "¡Éxito!",
          `Operación creada${folioFinal ? " (" + folioFinal + ")" : ""}`,
          "success",
        );

        window.bootstrap
          ? bootstrap.Modal.getOrCreateInstance(modalElMF).hide()
          : null;

        formOpMF.reset();
        resetRepeaterMF?.();

        try {
          listar?.();
        } catch (e) {}

        resolve(true);
        return;
      }

      // ✅ NUEVO: warning con códigos (DUP_CONT_MES)
      if (res.status === "warning") {
        // Limpia marcados previos
        clearContenedorInvalid();

        if (res.code === "DUP_CONT_MES") {
          const cont = res.data?.contenedor || "";
          const op = res.data?.operacion || "";
          const mes = res.data?.mes || "";

          // Marca el input del contenedor (si está en el repeater)
          markContenedorInvalid(cont);

          Swal?.fire(
            "Contenedor duplicado",
            res.msg ||
              `El contenedor ${cont} ya está asignado a ${op} en ${mes}.`,
            "warning",
          );
          resolve(false);
          return;
        }

        // warning genérico
        Swal?.fire("Atención", res.msg || "Revisa los datos.", "warning");
        resolve(false);
        return;
      }

      // error normal
      Swal?.fire("Error", res.msg || "No se pudo registrar.", "error");
      resolve(false);
    };

    x.send(fd);
  });
}
// ===== Wire-up MF =====

// BL: sanitiza en input y valida al blur
inpBL_MF?.addEventListener("input", limpiarBL_MF);
inpBL_MF?.addEventListener("blur", () => {
  limpiarBL_MF();
  validarBL_MF();
});

// Al abrir “Nueva Operación” MF desde tu botón de la vista
document
  .getElementById("maritimo_ferro_btnNuevaOperacion")
  ?.addEventListener("click", () => {
    if (inpNumeroOp_MF) {
      inpNumeroOp_MF.value = "";
      inpNumeroOp_MF.setAttribute("placeholder", "Se generará automáticamente");
      inpNumeroOp_MF.setAttribute("readonly", "readonly");
    }
    setHelpFolio("Selecciona un subtipo para generar el folio");
    // ¡No llames prefill aquí si aún no hay subtipo seleccionado!
  });

selSubtipoMF?.addEventListener("change", () => {
  prefillNumeroPorSubtipoMF(); // aquí sí, porque ya hay valor
});

modalElMF?.addEventListener("shown.bs.modal", () => {
  if (selSubtipoMF?.value) prefillNumeroPorSubtipoMF(); // solo si ya trae un valor
});

// Cuando cambie el subtipo en el modal MF, repite el folio preliminar si estás creando
selSubtipoMF?.addEventListener("change", () => {
  // Solo crear (no edición)
  const idEdit =
    (document.getElementById("id_operacion_mf")?.value || "").trim() !== "";
  if (!idEdit) prefillNumeroPorSubtipoMF();

  if (typeof validarCamposObligatorios === "function") {
    if (validarCamposObligatorios()) btnSaveMF?.removeAttribute("disabled");
    else btnSaveMF?.setAttribute("disabled", "disabled");
  }
});

// Habilita/deshabilita Guardar según requisitos
[selSubtipoMF, selNavieraMF, selForwarderMF].forEach((el) => {
  el?.addEventListener("change", () => {
    if (typeof validarCamposObligatorios === "function") {
      if (validarCamposObligatorios()) btnSaveMF?.removeAttribute("disabled");
      else btnSaveMF?.setAttribute("disabled", "disabled");
    }
  });
});

// Al mostrar el modal MF: valida estado del botón
modalElMF?.addEventListener("shown.bs.modal", () => {
  const isEdit =
    formOpMF?.dataset?.mode === "edit" ||
    (document.getElementById("id_operacion_mf")?.value || "").trim() !== "";
  if (!isEdit && selSubtipoMF?.value) prefillNumeroPorSubtipoMF();

  if (typeof validarCamposObligatorios === "function") {
    if (validarCamposObligatorios()) btnSaveMF?.removeAttribute("disabled");
    else btnSaveMF?.setAttribute("disabled", "disabled");
  }
});
