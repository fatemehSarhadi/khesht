import { getMagazine } from "../api/content.js";
import { initFx } from "./fx.js";

const esc = (v) =>
  String(v ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);
const fa = new Intl.DateTimeFormat("fa-IR", { dateStyle: "medium" });
const num = new Intl.NumberFormat("fa-IR");
const PER_PAGE = 9;

export function articleCardHtml(a, i = 0) {
  const img = a.image || `/media/mag-${(i % 3) + 1}.jpg`;
  const date = a.date && !Number.isNaN(new Date(a.date)) ? fa.format(new Date(a.date)) : "";
  const meta = [a.category?.name, date].filter(Boolean).join(" · ");
  return `
    <a class="card tilt" data-reveal data-cat="${esc(a.category?.slug || "")}" href="${esc(a.url || `/mag/article/?id=${a.id}`)}">
      <div class="card__media"><img src="${esc(img)}" alt="${esc(a.title)}" loading="lazy" /></div>
      <div class="card__body">
        <p class="article-meta">${esc(meta)}</p>
        <h3>${esc(a.title)}</h3>
        <p>${esc(a.excerpt)}</p>
        <span class="card__more">بیشتر بخوانید <svg class="icon"><use href="#i-arrow-left"/></svg></span>
      </div>
    </a>`;
}

/* Fallback when the API is unreachable: filter the static cards that are already in the page. */
function bindStaticFilter(list, chips, empty) {
  const apply = (cat) => {
    chips.forEach((c) => {
      const on = (c.dataset.catChip || "all") === cat;
      c.classList.toggle("is-active", on);
      c.setAttribute("aria-pressed", String(on));
    });
    let shown = 0;
    list.querySelectorAll("[data-cat]").forEach((card) => {
      const show = cat === "all" || card.dataset.cat === cat;
      card.hidden = !show;
      if (show) {
        shown++;
        card.classList.add("is-in");
      }
    });
    if (empty) empty.hidden = shown > 0;
  };
  chips.forEach((chip) => chip.addEventListener("click", () => apply(chip.dataset.catChip || "all")));
  apply("all");
}

export function initMagazineFilter() {
  const list = document.querySelector("[data-mag-list]");
  if (!list) return;
  const chips = [...document.querySelectorAll("[data-cat-chip]")];
  const empty = document.querySelector("[data-mag-empty]");
  const more = document.querySelector("[data-mag-more]");
  const count = document.querySelector("[data-mag-count]");
  const fixedCat = list.dataset.magCategory || "";
  const valid = new Set(chips.map((c) => c.dataset.catChip || "all"));
  const fromUrl = new URLSearchParams(window.location.search).get("cat");
  let cat = fromUrl && valid.has(fromUrl) ? fromUrl : "all";
  let page = 1;
  let busy = false;

  const markChips = () =>
    chips.forEach((c) => {
      const on = (c.dataset.catChip || "all") === cat;
      c.classList.toggle("is-active", on);
      c.setAttribute("aria-pressed", String(on));
    });

  const writeUrl = () => {
    if (fixedCat) return;
    const url = new URL(window.location.href);
    if (cat === "all") url.searchParams.delete("cat");
    else url.searchParams.set("cat", cat);
    history.replaceState({}, "", url);
  };

  async function load(append) {
    if (busy) return;
    busy = true;
    if (more) more.disabled = true;
    try {
      const data = await getMagazine({ category: fixedCat || (cat === "all" ? undefined : cat), page, per_page: PER_PAGE });
      const html = data.items.map(articleCardHtml).join("");
      if (append) list.insertAdjacentHTML("beforeend", html);
      else list.innerHTML = html;
      initFx(list);
      if (empty) empty.hidden = data.items.length > 0 || append;
      if (count) count.textContent = data.total ? `${num.format(data.total)} مطلب` : "";
      if (more) more.hidden = page >= (data.total_pages || 1);
    } catch (error) {
      if (!append && page === 1 && list.dataset.fallback !== "1") {
        list.dataset.fallback = "1";
        bindStaticFilter(list, chips, empty);
      }
    } finally {
      busy = false;
      if (more) more.disabled = false;
    }
  }

  chips.forEach((chip) =>
    chip.addEventListener("click", () => {
      if (list.dataset.fallback === "1") return;
      cat = chip.dataset.catChip || "all";
      page = 1;
      markChips();
      writeUrl();
      load(false);
    })
  );
  more?.addEventListener("click", () => {
    page += 1;
    load(true);
  });
  markChips();
  load(false);
}

// Home: latest N articles
export function initLatestArticles() {
  const box = document.querySelector("[data-articles]");
  if (!box) return;
  const limit = Number(box.dataset.limit) || 3;
  getMagazine({ per_page: limit })
    .then((data) => {
      if (!data.items?.length) return;
      box.innerHTML = data.items.slice(0, limit).map(articleCardHtml).join("");
      initFx(box);
    })
    .catch(() => {});
}
