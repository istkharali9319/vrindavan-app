/* Vrindavan Resort — shared interactions */
import "./loader";
import "./stay-dates";
(function () {
  const body = document.body;
  const header = document.querySelector(".site-header");

  // Header background on scroll
  const onScroll = () => header && header.classList.toggle("scrolled", window.scrollY > 40);
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  // Mobile nav
  const toggle = document.querySelector(".nav-toggle");
  if (toggle) {
    toggle.addEventListener("click", () => {
      const open = body.classList.toggle("nav-open");
      toggle.setAttribute("aria-expanded", open);
    });
    document.querySelectorAll(".main-nav a").forEach((a) =>
      a.addEventListener("click", () => body.classList.remove("nav-open"))
    );
  }

  // Reveal on scroll
  const reveals = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver(
      (entries) =>
        entries.forEach((e) => {
          if (e.isIntersecting) {
            e.target.classList.add("in");
            io.unobserve(e.target);
          }
        }),
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );
    reveals.forEach((el) => io.observe(el));
  } else {
    reveals.forEach((el) => el.classList.add("in"));
  }

  // Nearby carousel arrows
  document.querySelectorAll("[data-carousel]").forEach((track) => {
    const nav = track.nextElementSibling;
    if (!nav) return;
    const step = () => (track.firstElementChild ? track.firstElementChild.offsetWidth + 24 : track.clientWidth);
    const prev = nav.querySelector("[data-carousel-prev]");
    const next = nav.querySelector("[data-carousel-next]");
    if (prev) prev.addEventListener("click", () => track.scrollBy({ left: -step(), behavior: "smooth" }));
    if (next) next.addEventListener("click", () => track.scrollBy({ left: step(), behavior: "smooth" }));
  });

  // Category chips (Experience & Explore) — items carry space-separated data-cat values
  document.querySelectorAll("[data-filters]").forEach((group) => {
    const buttons = group.querySelectorAll("[data-filter]");
    const targets = document.querySelectorAll("main [data-cat]");
    buttons.forEach((btn) =>
      btn.addEventListener("click", () => {
        buttons.forEach((b) => b.classList.toggle("active", b === btn));
        const f = btn.dataset.filter;
        targets.forEach((el) => el.classList.toggle("is-hidden", f !== "all" && !el.dataset.cat.split(" ").includes(f)));
        document.querySelectorAll("[data-filter-group]").forEach((wrap) => {
          wrap.hidden = !wrap.querySelector("[data-cat]:not(.is-hidden)");
        });
        targets.forEach((el) => el.classList.contains("reveal") && el.classList.add("in"));
      })
    );
  });

  // Home booking bar — keep the "to" date after the "from" date
  const bar = document.querySelector(".booking-bar");
  if (bar) {
    const from = bar.elements.check_in;
    const to = bar.elements.check_out;
    const today = new Date().toISOString().slice(0, 10);
    from.min = today;
    to.min = today;
    from.addEventListener("change", () => {
      to.min = from.value || today;
      if (to.value && to.value <= from.value) to.value = "";
    });
  }

  // Gallery filter + lightbox
  const items = Array.from(document.querySelectorAll(".g-item"));
  if (items.length) {
    document.querySelectorAll(".filter-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        document.querySelectorAll(".filter-btn").forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        const f = btn.dataset.filter;
        items.forEach((it) => it.classList.toggle("hide", f !== "all" && it.dataset.cat !== f));
      });
    });

    const lb = document.querySelector(".lightbox");
    const lbImg = lb.querySelector("img");
    const lbCap = lb.querySelector(".lb-cap");
    let idx = 0;
    const visible = () => items.filter((it) => !it.classList.contains("hide"));
    const show = (i) => {
      const list = visible();
      idx = (i + list.length) % list.length;
      const it = list[idx];
      lbImg.src = it.querySelector("img").src;
      lbImg.alt = it.querySelector("img").alt;
      lbCap.textContent = it.querySelector("em").textContent;
    };
    items.forEach((it) =>
      it.addEventListener("click", () => {
        show(visible().indexOf(it));
        lb.classList.add("open");
        body.style.overflow = "hidden";
      })
    );
    const close = () => {
      lb.classList.remove("open");
      body.style.overflow = "";
    };
    lb.querySelector(".lb-close").addEventListener("click", close);
    lb.querySelector(".lb-prev").addEventListener("click", (e) => { e.stopPropagation(); show(idx - 1); });
    lb.querySelector(".lb-next").addEventListener("click", (e) => { e.stopPropagation(); show(idx + 1); });
    lb.addEventListener("click", (e) => { if (e.target === lb) close(); });
    document.addEventListener("keydown", (e) => {
      if (!lb.classList.contains("open")) return;
      if (e.key === "Escape") close();
      if (e.key === "ArrowLeft") show(idx - 1);
      if (e.key === "ArrowRight") show(idx + 1);
    });
  }

  // Contact form — prefill the message when arriving with stay dates in the link
  const form = document.querySelector("#contactForm");
  if (form) {
    const q = new URLSearchParams(location.search);
    if (q.get("from") && q.get("to") && !form.elements.message.value) {
      const kids = Number(q.get("children")) || 0;
      form.elements.message.value =
        `I'd like to stay from ${q.get("from")} to ${q.get("to")} — ` +
        `${q.get("adults") || 2} adult(s)${kids ? ` and ${kids} child(ren)` : ""}. Please share availability and rates.`;
    }
  }

  // Character counters (e.g. special requests on the booking form)
  document.querySelectorAll("[data-counter]").forEach((field) => {
    const output = document.getElementById(field.dataset.counter);
    if (!output) return;
    const update = () => (output.textContent = field.value.length);
    field.addEventListener("input", update);
    update();
  });

  // Two-factor challenge: switch between authenticator code and recovery code
  const twoFactorForm = document.querySelector("[data-two-factor-form]");
  const twoFactorToggle = document.querySelector("[data-two-factor-toggle]");
  if (twoFactorForm && twoFactorToggle) {
    twoFactorToggle.addEventListener("click", () => {
      const codeMode = twoFactorForm.querySelector('[data-mode="code"]');
      const recoveryMode = twoFactorForm.querySelector('[data-mode="recovery"]');
      const useRecovery = recoveryMode.hidden;
      codeMode.hidden = useRecovery;
      recoveryMode.hidden = !useRecovery;
      codeMode.querySelector("input").value = "";
      recoveryMode.querySelector("input").value = "";
      (useRecovery ? recoveryMode : codeMode).querySelector("input").focus();
      twoFactorToggle.textContent = useRecovery ? "Use your authenticator app instead" : "Lost your device? Use a recovery code";
    });
  }

  // Booking form: the submit button says what happens next (pay online, or confirm and pay at the resort)
  const payLabel = document.querySelector("[data-pay-label]");
  document.querySelectorAll('input[name="payment_method"][type="radio"]').forEach((option) => {
    option.addEventListener("change", () => {
      if (!option.checked) return;
      if (payLabel) payLabel.textContent = option.dataset.button;
      const note = document.querySelector("[data-pay-note]");
      if (note) note.textContent = option.value === "online" ? note.dataset.online : note.dataset.later;
    });
  });
})();
