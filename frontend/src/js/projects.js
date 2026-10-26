import { getProjects } from "../api/content.js";
import { initFx } from "./fx.js";

const esc = (v) =>
  String(v ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);
const STATUS = { done: "اجراشده", ongoing: "در حال اجرا" };

export function projectCardHtml(p, i = 0) {
  const img = p.image || `/media/sakht-${(i % 3) + 1}.jpg`;
  const badge = STATUS[p.status] ? `<span class="card__badge">${STATUS[p.status]}</span>` : "";
  return `
    <a class="card tilt" data-reveal href="${esc(p.url || `/project/?id=${p.id}`)}">
      <div class="card__media">${badge}<img src="${esc(img)}" alt="${esc(p.title)}" loading="lazy" /></div>
      <div class="card__body">
        <h3>${esc(p.title)}</h3>
        <p class="card__meta">
          ${p.location ? `<span><svg class="icon"><use href="#i-pin"/></svg>${esc(p.location)}</span>` : ""}
          ${p.area ? `<span><svg class="icon"><use href="#i-area"/></svg>${esc(p.area)}</span>` : ""}
          ${p.year ? `<span><svg class="icon"><use href="#i-clock"/></svg>${esc(p.year)}</span>` : ""}
        </p>
        ${p.excerpt ? `<p>${esc(p.excerpt)}</p>` : ""}
        <span class="card__more">مشاهده پروژه <svg class="icon"><use href="#i-arrow-left"/></svg></span>
      </div>
    </a>`;
}

// Fills [data-projects] grids from WordPress; static placeholder cards stay if the API has nothing yet.
export function initProjects() {
  document.querySelectorAll("[data-projects]").forEach((grid) => {
    const { group, status, limit } = grid.dataset;
    getProjects({ group, status, limit })
      .then((data) => {
        if (!data.items?.length) return;
        grid.innerHTML = data.items.map(projectCardHtml).join("");
        initFx(grid);
        const more = document.querySelector(`[data-projects-more="${group}"]`);
        if (more && limit && data.total <= Number(limit)) more.hidden = true;
      })
      .catch(() => {});
  });
}
