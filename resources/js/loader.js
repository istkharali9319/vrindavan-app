/*
 * Loading feedback for the website and the admin:
 *  - a thin gold progress bar at the top while the next page loads (links and form submits)
 *  - the clicked submit button shows a spinner and is disabled, which also stops double submits
 *
 * Opt out with data-no-loader on a link, form or button. Downloads (invoice PDFs, download links),
 * new tabs, in-page anchors, mailto/tel and dialog forms are skipped automatically.
 */
(function () {
  if (window.vrLoader) return;

  const style = document.createElement("style");
  style.textContent = `
    #vr-progress { position: fixed; top: 0; left: 0; z-index: 100000; height: 3px; width: 0; opacity: 0;
      background: linear-gradient(90deg, #c9a24a, #a87b45); box-shadow: 0 0 8px rgba(201, 162, 74, .6);
      transition: width .4s ease, opacity .3s ease; pointer-events: none; }
    #vr-progress.is-active { opacity: 1; }
    .vr-btn-loading { cursor: progress !important; opacity: .85; }
    .vr-spinner { display: inline-block; width: 1em; height: 1em; margin-right: .55em; vertical-align: -0.16em;
      border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%;
      animation: vr-spin .7s linear infinite; flex-shrink: 0; }
    @keyframes vr-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .vr-spinner { animation-duration: 1.6s; } #vr-progress { transition: none; } }
  `;
  document.head.appendChild(style);

  const bar = document.createElement("div");
  bar.id = "vr-progress";
  bar.setAttribute("role", "progressbar");
  bar.setAttribute("aria-label", "Loading");
  document.addEventListener("DOMContentLoaded", () => document.body.appendChild(bar));
  if (document.body) document.body.appendChild(bar);

  let creep = null;
  let safety = null;

  function start() {
    clearInterval(creep);
    clearTimeout(safety);
    bar.classList.add("is-active");
    let width = 12;
    bar.style.width = width + "%";
    creep = setInterval(() => {
      width += (90 - width) * 0.08;
      bar.style.width = width + "%";
    }, 200);
    // If the browser stays on this page (download, cancelled navigation…), stop after a while.
    safety = setTimeout(stop, 15000);
  }

  function stop() {
    clearInterval(creep);
    clearTimeout(safety);
    bar.style.width = "100%";
    setTimeout(() => {
      bar.classList.remove("is-active");
      bar.style.width = "0";
    }, 250);
    document.querySelectorAll(".vr-btn-loading").forEach(resetButton);
  }

  function busyButton(button) {
    if (!button || button.dataset.noLoader !== undefined || button.classList.contains("vr-btn-loading")) return;
    button.classList.add("vr-btn-loading");
    button.setAttribute("aria-busy", "true");
    if (button.tagName === "BUTTON") {
      const spinner = document.createElement("span");
      spinner.className = "vr-spinner";
      spinner.setAttribute("aria-hidden", "true");
      button.prepend(spinner);
    }
    // Disable after the browser has read the button's name/value for the submission.
    setTimeout(() => { button.disabled = true; }, 0);
  }

  function resetButton(button) {
    button.classList.remove("vr-btn-loading");
    button.removeAttribute("aria-busy");
    button.disabled = false;
    button.querySelectorAll(".vr-spinner").forEach((spinner) => spinner.remove());
  }

  function isDownload(url, element) {
    return element.hasAttribute("download") || /\/invoice(\/|\?|$)|\.(pdf|zip|csv|xlsx?)(\?|$)/i.test(url.pathname + url.search);
  }

  // Links to another page on this site
  document.addEventListener("click", (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest("a[href]");
    if (!link || link.closest("[data-no-loader]") || link.hasAttribute("data-bs-toggle")) return;
    if (link.target && link.target !== "_self") return;

    const href = link.getAttribute("href");
    if (!href || href.startsWith("#") || /^(mailto|tel|javascript):/i.test(href)) return;

    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin || isDownload(url, link)) return;
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return;

    start();
  });

  // Forms (runs after the form's own handlers, so a cancelled confirm() or failed check is respected)
  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (event.defaultPrevented || form.closest("[data-no-loader]") || form.method === "dialog") return;
    if (form.target && form.target !== "_self") return;

    const action = new URL(form.action || location.href, location.href);
    const submitter = event.submitter || form.querySelector('[type="submit"]');
    busyButton(submitter);
    if (!isDownload(action, form)) start();
    else setTimeout(stop, 4000);
  });

  // Coming back with the browser's Back button shows the page from memory: clear any loading state.
  window.addEventListener("pageshow", (event) => {
    if (event.persisted) stop();
  });

  window.vrLoader = { start, stop };
})();
