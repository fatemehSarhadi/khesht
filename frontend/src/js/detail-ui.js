/* Shared pieces for detail pages (property / project / article). */
import { submitConsultation } from "../api/forms.js";
import { formErrorMessage } from "./form-errors.js";

export const esc = (v) =>
  String(v ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);
const icon = (n) => `<svg class="icon"><use href="#i-${n}"/></svg>`;
export { icon };

export const queryKey = () => {
  const q = new URLSearchParams(window.location.search);
  return q.get("id") || q.get("slug") || "";
};

export function setPageMeta(title, description) {
  document.title = `${title} | خشت ۱۲۳`;
  const meta = document.querySelector('meta[name="description"]');
  if (meta && description) meta.setAttribute("content", String(description).slice(0, 160));
}

export function setHero(title, sub) {
  const h1 = document.querySelector(".hero h1");
  const p = document.querySelector(".hero__content > p:not(.hero__kicker)");
  if (h1) h1.textContent = title;
  if (p) p.textContent = sub || "";
}

export function notFound(root, backHref, backLabel) {
  root.innerHTML = `
    <div class="detail-empty">
      <h2>مورد پیدا نشد</h2>
      <p class="lede">ممکن است این صفحه حذف شده یا آدرس آن اشتباه باشد.</p>
      <a class="btn" href="${backHref}">${icon("arrow-right")}${esc(backLabel)}</a>
    </div>`;
}

/* ---------- gallery viewer ---------- */
export function galleryHtml(images, alt) {
  const list = images.length ? images : [];
  if (!list.length) return "";
  const thumbs =
    list.length > 1
      ? `<div class="gv__thumbs">${list
          .map(
            (src, i) =>
              `<button type="button" class="gv__thumb${i === 0 ? " is-active" : ""}" data-gv-thumb="${i}" aria-label="تصویر ${i + 1}"><img src="${esc(src)}" alt="" loading="lazy" /></button>`
          )
          .join("")}</div>`
      : "";
  const nav =
    list.length > 1
      ? `<button type="button" class="gv__nav gv__nav--prev" data-gv-prev aria-label="قبلی">${icon("chevron-right")}</button>
         <button type="button" class="gv__nav gv__nav--next" data-gv-next aria-label="بعدی">${icon("chevron-left")}</button>
         <span class="gv__count" data-gv-count>۱ / ${new Intl.NumberFormat("fa-IR").format(list.length)}</span>`
      : "";
  return `
    <div class="gv" data-gv data-images='${esc(JSON.stringify(list))}'>
      <div class="gv__stage"><img data-gv-main src="${esc(list[0])}" alt="${esc(alt)}" />${nav}</div>
      ${thumbs}
    </div>`;
}

export function bindGallery(root) {
  const gv = root.querySelector("[data-gv]");
  if (!gv) return;
  let images = [];
  try {
    images = JSON.parse(gv.dataset.images || "[]");
  } catch {
    images = [];
  }
  const main = gv.querySelector("[data-gv-main]");
  const count = gv.querySelector("[data-gv-count]");
  const thumbs = [...gv.querySelectorAll("[data-gv-thumb]")];
  const fa = new Intl.NumberFormat("fa-IR");
  let index = 0;
  const show = (i) => {
    index = (i + images.length) % images.length;
    main.classList.add("is-swapping");
    setTimeout(() => {
      main.src = images[index];
      main.classList.remove("is-swapping");
    }, 160);
    thumbs.forEach((t, k) => t.classList.toggle("is-active", k === index));
    if (count) count.textContent = `${fa.format(index + 1)} / ${fa.format(images.length)}`;
  };
  gv.querySelector("[data-gv-prev]")?.addEventListener("click", () => show(index - 1));
  gv.querySelector("[data-gv-next]")?.addEventListener("click", () => show(index + 1));
  thumbs.forEach((t) => t.addEventListener("click", () => show(Number(t.dataset.gvThumb))));
  gv.setAttribute("tabindex", "0");
  gv.addEventListener("keydown", (e) => {
    if (e.key === "ArrowLeft") show(index + 1);
    if (e.key === "ArrowRight") show(index - 1);
  });
}

/* ---------- specs / features ---------- */
export function specsHtml(rows) {
  const items = rows.filter(([, v]) => v !== "" && v !== null && v !== undefined && v !== false);
  if (!items.length) return "";
  return `<dl class="specs">${items
    .map(([k, v]) => `<div><dt>${esc(k)}</dt><dd>${esc(v === true ? "دارد" : v)}</dd></div>`)
    .join("")}</dl>`;
}

export function featuresHtml(list) {
  if (!list?.length) return "";
  return `<ul class="feature-chips">${list.map((f) => `<li>${icon("check")}${esc(f)}</li>`).join("")}</ul>`;
}

export function videoButton(url, label = "بازدید ویدئویی") {
  if (!url) return "";
  return `<a class="btn btn--video" href="${esc(url)}" target="_blank" rel="noopener">${icon("play")}${esc(label)}</a>`;
}

/* ---------- share ---------- */
export function shareHtml() {
  return `<div class="share"><span>اشتراک‌گذاری</span>
    <button type="button" class="share__btn" data-copy-link aria-label="کپی لینک">${icon("copy")}</button>
    <a class="share__btn" data-share-telegram target="_blank" rel="noopener" aria-label="اشتراک در تلگرام">${icon("telegram")}</a></div>`;
}

export function bindShare(root, title) {
  const url = window.location.href;
  const tg = root.querySelector("[data-share-telegram]");
  if (tg) tg.href = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
  const btn = root.querySelector("[data-copy-link]");
  btn?.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(url);
      btn.classList.add("is-done");
      setTimeout(() => btn.classList.remove("is-done"), 1600);
    } catch {
      window.prompt("لینک صفحه:", url);
    }
  });
}

/* ---------- inline consultation form (sidebar) ---------- */
export function inlineFormHtml(subject) {
  return `
    <form class="form form--compact" data-inline-consult>
      <input type="text" name="website" tabindex="-1" autocomplete="off" hidden />
      <input type="hidden" name="subject" value="${esc(subject)}" />
      <label>نام و نام خانوادگی<input name="name" required maxlength="80" autocomplete="name" /></label>
      <label>شماره تماس<input name="phone" required inputmode="tel" maxlength="15" autocomplete="tel" /></label>
      <label>توضیح (اختیاری)<textarea name="description" rows="2" maxlength="500"></textarea></label>
      <p data-form-error></p>
      <button class="btn" type="submit">${icon("chat")}درخواست مشاوره</button>
    </form>
    <div class="confirm-card" data-inline-confirm></div>`;
}

export function bindInlineForm(root) {
  const form = root.querySelector("[data-inline-consult]");
  if (!form) return;
  const confirm = root.querySelector("[data-inline-confirm]");
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const d = new FormData(form);
    if (d.get("website")) return;
    const description = String(d.get("description") || "").trim() || "لطفاً درباره‌ی این مورد با من تماس بگیرید.";
    try {
      await submitConsultation({
        name: d.get("name"),
        phone: d.get("phone"),
        subject: d.get("subject"),
        description,
        website: "",
      });
      form.hidden = true;
      confirm.textContent = "از ثبت درخواست شما بسیار متشکریم، کارشناسان ما در اسرع وقت با شما تماس خواهند گرفت.";
      confirm.classList.add("is-visible");
    } catch (error) {
      form.querySelector("[data-form-error]").textContent = formErrorMessage(error);
    }
  });
}
