export function initHeader() {
  const toggle = document.querySelector(".nav-toggle");
  const nav = document.querySelector(".nav");
  const page = document.body?.dataset.page;
  const map = {
    home: "home",
    amlak: "amlak",
    property: "amlak",
    sakht: "sakht",
    shahrak: "shahrak",
    mag: "mag",
    article: "mag",
    bazar: "mag",
    about: "about",
    card: "card",
    "sakht-projects": "sakht",
    project: "sakht",
    economic: "amlak",
    ceo: "about",
    hamkari: "hamkari",
  };
  const current = map[page];
  if (current) {
    document
      .querySelectorAll(`[data-nav="${current}"]`)
      .forEach((el) => el.setAttribute("aria-current", "page"));
  }

  const items = [...document.querySelectorAll(".nav__item.has-sub")];
  const closeAll = (except) =>
    items.forEach((item) => {
      if (item !== except) {
        item.classList.remove("is-open");
        item.querySelector(".nav__more")?.setAttribute("aria-expanded", "false");
      }
    });

  items.forEach((item) => {
    const btn = item.querySelector(".nav__more");
    btn?.addEventListener("click", (event) => {
      event.stopPropagation();
      const open = item.classList.toggle("is-open");
      btn.setAttribute("aria-expanded", String(open));
      closeAll(item);
    });
  });
  document.addEventListener("click", (event) => {
    if (!event.target.closest(".nav__item")) closeAll();
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeAll();
      nav?.classList.remove("is-open");
      toggle?.setAttribute("aria-expanded", "false");
    }
  });

  if (!toggle || !nav) return;
  toggle.addEventListener("click", () => {
    const open = nav.classList.toggle("is-open");
    toggle.setAttribute("aria-expanded", String(open));
  });
  nav.addEventListener("click", (event) => {
    if (event.target.closest(".nav__sub a")) {
      nav.classList.remove("is-open");
      toggle.setAttribute("aria-expanded", "false");
    }
  });
}
