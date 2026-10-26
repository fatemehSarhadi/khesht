import { listProperties } from "../api/properties.js";
import { propertyCardHtml } from "./property-card.js";
import { initFx } from "./fx.js";

function paramsFromForm(form) {
  const data = new FormData(form);
  const params = {};
  for (const [key, value] of data.entries()) {
    if (String(value).trim()) params[key] = String(value).trim();
  }
  return params;
}

function paramsFromUrl() {
  const q = new URLSearchParams(window.location.search);
  const keys = [
    "type",
    "deal",
    "city",
    "region",
    "neighborhood",
    "price_min",
    "price_max",
    "area_min",
    "area_max",
    "featured",
    "page",
  ];
  const params = {};
  keys.forEach((key) => {
    if (q.get(key)) params[key] = q.get(key);
  });
  return params;
}

function syncForm(form, params) {
  Object.entries(params).forEach(([key, value]) => {
    const field = form.elements.namedItem(key);
    if (field) field.value = value;
  });
}

function writeUrl(params) {
  const url = new URL(window.location.href);
  url.search = "";
  Object.entries(params).forEach(([key, value]) => {
    url.searchParams.set(key, value);
  });
  history.replaceState({}, "", url);
}

export function initPropertyFilters() {
  const form = document.querySelector("[data-property-filters]");
  const grid = document.querySelector("[data-property-grid]");
  if (!form || !grid) return;

  const apply = async (params) => {
    writeUrl(params);
    const data = await listProperties(params);
    grid.innerHTML = data.items.map(propertyCardHtml).join("") ||
      `<p>فایلی با این مشخصات یافت نشد.</p>`;
    initFx(grid);
  };

  const initial = paramsFromUrl();
  syncForm(form, initial);
  apply(initial);

  form.addEventListener("change", () => apply(paramsFromForm(form)));
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    apply(paramsFromForm(form));
  });
}

// Home page: show a few featured/latest properties (no filter form).
export function initHomeProperties() {
  const grid = document.querySelector("[data-property-home]");
  if (!grid) return;
  const limit = Number(grid.dataset.limit) || 3;
  listProperties({ per_page: limit })
    .then((data) => {
      grid.innerHTML = data.items.slice(0, limit).map(propertyCardHtml).join("");
      initFx(grid);
    })
    .catch(() => {
      grid.closest("section")?.setAttribute("hidden", "");
    });
}

// Dedicated category pages (e.g. فایل‌های اقتصادی): only files of one property type.
export function initFixedProperties() {
  const grid = document.querySelector("[data-property-fixed]");
  if (!grid) return;
  const empty = document.querySelector("[data-property-fixed-empty]");
  listProperties({ type: grid.dataset.type, per_page: 60 })
    .then((data) => {
      grid.innerHTML = data.items.map(propertyCardHtml).join("");
      if (empty) empty.hidden = data.items.length > 0;
      initFx(grid);
    })
    .catch(() => {
      if (empty) empty.hidden = false;
    });
}
