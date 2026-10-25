/* Vrindavan admin: server-side filter forms (live search, auto-submit, focus restore) */
(function () {
  const SEARCH_DELAY_MS = 400;

  document.querySelectorAll("form[data-auto-submit]").forEach((form) => {
    const search = form.querySelector("[data-live-search]");
    let timer;

    const submit = () => {
      // Leave empty fields out of the URL so links stay short and readable.
      form.querySelectorAll("input, select").forEach((field) => {
        field.disabled = field.name !== "" && field.value === "";
      });
      if (search) {
        search.closest(".search-field")?.classList.add("is-loading");
        sessionStorage.setItem("admin-live-search", search.name);
      }
      window.vrLoader?.start();
      form.submit();
    };

    if (search) {
      search.addEventListener("input", () => {
        clearTimeout(timer);
        timer = setTimeout(submit, SEARCH_DELAY_MS);
      });
      search.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
          event.preventDefault();
          clearTimeout(timer);
          submit();
        }
      });

      // After a live-search reload, put the cursor back at the end of the search text.
      try {
        if (sessionStorage.getItem("admin-live-search") === search.name) {
          sessionStorage.removeItem("admin-live-search");
          search.focus();
          const end = search.value.length;
          search.setSelectionRange(end, end);
        }
      } catch (error) {
        // Storage can be unavailable (private mode); live search still works without focus restore.
      }
    }

    form.querySelectorAll("select, [data-change-submit]").forEach((field) => field.addEventListener("change", submit));

    // Coming back with the browser's Back button restores the page as it was submitted: re-enable fields.
    window.addEventListener("pageshow", () => {
      form.querySelectorAll("input, select").forEach((field) => (field.disabled = false));
      search?.closest(".search-field")?.classList.remove("is-loading");
    });
  });
})();
