/* Vrindavan Resort Sikkim — shared interactions */
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

  // Gallery lightbox
  const items = Array.from(document.querySelectorAll(".g-item"));
  if (items.length) {
    const lb = document.querySelector(".lightbox");
    const lbImg = lb.querySelector("img");
    let idx = 0;
    const show = (i) => {
      idx = (i + items.length) % items.length;
      const img = items[idx].querySelector("img");
      lbImg.src = img.src;
      lbImg.alt = img.alt;
    };
    items.forEach((it, i) =>
      it.addEventListener("click", () => {
        show(i);
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

  // Guests & Rooms picker — rooms follow the group size (e.g. 2 adults + 1 child per room:
  // 3 adults → 2 rooms, 5 adults → 3 rooms). Guests may add rooms, up to one per adult.
  document.querySelectorAll("[data-guest-picker]").forEach((picker) => {
    const toggle = picker.querySelector(".guest-picker-toggle");
    const panel = picker.querySelector(".guest-picker-panel");
    const perAdults = Number(picker.dataset.perRoomAdults) || 2;
    const perChildren = Number(picker.dataset.perRoomChildren) || 0;
    const input = (field) => picker.querySelector(`input[name="${field}"]`);
    const state = { adults: Number(input("adults").value), children: Number(input("children").value), rooms: Number(input("rooms").value) };
    const limits = { adults: [1, 12], children: [0, 8] };
    const plural = (n, one, many) => `${n} ${n === 1 ? one : many}`;
    const roomsNeeded = (adults, children) =>
      Math.max(1, Math.ceil(adults / perAdults), perChildren > 0 ? Math.ceil(children / perChildren) : 0);
    // Children need an adult in their room, so cap them by what the adults can take.
    const maxChildren = () => Math.min(limits.children[1], perChildren * state.adults);

    // Rooms the guest added on top of what the group needs. Only these are sent: otherwise each room
    // type works out its own count (a roomier cottage needs fewer rooms than the picker's estimate).
    let extraRooms = Math.max(0, state.rooms - roomsNeeded(state.adults, state.children));

    const render = () => {
      state.children = Math.min(state.children, maxChildren());
      const needed = roomsNeeded(state.adults, state.children);
      state.rooms = Math.min(state.adults, needed + extraRooms);
      extraRooms = state.rooms - needed;
      ["adults", "children", "rooms"].forEach((field) => {
        input(field).value = state[field];
        picker.querySelector(`[data-count="${field}"]`).textContent = state[field];
      });
      input("rooms").disabled = extraRooms === 0;
      const bounds = { adults: limits.adults, children: [0, maxChildren()], rooms: [needed, state.adults] };
      picker.querySelectorAll("[data-step]").forEach((button) => {
        const [min, max] = bounds[button.dataset.step];
        const value = state[button.dataset.step];
        button.disabled = Number(button.dataset.delta) < 0 ? value <= min : value >= max;
      });
      picker.querySelector("[data-guest-summary]").textContent =
        plural(state.adults, "Adult", "Adults") +
        (state.children ? `, ${plural(state.children, "Child", "Children")}` : "") +
        ` | ${plural(state.rooms, "Room", "Rooms")}`;
    };

    picker.querySelectorAll("[data-step]").forEach((button) =>
      button.addEventListener("click", () => {
        const delta = Number(button.dataset.delta);
        if (button.dataset.step === "rooms") {
          extraRooms = Math.max(0, extraRooms + delta);
        } else {
          state[button.dataset.step] += delta;
        }
        render();
      })
    );

    // On the booking page the results must match the picker: closing it with new numbers re-runs the search.
    const snapshot = () => `${state.adults}/${state.children}/${state.rooms}`;
    let openedWith = snapshot();
    const open = (show) => {
      if (show === !panel.hidden) return;
      panel.hidden = !show;
      toggle.setAttribute("aria-expanded", String(show));
      if (show) {
        openedWith = snapshot();
        return;
      }
      const form = picker.closest("form");
      const datesChosen = form && [...form.querySelectorAll('input[name="check_in"], input[name="check_out"]')].every((i) => i.value);
      if ("autoSubmit" in picker.dataset && snapshot() !== openedWith && datesChosen) {
        form.requestSubmit();
      }
    };
    toggle.addEventListener("click", () => open(panel.hidden));
    picker.querySelector(".guest-picker-done").addEventListener("click", () => { open(false); toggle.focus(); });
    document.addEventListener("click", (e) => { if (!picker.contains(e.target)) open(false); });
    picker.addEventListener("keydown", (e) => { if (e.key === "Escape") { open(false); toggle.focus(); } });

    render();
  });

  // Password fields — the eye button shows or hides what was typed
  document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    const input = document.getElementById(button.getAttribute("aria-controls"));
    if (!input) return;
    button.addEventListener("click", () => {
      const show = input.type === "password";
      input.type = show ? "text" : "password";
      button.setAttribute("aria-pressed", String(show));
      button.setAttribute("aria-label", show ? "Hide password" : "Show password");
      input.focus();
    });
  });

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
