/* About-page gallery: loaded from WordPress (Gallery menu), with a simple lightbox. */
import { getGallery } from "../api/content.js";
import { initFx } from "./fx.js";

const esc = (v) =>
  String(v ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);

function lightbox() {
  let box = document.querySelector(".lightbox");
  if (box) return box;
  box = document.createElement("div");
  box.className = "lightbox";
  box.setAttribute("role", "dialog");
  box.setAttribute("aria-modal", "true");
  box.innerHTML = `
    <button class="lightbox__close" type="button" aria-label="بستن"><svg class="icon"><use href="#i-x"/></svg></button>
    <button class="lightbox__nav lightbox__nav--prev" type="button" aria-label="قبلی"><svg class="icon"><use href="#i-chevron-right"/></svg></button>
    <figure><img alt="" /><figcaption></figcaption></figure>
    <button class="lightbox__nav lightbox__nav--next" type="button" aria-label="بعدی"><svg class="icon"><use href="#i-chevron-left"/></svg></button>`;
  document.body.appendChild(box);
  return box;
}

function bindLightbox(grid, items) {
  const box = lightbox();
  const img = box.querySelector("img");
  const cap = box.querySelector("figcaption");
  let i = 0;
  const show = (n) => {
    i = (n + items.length) % items.length;
    img.src = items[i].url;
    img.alt = items[i].caption || "";
    cap.textContent = items[i].caption || "";
  };
  const open = (n) => {
    show(n);
    box.classList.add("is-open");
    document.documentElement.style.overflow = "hidden";
  };
  const close = () => {
    box.classList.remove("is-open");
    document.documentElement.style.overflow = "";
  };
  grid.querySelectorAll("[data-lb]").forEach((el) => el.addEventListener("click", () => open(Number(el.dataset.lb))));
  box.querySelector(".lightbox__close").onclick = close;
  box.querySelector(".lightbox__nav--prev").onclick = () => show(i - 1);
  box.querySelector(".lightbox__nav--next").onclick = () => show(i + 1);
  box.onclick = (e) => {
    if (e.target === box) close();
  };
  document.addEventListener("keydown", (e) => {
    if (!box.classList.contains("is-open")) return;
    if (e.key === "Escape") close();
    if (e.key === "ArrowLeft") show(i + 1);
    if (e.key === "ArrowRight") show(i - 1);
  });
}

export function initGallery() {
  const grid = document.querySelector("[data-gallery]");
  if (!grid) return;
  const attach = (items) => bindLightbox(grid, items);
  getGallery()
    .then((data) => {
      if (!data.items?.length) return;
      grid.innerHTML = data.items
        .map(
          (g, i) =>
            `<figure class="photo tilt" data-reveal data-lb="${i}" role="button" tabindex="0"><img src="${esc(g.thumb || g.url)}" alt="${esc(g.caption)}" loading="lazy" /></figure>`
        )
        .join("");
      initFx(grid);
      attach(data.items);
    })
    .catch(() => {});
}
