import { getProject, getProjects } from "../api/content.js";
import { projectCardHtml } from "./projects.js";
import { initFx } from "./fx.js";
import {
  esc, icon, queryKey, setPageMeta, setHero, notFound, galleryHtml, bindGallery, specsHtml,
  featuresHtml, videoButton, shareHtml, bindShare, inlineFormHtml, bindInlineForm,
} from "./detail-ui.js";

const STATUS = { done: "اجراشده", ongoing: "در حال اجرا" };
const GROUP = { sakht: "ساخت‌وساز", shahrak: "شهرک‌سازی" };

function render(root, p) {
  const images = [...new Set([p.image, ...(p.gallery || [])].filter(Boolean))];
  const photos = images.length ? images : ["/media/sakht-1.jpg"];
  const rows = [
    ["دسته", GROUP[p.group] || ""],
    ["وضعیت", STATUS[p.status] || ""],
    ["موقعیت", p.location],
    ["متراژ / ابعاد", p.area],
    ["تعداد واحد", p.units],
    ["سال", p.year],
    ["مدت اجرا", p.duration],
    ["کارفرما", p.client],
  ];
  root.innerHTML = `
    <div class="detail">
      <div class="detail__main">
        ${galleryHtml(photos, p.title)}
        ${p.body ? `<h2 class="detail__h">درباره‌ی پروژه</h2><div class="prose">${p.body}</div>` : ""}
        <h2 class="detail__h">مشخصات پروژه</h2>
        ${specsHtml(rows)}
        ${p.features?.length ? `<h2 class="detail__h">ویژگی‌ها</h2>${featuresHtml(p.features)}` : ""}
      </div>
      <aside class="detail__aside">
        <div class="price-card">
          <div class="price-card__top">
            ${STATUS[p.status] ? `<span class="badge">${STATUS[p.status]}</span>` : ""}
            ${GROUP[p.group] ? `<span class="badge badge--brown">${GROUP[p.group]}</span>` : ""}
          </div>
          <h3 class="price-card__form-title" style="margin-top:0">پروژه‌ای مشابه می‌خواهید؟</h3>
          <div class="price-card__actions">${videoButton(p.video_url, "مشاهده ویدئو")}</div>
          ${inlineFormHtml(`پروژه: ${p.title}`)}
          ${shareHtml()}
        </div>
      </aside>
    </div>
    <section class="detail-related" data-related hidden>
      <h2 class="detail__h">پروژه‌های دیگر</h2>
      <div class="grid grid--3" data-related-grid></div>
    </section>`;
  bindGallery(root);
  bindInlineForm(root);
  bindShare(root, p.title);
  initFx(root);
}

async function loadRelated(root, p) {
  try {
    const data = await getProjects({ group: p.group, limit: 4 });
    const items = data.items.filter((x) => String(x.id) !== String(p.id)).slice(0, 3);
    if (!items.length) return;
    const box = root.querySelector("[data-related]");
    root.querySelector("[data-related-grid]").innerHTML = items.map(projectCardHtml).join("");
    box.hidden = false;
    initFx(box);
  } catch {
    /* optional */
  }
}

export async function initProjectDetail() {
  const root = document.querySelector("[data-project-detail]");
  if (!root) return;
  const key = queryKey();
  const back = ["/sakht/projects/", "بازگشت به پروژه‌ها"];
  if (!key) return notFound(root, ...back);
  try {
    const p = await getProject(key);
    if (!p?.title) throw new Error("not found");
    setPageMeta(p.title, p.excerpt);
    setHero(p.title, [GROUP[p.group], p.location].filter(Boolean).join(" · "));
    const img = document.querySelector(".hero__image");
    if (img && p.group === "shahrak") img.src = "/media/hero-shahrak.jpg";
    render(root, p);
    loadRelated(root, p);
  } catch {
    notFound(root, ...back);
  }
}
