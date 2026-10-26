/* Content that editors manage in WordPress: hero photos, contact info, services, team, partners, area prices.
   Static HTML stays as the fallback when the API is empty or unreachable. */
import { getSite, getServices, getTeam, getPartners, getAreaPrices } from "../api/content.js";
import { initFx } from "./fx.js";

const esc = (v) =>
  String(v ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);
const icon = (n) => `<svg class="icon"><use href="#i-${esc(n)}"/></svg>`;
const fa = new Intl.NumberFormat("fa-IR");

const HERO_SLOT = {
  amlak: "amlak",
  sakht: "sakht",
  "sakht-projects": "sakht",
  shahrak: "shahrak",
  mag: "mag",
  bazar: "bazar",
  about: "about",
  card: "card",
  hamkari: "hamkari",
};

function applySite(data) {
  const heroes = data.heroes || {};
  const page = document.body.dataset.page;
  if (page === "home") {
    document.querySelectorAll(".slider__slide img").forEach((img, i) => {
      if (heroes[`home${i + 1}`]) img.src = heroes[`home${i + 1}`];
    });
  } else if (heroes[HERO_SLOT[page]]) {
    const img = document.querySelector(".hero__image");
    if (img) img.src = heroes[HERO_SLOT[page]];
  }
  const ceo = data.site?.ceo;
  if (ceo) {
    document.querySelectorAll("[data-ceo-name]").forEach((el) => ceo.name && (el.textContent = ceo.name));
    document.querySelectorAll("[data-ceo-title]").forEach((el) => ceo.title && (el.textContent = ceo.title));
    document.querySelectorAll("[data-ceo-photo]").forEach((el) => ceo.photo && (el.src = ceo.photo));
    document.querySelectorAll("[data-ceo-bio]").forEach((el) => ceo.bio && (el.innerHTML = ceo.bio));
  }
  const s = data.site || {};
  document.querySelectorAll('[data-site="phone"]').forEach((el) => {
    if (!s.phone) return;
    el.textContent = s.phone;
    if (el.tagName === "A") el.href = `tel:+98${String(s.phone).replace(/^0/, "")}`;
  });
  document.querySelectorAll('[data-site="address"]').forEach((el) => {
    if (s.address) el.textContent = s.address;
  });
  document.querySelectorAll('[data-site="copyright"]').forEach((el) => {
    if (s.copyright) el.textContent = s.copyright;
  });
  document.querySelectorAll("[data-site-social]").forEach((el) => {
    const url = s.social?.[el.dataset.siteSocial];
    if (url) el.href = url;
  });
}

const serviceHtml = (s, i) => `
  <article class="service tilt" data-reveal>
    <span class="service__num" aria-hidden="true">${String(i + 1).padStart(2, "0")}</span>
    <div class="service__icon z2">${icon(s.icon || "building")}</div>
    <h3 class="z1">${esc(s.title)}</h3>
    <p class="z1">${esc(s.description)}</p>
  </article>`;

const teamHtml = (m, i) => `
  <article class="card person tilt" data-reveal>
    <div class="person__photo"><img src="${esc(m.photo || `/media/team-${(i % 3) + 1}.jpg`)}" alt="${esc(m.name)}" loading="lazy" /></div>
    <div class="card__body"><h3>${esc(m.name)}</h3><span class="person__role">${esc(m.role)}</span><p>${esc(m.bio)}</p></div>
  </article>`;

const partnerHtml = (p, i) => `
  <${p.url ? `a href="${esc(p.url)}" target="_blank" rel="noopener"` : "article"} class="service tilt" data-reveal>
    <span class="service__num" aria-hidden="true">${String(i + 1).padStart(2, "0")}</span>
    <div class="service__icon z2">${p.logo ? `<img src="${esc(p.logo)}" alt="" style="width:34px;height:34px;object-fit:contain" />` : icon("handshake")}</div>
    <h3 class="z1">${esc(p.name)}</h3>
    <p class="z1">${esc(p.description)}</p>
  </${p.url ? "a" : "article"}>`;

const areaHtml = (a, i) => `
  <article class="service tilt" data-reveal>
    <span class="service__num" aria-hidden="true">${String(i + 1).padStart(2, "0")}</span>
    <div class="service__icon z2">${icon("pin")}</div>
    <h3 class="z1">${esc(a.title)}</h3>
    ${a.range ? `<p class="z1"><strong>${esc(a.range)}</strong></p>` : ""}
    <p class="z1">${esc(a.summary)}</p>
  </article>`;

function fill(selector, loader, render) {
  const box = document.querySelector(selector);
  if (!box) return;
  loader()
    .then((data) => {
      if (!data.items?.length) return;
      box.innerHTML = data.items.map(render).join("");
      initFx(box);
    })
    .catch(() => {});
}

export function initSiteContent() {
  getSite().then(applySite).catch(() => {});
  fill("[data-services]", getServices, serviceHtml);
  fill("[data-team]", getTeam, teamHtml);
  fill("[data-partners]", getPartners, partnerHtml);
  fill("[data-area-prices]", getAreaPrices, areaHtml);
  void fa;
}
