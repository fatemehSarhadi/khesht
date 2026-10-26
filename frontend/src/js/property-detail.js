import { getProperty, listProperties } from "../api/properties.js";
import { propertyCardHtml, formatPrice, formatArea, propertyImage } from "./property-card.js";
import { initFx } from "./fx.js";
import {
  esc, icon, queryKey, setPageMeta, setHero, notFound, galleryHtml, bindGallery, specsHtml,
  featuresHtml, videoButton, shareHtml, bindShare, inlineFormHtml, bindInlineForm,
} from "./detail-ui.js";

const fa = new Intl.NumberFormat("fa-IR");

function mapHtml(p) {
  const lat = Number(p.lat);
  const lng = Number(p.lng);
  if (!Number.isFinite(lat) || !Number.isFinite(lng) || !p.lat) return "";
  const d = 0.006;
  const bbox = [lng - d, lat - d, lng + d, lat + d].join("%2C");
  return `
    <h2 class="detail__h">موقعیت روی نقشه</h2>
    <div class="detail__map">
      <iframe title="نقشه" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat}%2C${lng}"></iframe>
    </div>
    <p><a class="card__more" href="https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=16/${lat}/${lng}" target="_blank" rel="noopener">مشاهده در نقشه ${icon("arrow-left")}</a></p>`;
}

function render(root, p) {
  const images = [...new Set([p.image, ...(p.gallery || [])].filter(Boolean))];
  const photos = images.length ? images : [propertyImage(p)];
  const loc = p.location?.label || p.location || "";
  const s = p.specs || {};
  const rows = [
    ["نوع ملک", (p.property_type_labels || []).join("، ")],
    ["نوع معامله", (p.transaction_labels || []).join("، ")],
    ["موقعیت", loc],
    ["متراژ", p.area_m2 ? formatArea(p.area_m2) : ""],
    ["متراژ بنا", s.build_area ? `${s.build_area} متر مربع` : ""],
    ["تعداد خواب", s.bedrooms],
    ["سال ساخت", s.year_built],
    ["سبک ساخت", s.build_style],
    ["محوطه‌سازی", s.landscaping],
    ["نوع سند", s.deed],
    ["طبقه", s.floor],
    ["پارکینگ", s.parking],
    ["انباری", s.storage],
    ["آسانسور", s.elevator],
    ["کد فایل", p.code],
  ];
  const price = p.price_amount
    ? `<strong class="price-card__value">${formatPrice(p.price_amount, false)}</strong>`
    : `<strong class="price-card__value">توافقی</strong>`;
  const phone = p.contact_phone || document.querySelector('[data-site="phone"]')?.textContent || "09122615643";
  const tel = `tel:+98${String(phone).replace(/^0/, "").replace(/\D/g, "")}`;
  const deals = (p.transaction_labels || []).map((t) => `<span class="badge">${esc(t)}</span>`).join("");

  root.innerHTML = `
    <div class="detail">
      <div class="detail__main">
        ${galleryHtml(photos, p.title)}
        ${p.body ? `<h2 class="detail__h">توضیحات</h2><div class="prose">${p.body}</div>` : ""}
        <h2 class="detail__h">مشخصات ملک</h2>
        ${specsHtml(rows)}
        ${p.features?.length ? `<h2 class="detail__h">امکانات</h2>${featuresHtml(p.features)}` : ""}
        ${mapHtml(p)}
      </div>
      <aside class="detail__aside">
        <div class="price-card">
          <div class="price-card__top">${deals}${p.featured ? '<span class="badge badge--brown">ویژه</span>' : ""}</div>
          <span class="price-card__label">قیمت حدودی</span>
          ${price}
          ${p.negotiable ? '<small class="price-card__note">قیمت قابل مذاکره است</small>' : ""}
          ${p.code ? `<small class="price-card__note">کد فایل: ${esc(p.code)}</small>` : ""}
          <div class="price-card__actions">
            ${videoButton(p.video_url)}
            <a class="btn btn--outline" href="${tel}">${icon("phone")}تماس با کارشناس · <span dir="ltr">${esc(phone)}</span></a>
          </div>
          <h3 class="price-card__form-title">درخواست مشاوره درباره‌ی این ملک</h3>
          ${inlineFormHtml(`ملک: ${p.title}${p.code ? ` (کد ${p.code})` : ""}`)}
          ${shareHtml()}
        </div>
      </aside>
    </div>
    <section class="detail-related" data-related hidden>
      <h2 class="detail__h">املاک مشابه</h2>
      <div class="grid grid--3" data-related-grid></div>
    </section>`;
  bindGallery(root);
  bindInlineForm(root);
  bindShare(root, p.title);
  initFx(root);
}

async function loadRelated(root, p) {
  const type = p.property_type?.[0];
  try {
    const data = await listProperties({ type, per_page: 4 });
    const items = data.items.filter((x) => String(x.id) !== String(p.id)).slice(0, 3);
    if (!items.length) return;
    const box = root.querySelector("[data-related]");
    root.querySelector("[data-related-grid]").innerHTML = items.map(propertyCardHtml).join("");
    box.hidden = false;
    initFx(box);
  } catch {
    /* related is optional */
  }
}

export async function initPropertyDetail() {
  const root = document.querySelector("[data-property-detail]");
  if (!root) return;
  const key = queryKey();
  if (!key) return notFound(root, "/amlak/", "بازگشت به املاک");
  try {
    const p = await getProperty(key);
    if (!p?.title) throw new Error("not found");
    const loc = p.location?.label || p.location || "";
    setPageMeta(p.title, p.excerpt || `${p.title} — ${loc}`);
    setHero(p.title, loc);
    render(root, p);
    loadRelated(root, p);
    void fa;
  } catch {
    notFound(root, "/amlak/", "بازگشت به املاک");
  }
}
