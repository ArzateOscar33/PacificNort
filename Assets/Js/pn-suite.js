/* PacificNort Suite | escaparate público, sin AJAX ni datos reales. */
(function () {
  "use strict";
  const root = document.getElementById("tecnologia");
  if (!root) return;
  const tablist = root.querySelector('[role="tablist"]');
  const tabs = Array.from(root.querySelectorAll('[role="tab"]'));
  const panels = Array.from(root.querySelectorAll('[role="tabpanel"]'));
  const dialog = root.querySelector("#pnSuiteDialog");
  if (!tablist || !tabs.length || tabs.length !== panels.length) return;
  // Fase 7.7.2: rotación automática accesible de ambas pantallas.
  const INTERVALO = 2000;
  const showcase = root.querySelector(".pn-suite-showcase");
  const label = root.querySelector(".pn-suite-showcase-label");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  let current = 0;
  let timer = null;
  let enabled = !reduceMotion.matches;
  let inView = !("IntersectionObserver" in window);
  let hovering = false;

  const playback = document.createElement("button");
  playback.type = "button";
  playback.className = "pn-suite-playback";
  const controls = document.createElement("span");
  controls.className = "pn-suite-playback-controls";
  const demoLabel = label?.querySelector(".pn-suite-demo-label");
  if (demoLabel) controls.appendChild(demoLabel);
  controls.appendChild(playback);
  if (label) label.appendChild(controls);
  else tablist.before(controls);

  const updatePlayback = () => {
    playback.textContent = enabled ? "Ⅱ Pausar" : "▷ Reproducir";
    playback.setAttribute(
      "aria-label",
      enabled
        ? "Pausar cambio automático de vistas"
        : "Reproducir cambio automático de vistas",
    );
  };
  const clearTimer = () => {
    if (timer !== null) window.clearTimeout(timer);
    timer = null;
  };
  const schedule = () => {
    clearTimer();
    if (!enabled || !inView || hovering || document.hidden || dialog?.open)
      return;
    timer = window.setTimeout(() => {
      timer = null;
      activate((current + 1) % tabs.length);
    }, INTERVALO);
  };
  const setPlayback = (value) => {
    enabled = value;
    updatePlayback();
    schedule();
  };
  const activate = (index, focus = false, animate = true) => {
    const changed = current !== index;
    current = index;
    tabs.forEach((tab, i) => {
      const active = index === i;
      tab.setAttribute("aria-selected", String(active));
      tab.tabIndex = active ? 0 : -1;
      panels[i].classList.remove("pn-suite-screen-enter");
      panels[i].hidden = !active;
      if (active && changed && animate && !reduceMotion.matches) {
        panels[i].classList.add("pn-suite-screen-enter");
      }
    });
    if (focus) tabs[index].focus();
    schedule();
  };
  playback.addEventListener("click", () => setPlayback(!enabled));
  if (showcase) {
    showcase.addEventListener("pointerenter", (event) => {
      if (event.pointerType !== "mouse" && event.pointerType !== "pen") return;
      hovering = true;
      clearTimer();
    });
    showcase.addEventListener("pointerleave", () => {
      hovering = false;
      schedule();
    });
    // La navegación con teclado detiene la rotación hasta pulsar Reproducir.
    showcase.addEventListener("focusin", (event) => {
      if (event.target !== playback) setPlayback(false);
    });
  }
  document.addEventListener("visibilitychange", schedule);
  reduceMotion.addEventListener("change", (event) => {
    if (event.matches) setPlayback(false);
  });
  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        inView = entries[0].isIntersecting;
        if (inView) {
          // Preparar las vistas antes de la primera rotación, sin tocar la BD.
          panels.forEach((panel) =>
            panel
              .querySelectorAll(".pn-suite-device-stage img")
              .forEach((img) => {
                img.loading = "eager";
              }),
          );
        }
        schedule();
      },
      { threshold: 0.15 },
    );
    observer.observe(showcase || root);
  }
  updatePlayback();
  tabs.forEach((tab, i) => {
    tab.addEventListener("click", () => {
      setPlayback(false);
      activate(i);
    });
    tab.addEventListener("keydown", (event) => {
      let next;
      switch (event.key) {
        case "ArrowRight":
          next = (i + 1) % tabs.length;
          break;
        case "ArrowLeft":
          next = (i + tabs.length - 1) % tabs.length;
          break;
        case "Home":
          next = 0;
          break;
        case "End":
          next = tabs.length - 1;
          break;
        default:
          return;
      }
      event.preventDefault();
      setPlayback(false);
      activate(next, true);
    });
  });
  tablist.hidden = false;
  activate(0, false, false);
  if (!dialog || typeof dialog.showModal !== "function") return;
  const body = dialog.querySelector(".pn-suite-dialog-body");
  const title = dialog.querySelector("#pnSuiteDialogTitle");
  const close = dialog.querySelector(".pn-suite-dialog-close");
  let trigger = null;
  root.querySelectorAll("[data-pn-zoom]").forEach((button) => {
    button.hidden = false;
    button.addEventListener("click", () => {
      const panel = button.closest('[role="tabpanel"]');
      const source = panel?.querySelector(".pn-suite-device-stage");
      if (!source || dialog.open) return;
      trigger = button;
      const gallery = document.createElement("div");
      gallery.className = "pn-suite-zoom-gallery";
      source.querySelectorAll("img").forEach((img, index) => {
        const figure = document.createElement("figure");
        figure.className =
          index === 0 ? "pn-suite-zoom-desktop" : "pn-suite-zoom-mobile";
        const caption = document.createElement("figcaption");
        caption.textContent =
          index === 0 ? "Vista de escritorio" : "Vista móvil";
        const clone = img.cloneNode(true);
        clone.loading = "eager";
        const scroll = document.createElement("div");
        scroll.className = "pn-suite-zoom-scroll";
        scroll.appendChild(clone);
        figure.append(caption, scroll);
        gallery.appendChild(figure);
      });
      body.replaceChildren(gallery);
      const active = tabs.find(
        (tab) => tab.getAttribute("aria-selected") === "true",
      );
      title.textContent =
        "Demostración · " + (active?.textContent.trim() || "PacificNort Suite");
      clearTimer();
      dialog.showModal();
      body.scrollTop = 0;
      body.scrollLeft = 0;
      close.focus();
    });
  });
  close.addEventListener("click", () => dialog.close());
  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener("close", () => {
    body.replaceChildren();
    trigger?.focus();
    trigger = null;
    schedule();
  });
})();
